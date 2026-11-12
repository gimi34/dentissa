---
id: 045
slug: terminos-privacidad
status: draft
created: 2026-11-12
extends: [002, 038]
---

# 045 · Términos de uso y aviso de privacidad

## Problema
Para crear una cuenta, el registro (el normal y el que se completa tras entrar con Google) obliga a
aceptar «los términos de uso y la política de privacidad», pero los dos enlaces no llevan a ninguna
parte: quien se registra acepta algo que no puede leer. El sitio tampoco explica en ningún sitio qué
datos guarda la clínica (entre ellos, datos de salud), para qué, con quién los comparte ni cómo
pedir que se corrijan o se borren, que es lo que pide el marco de protección de datos que sigue el
proyecto (LFPDPPP). Quien quiera consultarlo después de registrarse tampoco tiene dónde.

## Historias de usuario
- Como persona que se registra, quiero leer los términos de uso y el aviso de privacidad antes de aceptarlos, sin perder lo que ya escribí en el registro.
- Como visitante o paciente, quiero saber qué datos guarda la clínica, para qué, con quién los comparte y cómo pedir verlos, corregirlos o borrarlos.
- Como visitante o paciente, quiero encontrar esas páginas desde cualquier página pública, no solo al registrarme.
- Como clínica, quiero que el sitio explique sus reglas de uso y cómo trata los datos personales, para cumplir el marco de protección de datos que sigue el proyecto.

## Criterios de aceptación
- [ ] CA1 · Dado cualquiera, con o sin sesión, cuando abre «Términos de uso», entonces ve una página en español con el estilo de las páginas públicas, el título «Términos de uso», la fecha de la última actualización y estas secciones: qué es el sitio y quién lo ofrece (la clínica, con su dirección y su teléfono); la cuenta (es personal, con datos verdaderos, y su titular cuida su contraseña); las citas en línea (se piden y se gestionan con las reglas de la agenda de la clínica, y el sitio no sirve para urgencias: para eso, el teléfono de la clínica); lo que no se permite (usar una cuenta ajena, intentar ver datos de otras personas o entorpecer el servicio) y qué puede hacer la clínica si pasa (desactivar la cuenta); que la información del sitio no sustituye la consulta; cómo se avisan los cambios de los términos, y el contacto.
- [ ] CA2 · Dado cualquiera, con o sin sesión, cuando abre «Aviso de privacidad», entonces ve una página en español con el estilo de las páginas públicas, el título «Aviso de privacidad», la fecha de la última actualización y estas secciones: quién es el responsable de los datos (la clínica, con su domicilio); qué datos se recaban (los de identificación y contacto del registro, los que da Google al registrarse con él, y los de salud que el personal anota en la atención y en el expediente, señalados como datos sensibles; las fotos de la galería, solo con el consentimiento del paciente); para qué se usan (dar la atención, llevar la agenda y el expediente, y los avisos de la cuenta, como recuperar la contraseña) y que no se usan para publicidad; con quién se comparten (los proveedores que hacen funcionar el servicio, como el alojamiento, el inicio de sesión con Google y el envío de correos, y las autoridades cuando la ley lo exige); cuánto tiempo se conservan (el expediente, al menos cinco años); cómo ejercer los derechos de acceso, rectificación, cancelación y oposición y revocar el consentimiento, por qué medios y en qué plazo se responde; qué cookies usa el sitio (solo las necesarias para la sesión, sin rastreo ni publicidad), y cómo se avisan los cambios del aviso.
- [ ] CA3 · Dado el aviso de privacidad, cuando se compara con el registro, entonces cada dato que pide el registro (en el normal y en el que se completa con Google) aparece en la lista de datos que se recaban, y el aviso no menciona datos que el sitio no pide ni usos que no tiene.
- [ ] CA4 · Dado alguien en el registro (el normal o el que se completa tras entrar con Google), cuando lee la casilla de aceptación, entonces dice «Acepto los términos de uso y el aviso de privacidad de Dentissa», y «términos de uso» y «aviso de privacidad» son enlaces a sus páginas, que se abren en otra pestaña y lo anuncian, también a los lectores de pantalla, con «(se abre en otra pestaña)»; al volver a la pestaña del registro, lo escrito sigue ahí.
- [ ] CA5 · Dado alguien que envía el registro (normal o con Google) sin marcar la casilla, cuando se rechaza el envío, entonces el mensaje es «Debes aceptar los términos de uso y el aviso de privacidad.» y no se crea ninguna cuenta (hoy el registro con Google ya lo rechaza; el normal solo lo impide el navegador).
- [ ] CA6 · Dado cualquiera en una página pública (inicio, servicios, galería y las dos páginas de esta spec), cuando llega al pie, entonces encuentra «Términos de uso» y «Aviso de privacidad», que llevan a sus páginas en la misma pestaña, a 320, 390 y 1440 px de ancho.
- [ ] CA7 · Dado cualquiera en «Términos de uso» o «Aviso de privacidad», cuando quiere salir, entonces tiene la cabecera y el pie de las páginas públicas (con sesión, «Ir a mi panel»; sin ella, «Iniciar sesión» y «Crear cuenta»), y con sesión la página se muestra igual, sin llevarlo a su panel.
- [ ] CA8 · Dado cualquiera con JavaScript desactivado, cuando abre cualquiera de las dos páginas, entonces lee el texto completo y todos sus enlaces funcionan.

## Fuera de alcance
- La revisión del texto por un abogado: es un texto base para un proyecto académico con datos ficticios, no asesoría legal.
- Guardar qué versión de los términos y del aviso aceptó cada cuenta y cuándo, y pedir que se acepten de nuevo cuando cambian.
- Un formulario en línea para ejercer los derechos de acceso, rectificación, cancelación y oposición: el aviso explica cómo pedirlos.
- El consentimiento expreso y por escrito para los datos de salud: se sigue recabando en la clínica, como hoy.
- Un aviso o banner de cookies: el sitio no usa cookies de rastreo ni publicidad.
- El aviso de privacidad simplificado o corto, y las versiones en otros idiomas.
- Cambiar el diseño del registro más allá de los enlaces y el texto de la casilla y de su mensaje de error.
- El `sitemap.xml`, que sigue pendiente en `docs/seo.md`.

## Seguridad y privacidad
- Datos sensibles involucrados: ninguno nuevo. Las dos páginas son texto fijo para todos: no muestran datos de ninguna cuenta ni de pacientes, y no tienen campos ni formularios.
- Quién puede hacer qué: cualquiera, con o sin sesión, ve las dos páginas. Ningún permiso cambia; registrarse sigue exigiendo aceptar la casilla.
- Casos de abuso: no aplica. Las páginas no reciben datos, no dependen de quién las abre y sus enlaces van a páginas fijas del propio sitio.

## Auditoría
No aplica: las páginas no tocan datos sensibles, autenticación ni permisos, y aceptar la casilla sigue
siendo parte del alta. El alta de cuentas aún no se audita (pendiente en `docs/observability.md`, para la spec 021): es
código previo que esta spec no cambia.

## Diseño
Páginas para **leer**: quien llega quiere encontrar rápido la parte que le importa (qué datos
guardan, cómo borrarlos) y leerla con calma. Deben sentirse parte del sitio público de la clínica
(misma cabecera, pie y tipografía), sobrias y claras, sin letra pequeña ni jerga legal innecesaria.
Sistema: [docs/design/system.md](../../design/system.md) (1.14.0, superficie «Sitio público»).
Diseño aceptado: [design.md](design.md) (`/design-spec 045`, candidato `c4`, 82/100).

- **Qué debe lograr cada pantalla:** en «Aviso de privacidad», entender en cinco segundos que hay
  una lista de secciones y llegar a la que busca; en «Términos de uso», lo mismo con sus secciones.
  En el registro, que los enlaces se vean como enlaces y quede claro que abren otra pestaña.
- **Recursos de marca que existen:** la cabecera, el pie, las fuentes y los colores de las páginas
  públicas; no hacen falta fotos ni ilustraciones.
- **Qué debe diseñarse:** las dos páginas (con su lista de secciones al principio y la fecha de
  actualización), su estado sin JavaScript y con sesión, los dos enlaces nuevos del pie, y los
  enlaces de la casilla del registro con su aviso de pestaña nueva.

## Criterios de diseño
Modo: leer · umbral 80 · mínimo por punto 3 · presupuesto 5 candidatos (`.ai/project.yaml →
design`). Se miden con `design/medir.mjs` (que llama a `evaluate.mjs`) sobre las dos páginas a 320, 390 y
1440 px ([design/criterios.json](design/criterios.json)).

| ID | Qué se mide | Objetivo | Sale de |
|---|---|---|---|
| K1 | El título y el primer párrafo en la primera pantalla | visibles enteros a 320, 390 y 1440 px | modo leer |
| K2 | Las secciones de cada página | los títulos de sección de CA1 y CA2, en ese orden, y la fecha de actualización | CA1, CA2 |
| K3 | Lista de secciones al principio | un enlace por sección, antes de la primera (comprobación manual: `evaluate.mjs` no lo mide) | Diseño |
| K4 | Largo de línea del texto | ≤ 80 caracteres a 1440 px (comprobación manual y test de CSS) | modo leer |
| K5 | Enlaces del pie | «Términos de uso» y «Aviso de privacidad» a 320, 390 y 1440 px | CA6 |
| K6 | Estabilidad visual (CLS) | ≤ 0.1 | modo leer |
| K7 | JavaScript de la ruta | ≤ 100 kB | P10 |

Puertas: las de `design-spec/references/criterios.md` §2 (CA7 y CA8 los cubren `G-sin-js`,
`G-desborde`, `G-toque` y `G-foco`; CA3, CA4 y CA5 los prueban sus tests). Rúbrica: pesos por defecto.

## Requisitos no funcionales
- Accesibilidad: WCAG 2.1 AA (P9): un solo título principal por página y títulos en orden, contraste
  ≥ 4.5:1 en el texto, enlaces reconocibles sin depender solo del color, y foco visible.
- Descubribilidad (P10): las dos páginas son indexables, cada una con su título («Términos de uso ·
  Dentissa», «Aviso de privacidad · Dentissa»), su descripción y su dirección canónica propias; el
  texto llega completo en el HTML del servidor. `docs/seo.md` las añade a su inventario.
- Peso: como las demás páginas públicas, sin imágenes nuevas y sin JavaScript propio nuevo.

## Preguntas abiertas
- Ninguna.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-08 | brecha | Enlaces «términos de uso» y «política de privacidad» del registro que llevan a `#` | Crear páginas sencillas, en esta spec | usuario (spec 044, Decisiones) |
| 2026-11-12 | brecha | ¿De dónde sale el texto de las dos páginas? | Lo redacta Claude con la estructura del aviso de privacidad integral de la LFPDPPP y unos términos de uso sencillos, con los datos de la clínica; el usuario lo revisa al aceptar el diseño (`/design-spec 045`). No es asesoría legal | usuario |
| 2026-11-12 | brecha | ¿Cómo se abren los enlaces desde el registro? | En otra pestaña, anunciándolo; lo escrito en el registro no se pierde | usuario |
| 2026-11-12 | brecha | ¿Desde dónde más se llega a las páginas? | También desde el pie de todas las páginas públicas | usuario |
| 2026-11-12 | implícita | ¿«Política de privacidad» o «aviso de privacidad»? | «Aviso de privacidad», el nombre que usa la LFPDPPP; cambian el texto de la casilla y el mensaje de error | `docs/constitution.md` → Restricciones (marco LFPDPPP) |
| 2026-11-12 | implícita | ¿Cuánto se conserva el expediente? | Al menos cinco años | `docs/constitution.md` → Restricciones (NOM-004-SSA3) |
| 2026-11-12 | supuesto | ¿Por qué medios se ejercen los derechos y se revoca el consentimiento? | En la clínica y por su teléfono (el sitio no publica un correo de la clínica); el texto lo concreta y el usuario lo revisa | — |
| 2026-11-12 | supuesto | ¿Se indexan las dos páginas? | Sí, con título, descripción y canónica propios: son públicas y dan confianza | `docs/seo.md` (reglas del sitio público) |
| 2026-11-12 | supuesto | ¿Qué cookies se declaran? | Solo las necesarias para la sesión; el sitio no tiene analítica ni publicidad | revisión de las plantillas públicas (sin scripts de terceros) |
| 2026-11-12 | diseño | Diseño de las dos páginas | Aceptado `c4` (82/100) en [design.md](design.md): la composición de `/servicios` con «En pocas palabras» y secciones numeradas; propone al sistema un respaldo de Newsreader con sus medidas (1.15.0). K3 y K4 se comprueban a mano | `/design-spec 045` |

## Notas para /plan
- Direcciones sugeridas: `/terminos` y `/aviso-de-privacidad`, con `layouts/publico` (cabecera y pie),
  indexables (sin `noindex`).
- La casilla está en `resources/views/auth/register.blade.php` y `auth/registro-contacto.blade.php`
  (hoy con `href="#"` y `style` en línea); el mensaje, en `RegisterController` (`terminos.accepted`,
  dos sitios). Pestaña nueva con `target="_blank"` y `rel="noopener"`.
- Los datos de la clínica (dirección, teléfono, horario) salen de `config('clinica.contacto')`, como en
  las demás páginas públicas.
- Los enlaces del pie van en `layouts/publico.blade.php` (`pub-foot__links`).
- Fecha de actualización: un valor fijo por página, que cambia cuando cambia el texto.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-11-12 | Creada en `draft` y aprobada por el usuario | /specify 045 |
| 2026-11-12 | Diseño aprobado por el usuario (`c4`, 82/100) con el texto de las dos páginas | /design-spec 045 |
| 2026-11-12 | CA5 sin «como hoy» para el registro normal, y «Auditoría» dice que el alta aún no se audita; vuelve a `draft` hasta que el usuario la apruebe | /analyze 045 r1 A9, A13 |
