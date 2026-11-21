// Construye candidatos de la 053 desde la página de inicio renderizada en local (base.html).
// Uso: node construir.mjs <base.html> <salida.html> <variante: base|c1|...>
import fs from 'node:fs';

const [, , entrada, salida, variante] = process.argv;
let h = fs.readFileSync(entrada, 'utf8');

// Recursos del sitio → rutas del repo servido en la raíz (/public/...).
h = h.replace(/http:\/\/localhost:8000\/(css|js|images)\/([^"?]+)(\?v=\d+)?/g, '/public/$1/$2');
// Íconos del navegador y enlaces de navegación: no se miden.
h = h.replace(/<link[^>]+rel="(icon|apple-touch-icon|shortcut icon)"[^>]*>\s*/g, '');
h = h.replace(/http:\/\/localhost:8000/g, '#');

const WA = 'https://wa.me/527530000000?text=Hola%2C%20quiero%20informaci%C3%B3n%20para%20agendar%20una%20cita.';
// Burbuja de conversación dibujada con trazo, como los demás íconos del sitio (no el logotipo de WhatsApp).
const burbuja = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4.5 19.5l1.2-3.6A8 8 0 1 1 8.4 18.5z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M9 10.5h6M9 13.5h4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
// Logotipo de WhatsApp de una tinta (Simple Icons, CC0; normas de la marca: el símbolo sin alterar, en un color).
const logoWA = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>';
const boton = (extra, icono = burbuja) => `<a class="c-btn c-btn--secondary pub-wa${extra}" href="${WA}" target="_blank" rel="noopener noreferrer" aria-label="Escríbenos por WhatsApp (se abre WhatsApp en una pestaña nueva)">${icono}Escríbenos por WhatsApp</a>`;

const variantes = {
  base: { css: '', hero: (s) => s, visita: (s) => s },
  c1: {
    css: `
/* 053 · c1: «Escríbenos por WhatsApp» como botón secundario junto al teléfono (primera pantalla y contacto). */
.pub-hero__facts a.pub-wa { font-size: var(--text-publico-small); }
.pub-hero__facts a.pub-wa:hover { text-decoration: none; }
.pub-visit__acciones { display: flex; flex-wrap: wrap; gap: var(--space-4); }
`,
    // Primera pantalla: un elemento más en la lista del horario y el teléfono, justo después del teléfono.
    hero: (s) => s.replace(/(<li><a href="tel:\+527530000000">753 000 0000<\/a><\/li>)/, `$1\n                <li>${boton('')}</li>`),
    // Contacto: después del teléfono, el botón y «Cómo llegar» en una misma fila.
    visita: (s) => s.replace(/(<a class="pub-visit__tel"[^>]*>753 000 0000<\/a>\s*)<div>/, `$1<div class="pub-visit__acciones">\n                    ${boton('')}`),
  },
  c2: {
    css: `
/* 053 · c2 (corrige c1, evaluador): el teléfono y WhatsApp como un solo grupo de contacto en su propia fila,
   con el horario como dato aparte encima; en contacto, los dos botones forman un grupo que se reparte el ancho
   (iguales al apilarse); por debajo de 560 px, botones a todo el ancho como las acciones del hero. */
.pub-hero__facts > li.pub-hero__contacto { display: flex; flex-basis: 100%; flex-wrap: wrap; align-items: center; gap: var(--space-2) var(--space-6); }
.pub-hero__facts a.pub-wa { font-size: var(--text-publico-small); }
.pub-hero__facts a.pub-wa:hover { text-decoration: none; }
.pub-visit__acciones { display: flex; flex-wrap: wrap; gap: var(--space-4); margin-top: var(--space-4); }
.pub-visit__acciones .c-btn { flex: 1 1 auto; margin-top: 0; }
@media (max-width: 559px) {
    .pub-hero__contacto .pub-wa,
    .pub-visit__acciones .c-btn { width: 100%; padding: 0 var(--space-6); }
}
`,
    hero: (s) => s.replace(/<li>(<a href="tel:\+527530000000">753 000 0000<\/a>)<\/li>/, `<li class="pub-hero__contacto">$1${boton('')}</li>`),
    visita: (s) => s.replace(/(<a class="pub-visit__tel"[^>]*>753 000 0000<\/a>\s*)<div>/, `$1<div class="pub-visit__acciones">\n                    ${boton('')}`),
  },
};
// c3: c2 con el logotipo de WhatsApp en lugar de la burbuja (decisión del usuario, 2026-11-20).
variantes.c3 = {
  css: variantes.c2.css.replace('053 · c2 (corrige c1, evaluador)', '053 · c3 (c2 con el logotipo de WhatsApp de una tinta; decisión del usuario) · c2'),
  hero: (s) => s.replace(/<li>(<a href="tel:\+527530000000">753 000 0000<\/a>)<\/li>/, `<li class="pub-hero__contacto">$1${boton('', logoWA)}</li>`),
  visita: (s) => s.replace(/(<a class="pub-visit__tel"[^>]*>753 000 0000<\/a>\s*)<div>/, `$1<div class="pub-visit__acciones">
                    ${boton('', logoWA)}`),
};
const v = variantes[variante];
if (!v) { console.error('variante desconocida'); process.exit(2); }
const antes = h;
h = v.visita(v.hero(h));
if (variante !== 'base' && (h.match(/pub-wa/g) || []).length < 2) { console.error('no se insertaron los dos botones'); process.exit(1); }
if (v.css) h = h.replace('</head>', `<style id="cambios-053">${v.css}</style>\n</head>`);
h = h.replace('<head>', `<head>\n<!-- Candidato ${variante} de /design-spec 053: la página de inicio renderizada en local (main, a2cd958) con el CSS y el JS reales de public/; lo nuevo va en <style id="cambios-053">. Se mide servido por HTTP con la raíz en el repo. -->`);
fs.writeFileSync(salida, h);
console.log(salida, antes === h ? '(sin cambios)' : 'ok');
