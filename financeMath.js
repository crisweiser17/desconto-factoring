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

/**
 * Calcula a Taxa de Juros (RATE) usando o método de Newton-Raphson
 * Resolve a equação: PV * r - PMT * (1 - (1+r)^-n) = 0
 * @param {number} nper Número de períodos (meses)
 * @param {number} pmt Valor da parcela
 * @param {number} pv Valor Presente (Empréstimo)
 * @param {number} guess Chute inicial para a taxa (default 0.1)
 * @returns {number|null} Taxa de juros (decimal) ou null se não convergir
 */
function calculateRATE(nper, pmt, pv, guess = 0.1) {
    const maxIter = 100;
    const tol = 1e-7;
    let r = guess;

    for (let i = 0; i < maxIter; i++) {
        // Função f(r) = PV * r - PMT * (1 - (1+r)^-n)
        const f = pv * r - pmt * (1 - Math.pow(1 + r, -nper));
        
        // Derivada f'(r) = PV - PMT * n * (1+r)^(-n-1)
        const df = pv - pmt * nper * Math.pow(1 + r, -nper - 1);
        
        const r_next = r - f / df;
        
        if (Math.abs(r_next - r) < tol) {
            return r_next;
        }
        r = r_next;
    }
    
    return null; // Não convergiu
}

function calculateRATEFromDays(daysArray, pmt, pv, guess = 0.1) {
    if (!Array.isArray(daysArray) || daysArray.length === 0) return null;

    const maxIter = 100;
    const tol = 1e-7;
    let r = guess;

    for (let i = 0; i < maxIter; i++) {
        let factorSum = 0;
        let derivativeSum = 0;

        for (const days of daysArray) {
            const exponent = days / 30;
            const base = 1 + r;

            if (base <= 0) return null;

            factorSum += Math.pow(base, -exponent);
            derivativeSum += -exponent * Math.pow(base, -exponent - 1);
        }

        const f = pmt * factorSum - pv;
        const df = pmt * derivativeSum;

        if (Math.abs(df) < tol) {
            return null;
        }

        const rNext = r - (f / df);

        if (!Number.isFinite(rNext) || rNext <= -0.999999) {
            return null;
        }

        if (Math.abs(rNext - r) < tol) {
            return rNext;
        }

        r = rNext;
    }

    return null;
}

// Export para uso em testes (Node.js) se module for definido
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        calculatePMT,
        calculatePV,
        calculateRATE,
        calculateDiscountFactorSum,
        calculatePMTFromDays,
        calculatePVFromDays,
        calculateRATEFromDays,
    };
}
