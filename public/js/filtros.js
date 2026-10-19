/*
 * Filtro plegable de la lista de citas (spec 034, D6). Se carga en <head> sin defer: marca <html> con
 * filtros-js antes de pintar, para que movil.css pliegue el panel solo cuando el script existe (sin JS,
 * el filtro queda desplegado). El modo lo decide movil.css; sin dependencias ni HTML construido
 * desde cadenas.
 */
document.documentElement.classList.add('filtros-js');

document.addEventListener('DOMContentLoaded', () => {
    const filtro = document.querySelector('.filtro-citas');
    if (!filtro) return;
    const boton = filtro.querySelector('.filtro-btn');
    if (!boton) return;

    // «Filtros» despliega y repliega el panel; el foco se queda en el botón.
    boton.addEventListener('click', () => {
        const abierto = filtro.classList.toggle('abierto');
        boton.setAttribute('aria-expanded', abierto ? 'true' : 'false');
    });
});
