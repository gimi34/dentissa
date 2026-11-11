/*
 * «Descargar» de los reportes (spec 047, CA8, D7): abre el diálogo de impresión del navegador,
 * donde el reporte se guarda como PDF o se imprime. Se carga con defer. El botón nace oculto en
 * el HTML: sin este archivo no aparece y la página se imprime o se guarda igual desde el
 * navegador. Sin dependencias ni HTML construido desde cadenas.
 */
const descargar = document.getElementById('reporte-imprimir');

if (descargar) {
    descargar.hidden = false;
    descargar.addEventListener('click', () => window.print());
}
