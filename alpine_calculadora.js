document.addEventListener('alpine:init', () => {
    Alpine.data('calculadora', () => ({
        params: {
            cedenteId: '',
            taxa: 5.00,
            dataOperacao: new Date().toISOString().split('T')[0],
            tipoPagamento: 'direto',
            incorreIOF: 'Sim',
            cobrarIOF: 'Sim',
            notas: ''
        },
        titulos: [
            { valorOriginal: '', dataVencimento: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], sacadoId: '', tipo: 'duplicata' }
        ],
        resultados: null,
        isCalculating: false,
        isRegistering: false,
        feedback: { message: '', isError: false },
        
        // Encontro de contas (Mantido o estado da compensação)
        compensacao: {
            ativa: false,
            valorTotal: 0,
            creditoCliente:document.addEventListener('alpine:init', () => {
 s:    Alpine.data('calculadora', () => ({
       ra        params: {
            cedenteId: '
        arquivosDescricao: '',
        isUploa            dataOperac        chartBase64: '',
        myFluxoChart: null,

        init()            incorreIOF: 'Sim',
    va            cobrarIOF: 'Sim',e             notas: ''
                },
        titulos: hamado automa            { val-m        ],
        resultados: null,
        isCalculating: false,
        isRegistering: false,
        feedback: { message: '', isError: false },
        
        // Encontro de co          ris        isCalculating: f,         isRegistering: false/ Removemos o watcher genérico para evitar loops. Os eventos no HTML já chamam updateCalc       s(        compensacao: {
            ativa: false,
            valen            ativa: fa              valorTotal:  |            creditoClient s:    Alpine.data('"calculadora"', () => ({
       ra        params: {
             ra        params: {
            cedenteId:,             cedenteId: ''        arquivosDescricao: ).        isUploa            dataOperac          myFluxoChart: null,

        init()            incorreIOF: 'ur
        init()           ixe    va            cobrarIOF: '"Sim"',e       57                },
        titulos: hamado automa            { val-m     yt        titulos: n         resultados: null,
        isCalculating: false,
  ur        isCalculating: f        isRegistering: fa tota        feedback: { message:et        
        // Encontro de co          ris        isal       .s            ativa: false,
            valen            ativa: fa              valorTotal:  |            creditoClient s:    Alpine.data('"calculadora"', () => ({
       ra        params: {
    length > 0 ? t            valen       .l       ra        params: {
             ra        params: {
            cedenteId:,             cedenteId: ''        arquivosDescricao: ).               ra        pata            cedenteId:,          
        init()            incorreIOF: ur