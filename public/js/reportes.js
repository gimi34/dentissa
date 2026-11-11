/*
 * «Imprimir» de los reportes (spec 047, CA8, D7). Se carga con defer. El botón nace oculto en el
 * HTML: sin este archivo no aparece y la página se imprime igual desde el navegador. Sin
 * dependencias ni HTML construido desde cadenas.
 */
const imprimir = document.getElementById('reporte-imprimir');

if (imprimir) {
    imprimir.hidden = false;
    imprimir.addEventListener('click', () => window.print());
}
