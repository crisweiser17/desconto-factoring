const assert = require('assert');
const { calculatePMT, calculatePV, calculateRATE } = require('./financeMath.js');

// Test PMT
let pmt = calculatePMT(0.0177, 10, 10000);
assert.ok(Math.abs(pmt - 1099.91) < 0.01, 'PMT calculation failed');

// Test PV
let pv = calculatePV(0.02, 12, 500);
assert.ok(Math.abs(pv - 5287.67) < 0.01, 'PV calculation failed');

// Test RATE
let rate = calculateRATE(10, 1100, 10000, 0.05);
assert.ok(Math.abs(rate - 0.0177) < 0.0001, 'RATE calculation failed');

console.log('Todos os testes de matemática financeira passaram com sucesso!');
