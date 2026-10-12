/*
 * Menú del móvil (spec 033, opción A). Se carga en <head> sin defer: marca <html> con menu-js antes
 * de pintar, para que movil.css oculte el panel solo cuando el script existe (sin JS, el menú queda
 * en el flujo). Sin dependencias ni HTML construido desde cadenas.
 */
document.documentElement.classList.add('menu-js');

document.addEventListener('DOMContentLoaded', () => {
    const html = document.documentElement;
    const boton = document.querySelector('.menu-btn');
    const cerrarBoton = document.querySelector('.menu-cerrar');
    const velo = document.querySelector('.menu-velo');
    const panel = document.getElementById('menu-principal');
    const principal = document.querySelector('main');
    if (!boton || !panel || !principal) return;

    // El modo móvil lo decide movil.css: el botón solo se muestra por debajo del punto de corte.
    const esMovil = () => getComputedStyle(boton).display !== 'none';
    const abierto = () => html.classList.contains('menu-abierto');

    const abrir = () => {
        html.classList.add('menu-abierto');
        boton.setAttribute('aria-expanded', 'true');
        if (velo) velo.hidden = false;
        principal.inert = true;
        const destino = panel.querySelector('a[aria-current="page"]') || panel.querySelector('nav a');
        // El navegador no acepta el foco hasta que pinta un fotograma con el panel visible (T030):
        // ni en el mismo instante ni en el fotograma siguiente.
        if (destino) requestAnimationFrame(() => requestAnimationFrame(() => destino.focus()));
    };

    const cerrar = (devolverFoco = true) => {
        html.classList.remove('menu-abierto');
        boton.setAttribute('aria-expanded', 'false');
        if (velo) velo.hidden = true;
        principal.inert = false;
        if (devolverFoco) boton.focus();
    };

    boton.addEventListener('click', () => (abierto() ? cerrar() : abrir()));
    if (cerrarBoton) cerrarBoton.addEventListener('click', () => cerrar());
    if (velo) velo.addEventListener('click', () => cerrar());

    document.addEventListener('keydown', (evento) => {
        if (evento.key === 'Escape' && abierto()) {
            evento.preventDefault();
            cerrar();
        }
    });

    // Al pasar al escritorio con el menú abierto, se cierra sin mover el foco.
    window.addEventListener('resize', () => {
        if (abierto() && !esMovil()) cerrar(false);
    });
});
