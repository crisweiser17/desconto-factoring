/**
 * Utilitários de Matemática Financeira para Calculadora Flexível
 */

/**
 * Calcula o valor da Parcela (PMT)
 * @param {number} rate Taxa de juros por período (decimal, ex: 0.02 para 2%)
 * @param {number} nper Número de períodos (meses)
 * @param {number} pv Valor Presente (Empréstimo)
 * @returns {number} Valor da parcela
 */
function calculatePMT(rate, nper, pv) {
    if (rate === 0) return pv / nper;
    return pv * (rate / (1 - Math.pow(1 + rate, -nper)));
}

/**
 * Calcula o Valor Presente (PV) / Valor do Empréstimo
 * @param {number} rate Taxa de juros por período (decimal)
 * @param {number} nper Número de períodos (meses)
 * @param {number} pmt Valor da parcela
 * @returns {number} Valor Presente
 */
function calculatePV(rate, nper, pmt) {
    if (rate === 0) return pmt * nper;
    return pmt * ((1 - Math.pow(1 + rate, -nper)) / rate);
}

function calculateDiscountFactorSum(rate, daysArray) {
    return daysArray.reduce((sum, days) => {
        return sum + Math.pow(1 + rate, -(days / 30));
    }, 0);
}

function calculatePMTFromDays(rate, daysArray, pv) {
    if (!Array.isArray(daysArray) || daysArray.length === 0) return 0;
    if (rate === 0) return pv / daysArray.length;

    const factorSum = calculateDiscountFactorSum(rate, daysArray);
    return factorSum > 0 ? pv / factorSum : 0;
}

function calculatePVFromDays(rate, daysArray, pmt) {
    if (!Array.isArray(daysArray) || daysArray.length === 0) return 0;
    if (rate === 0) return pmt * daysArray.length;

    return pmt * calculateDiscountFactorSum(rate, daysArray);
}

// Chutes alternativos tentados quando o chute principal não converge.
// Cobrem do crédito barato (~0.5% a.m.) ao cheque especial (~15% a.m.).
const RATE_FALLBACK_GUESSES = [0.005, 0.01, 0.02, 0.05, 0.1, 0.15, 0.20, 0.50];

function _newtonRaphsonRate(guess, evaluator) {
    const maxIter = 100;
    const tol = 1e-7;
    let r = guess;

    for (let i = 0; i < maxIter; i++) {
        const result = evaluator(r);
        if (result === null) return null;

        const { f, df } = result;
        if (Math.abs(df) < tol) return null;

        const rNext = r - f / df;

        if (!Number.isFinite(rNext) || rNext <= -0.999999) return null;
        if (Math.abs(rNext - r) < tol) return rNext;

        r = rNext;
    }

    return null;
}

function _tryRateWithFallback(preferredGuess, evaluator) {
    const tried = new Set();
    const queue = [preferredGuess, ...RATE_FALLBACK_GUESSES];

    // r=0 é sempre raiz da equação f(r)=PV*r - PMT*(1-(1+r)^-n), mas é
    // espúria (limite). Newton-Raphson com chute pequeno costuma cair nela.
    // Descartamos resultados < 0.0001% a.m. para ficar com a raiz real.
    const MIN_VALID_RATE = 1e-6;

    for (const guess of queue) {
        if (!Number.isFinite(guess)) continue;
        const key = guess.toFixed(6);
        if (tried.has(key)) continue;
        tried.add(key);

        const result = _newtonRaphsonRate(guess, evaluator);
        if (result !== null && result > MIN_VALID_RATE) return result;
    }

    return null;
}

/**
 * Calcula a Taxa de Juros (RATE) usando Newton-Raphson com fallback.
 * Resolve a equação: PV * r - PMT * (1 - (1+r)^-n) = 0
 * @param {number} nper Número de períodos (meses)
 * @param {number} pmt Valor da parcela
 * @param {number} pv Valor Presente (Empréstimo)
 * @param {number} guess Chute inicial preferencial (default 0.1)
 * @returns {number|null} Taxa positiva (decimal) ou null se não convergir
 */
function calculateRATE(nper, pmt, pv, guess = 0.1) {
    if (!(nper > 0) || !(pv > 0) || !(pmt > 0)) return null;
    // Combinação impossível: parcelas pagam menos que o principal.
    if (pmt * nper <= pv) return null;

    return _tryRateWithFallback(guess, (r) => {
        const f = pv * r - pmt * (1 - Math.pow(1 + r, -nper));
        const df = pv - pmt * nper * Math.pow(1 + r, -nper - 1);
        return { f, df };
    });
}

function calculateRATEFromDays(daysArray, pmt, pv, guess = 0.1) {
    if (!Array.isArray(daysArray) || daysArray.length === 0) return null;
    if (!(pv > 0) || !(pmt > 0)) return null;
    // Combinação impossível: soma das parcelas <= principal.
    if (pmt * daysArray.length <= pv) return null;

    return _tryRateWithFallback(guess, (r) => {
        const base = 1 + r;
        if (base <= 0) return null;

        let factorSum = 0;
        let derivativeSum = 0;
        for (const days of daysArray) {
            const exponent = days / 30;
            factorSum += Math.pow(base, -exponent);
            derivativeSum += -exponent * Math.pow(base, -exponent - 1);
        }

        const f = pmt * factorSum - pv;
        const df = pmt * derivativeSum;
        return { f, df };
    });
}

const financeMathApi = {
    calculatePMT,
    calculatePV,
    calculateRATE,
    calculateDiscountFactorSum,
    calculatePMTFromDays,
    calculatePVFromDays,
    calculateRATEFromDays,
};

const financeMathGlobalScope =
    typeof globalThis !== 'undefined'
        ? globalThis
        : (typeof window !== 'undefined'
            ? window
            : (typeof self !== 'undefined' ? self : null));

if (financeMathGlobalScope) {
    financeMathGlobalScope.FinanceMath = Object.assign(
        {},
        financeMathGlobalScope.FinanceMath || {},
        financeMathApi,
    );

    // Mantem compatibilidade com chamadas legadas via funções globais.
    Object.keys(financeMathApi).forEach((key) => {
        if (typeof financeMathGlobalScope[key] !== 'function') {
            financeMathGlobalScope[key] = financeMathApi[key];
        }
    });
}

// Export para uso em testes (Node.js) se module for definido
if (typeof module !== 'undefined' && module.exports) {
    module.exports = financeMathApi;
}
