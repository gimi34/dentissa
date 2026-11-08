/*
 * c1 · Carrusel de trabajos (spec 039). Mejora progresiva: sin JavaScript la fila se desplaza a mano y
 * «Anterior» y «Siguiente» son enlaces a las fotos. Con JavaScript, los botones mueven la ventana una foto
 * (sin animación con movimiento reducido), apuntan siempre a la vecina y la región de estado anuncia la
 * foto con textContent. Sin avance automático y sin dependencias.
 */
(function () {
    'use strict';
    var reducir = window.matchMedia('(prefers-reduced-motion: reduce)');
    [].forEach.call(document.querySelectorAll('[data-carrusel]'), function (c) {
        var ventana = c.querySelector('.pub-carrusel__ventana');
        var items = [].slice.call(c.querySelectorAll('.pub-carrusel__lista > li'));
        var ant = c.querySelector('[data-anterior]');
        var sig = c.querySelector('[data-siguiente]');
        var estado = c.querySelector('[data-estado]');
        if (!ventana || items.length < 2 || !ant || !sig) return;
        var actual = 0;
        function vecina(d) { return (actual + d + items.length) % items.length; }
        function marcar(i) {
            actual = i;
            items.forEach(function (li, k) { li.classList.toggle('is-actual', k === i); });
            ant.setAttribute('href', '#' + items[vecina(-1)].id);
            sig.setAttribute('href', '#' + items[vecina(1)].id);
        }
        function ir(i) {
            ventana.scrollTo({ left: items[i].offsetLeft - items[0].offsetLeft, behavior: reducir.matches ? 'auto' : 'smooth' });
            marcar(i);
            if (estado) estado.textContent = items[i].querySelector('figcaption').textContent.replace(/\s+/g, ' ').trim();
        }
        ant.addEventListener('click', function (e) { e.preventDefault(); ir(vecina(-1)); });
        sig.addEventListener('click', function (e) { e.preventDefault(); ir(vecina(1)); });
        var espera;
        ventana.addEventListener('scroll', function () {
            clearTimeout(espera);
            espera = setTimeout(function () {
                var x = ventana.scrollLeft, mejor = 0, d = Infinity;
                items.forEach(function (li, k) {
                    var dk = Math.abs(li.offsetLeft - items[0].offsetLeft - x);
                    if (dk < d) { d = dk; mejor = k; }
                });
                marcar(mejor);
            }, 120);
        }, { passive: true });
        marcar(0);
    });
})();
