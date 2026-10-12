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
    let focoAplazado = 0;
    // Reintenta en los fotogramas siguientes mientras el foco no quede en el destino (como máximo n).
    const enfocar = (destino, n) => {
        focoAplazado = requestAnimationFrame(() => {
            if (!abierto()) return;
            destino.focus();
            if (document.activeElement !== destino && n > 1) enfocar(destino, n - 1);
        });
    };

    const abrir = () => {
        html.classList.add('menu-abierto');
        boton.setAttribute('aria-expanded', 'true');
        if (velo) velo.hidden = false;
        principal.inert = true;
        const destino = panel.querySelector('a[aria-current="page"]') || panel.querySelector('nav a');
        // El navegador no siempre acepta el foco en los primeros fotogramas con el panel visible
        // (T030, T071): enfocar() lo reintenta; cerrar() cancela el intento pendiente (R4).
        if (destino) enfocar(destino, 10);
    };

    const cerrar = (devolverFoco = true) => {
        cancelAnimationFrame(focoAplazado);
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

    // Al volver con «Atrás», la página puede salir de la caché del historial con el menú abierto (R5).
    window.addEventListener('pageshow', (evento) => {
        if (evento.persisted && abierto()) cerrar(false);
    });

    // Al cruzar el punto de corte (girar la pantalla, cambiar el zoom), el menú se cierra sin mover
    // el foco y, si este queda en un elemento que deja de verse, pasa a uno visible (R3). El navegador
    // puede haberlo pasado ya a <body> al ocultarlo: por eso se recuerda el último elemento con foco.
    let ultimoFoco = null;
    document.addEventListener('focusin', (evento) => { ultimoFoco = evento.target; });
    let modoMovil = esMovil();
    window.addEventListener('resize', () => {
        if (esMovil() === modoMovil) return;
        modoMovil = !modoMovil;
        if (abierto()) cerrar(false);
        const activo = document.activeElement;
        const perdido = !activo || activo === document.body || !activo.getClientRects().length;
        if (modoMovil && panel.contains(perdido ? ultimoFoco : activo)) {
            boton.focus();
        } else if (!modoMovil && perdido && (ultimoFoco === boton || ultimoFoco === cerrarBoton)) {
            const actual = panel.querySelector('a[aria-current="page"]') || panel.querySelector('nav a');
            if (actual) actual.focus();
        }
    });
});
