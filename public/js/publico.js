/*
 * Script común de las páginas públicas (spec 038, D16). Esqueleto de T077: la función que prueba
 * tests/js/publico.test.mjs existe y lanza «sin implementar»; T081 la implementa.
 */
(function (global) {
    'use strict';

    function estadoAhora() {
        throw new Error('sin implementar');
    }

    global.DentissaPublico = { estadoAhora: estadoAhora };
})(globalThis);
