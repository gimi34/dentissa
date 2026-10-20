---
id: 038
slug: pagina-inicio
status: draft
created: 2026-10-20
extends: [001, 031]
---

# 038 · Página de inicio pública con galería

## Problema
Quien llega a la dirección principal de Dentissa sin sesión no ve nada de la clínica: hoy se le
manda directo al inicio de sesión (001 CA4). Un colaborador subió a la rama principal, fuera del
flujo, una página de inicio que no cumple las reglas del proyecto: usa una paleta y unos estilos
ajenos al sistema de diseño que se cargan de otro sitio (la política de seguridad de la 031 los
bloquea y la página se vería sin estilos), muestra una dirección y un teléfono que no son
ficticios y no coinciden con el mapa, lista servicios escritos a mano que no son los del catálogo
y tiene una galería con recuadros vacíos. Los pacientes potenciales necesitan una presentación
fiable de la clínica que los lleve a crear su cuenta, y la clínica, poder mostrar fotos reales de
sus instalaciones sin tocar código.

## Historias de usuario
- Como visitante sin cuenta, quiero conocer la clínica, sus servicios y cómo contactarla para
  decidir si me registro y agendo una cita.
- Como paciente o miembro del personal con sesión, quiero que la dirección principal me siga
  llevando a mi panel para no dar un paso de más.
- Como administrador, quiero subir, ordenar y quitar fotos de la clínica para que la página de
  inicio muestre las instalaciones actuales.
- Como persona que busca una clínica dental en un buscador, quiero encontrar la página de inicio
  con un título y una descripción que expliquen qué es Dentissa.

## Criterios de aceptación
### Página de inicio
- [ ] CA1 · Dado un visitante sin sesión, cuando abre `/`, entonces ve la página de inicio de la
  clínica (sin redirección) con presentación, servicios, ubicación y contacto, y enlaces visibles a
  «Iniciar sesión» y a crear una cuenta (reemplaza 001 CA4).
- [ ] CA2 · Dado un usuario con sesión de cualquier rol, cuando abre `/`, entonces se le lleva a su
  panel, como hasta ahora.
- [ ] CA3 · Dado un catálogo con servicios activos e inactivos, cuando un visitante abre la página,
  entonces ve el nombre y la descripción de cada servicio activo, sin precio, y ningún servicio
  inactivo; si no hay ningún servicio activo, la sección de servicios no aparece.
- [ ] CA4 · Dado un visitante, cuando mira la sección de contacto, entonces ve exactamente la
  dirección «Av. Insurgentes 450, Col. Centro, Lázaro Cárdenas, Mich., C.P. 60950», el teléfono
  «753 000 0000» (que en un móvil se marca con un toque) y el horario «Lunes a viernes 9:00–20:00 ·
  Sábados 9:00–14:00», un mapa de Google incrustado en la sección de ubicación que marca esa misma
  dirección, una nota visible junto al mapa que dice que lo sirve Google, y un enlace «Cómo
  llegar» que abre esa dirección en un mapa aparte.
- [ ] CA5 · Dado un visitante, cuando abre la página, entonces el navegador solo pide recursos a
  los sitios que ya usa el resto de la aplicación y, para el mapa, a Google Maps, y no aparece
  ningún recurso bloqueado por la política de seguridad. El resto de las pantallas siguen sin poder
  mostrar contenido de otros sitios (amplía 031 CA1 solo en la página de inicio).
- [ ] CA6 · Dado un visitante con JavaScript desactivado, cuando abre la página, entonces todo el
  contenido (textos, servicios, galería, dirección, teléfono, horario y enlace «Cómo llegar») es
  visible y todos los enlaces funcionan; solo el mapa incrustado puede no mostrarse.
- [ ] CA7 · Dado un visitante en una pantalla de 320 a 1440 px de ancho, cuando recorre la página
  con el dedo, el ratón o solo el teclado, entonces no hay desplazamiento horizontal, cada destino
  de toque mide al menos 44 px, el foco se ve en todo elemento interactivo, el menú se abre y se
  cierra con el teclado, y si el visitante pidió reducir el movimiento no hay animaciones.

### Galería
- [ ] CA8 · Dado un administrador, cuando sube una foto en JPG, PNG o WebP de hasta 5 MB con su
  descripción, entonces la foto aparece en la galería de la página de inicio, con esa descripción
  como texto alternativo, en la última posición.
- [ ] CA9 · Dado un administrador con varias fotos subidas, cuando cambia su orden o quita una,
  entonces la página de inicio muestra las fotos en el nuevo orden y sin la quitada en la
  siguiente visita, y la foto quitada deja de poder abrirse por su dirección.
- [ ] CA10 · Dada una galería sin fotos, cuando un visitante abre la página de inicio, entonces la
  sección de galería no aparece (ni recuadros vacíos ni marcadores de «Foto»).
- [ ] CA11 · Dado un administrador, cuando intenta subir una foto sin descripción, entonces se
  rechaza con el mensaje «Escribe una descripción de la foto.» y no se publica nada.

### Descubribilidad
- [ ] CA12 · Dado un buscador o un visitante, cuando pide `/`, entonces la respuesta trae en el
  HTML del servidor el título «Dentissa · Tu sonrisa, nuestra prioridad», la descripción «Clínica
  dental Dentissa en Lázaro Cárdenas: conoce nuestros servicios, crea tu cuenta y agenda tu cita
  en línea.», una sola dirección canónica absoluta a la raíz del dominio de producción y ninguna
  indicación de no indexar; título y descripción son distintos de los de `/registro`.

### Abuso
- [ ] CA13 (abuso) · Dado un administrador, cuando sube un archivo que no es una imagen aunque
  termine en `.jpg`, una imagen de otro formato, una de más de 5 MB o una cuando ya hay 12 fotos,
  entonces se rechaza con un mensaje que dice el motivo y no se publica ni se guarda nada.
- [ ] CA14 (abuso) · Dado un dentista, un asistente, un paciente o un visitante sin sesión, cuando
  intenta abrir la gestión de la galería o enviar directamente una subida, un cambio de orden o una
  baja, entonces se le niega (sin sesión, al inicio de sesión) y la galería no cambia.
- [ ] CA15 (abuso) · Dada una foto con su ubicación u otros datos incrustados por la cámara,
  cuando se publica, entonces la versión que descarga un visitante no conserva esos datos.
- [ ] CA16 (abuso) · Dada una descripción con etiquetas o código, cuando la página la muestra,
  entonces aparece como texto literal y no se ejecuta nada.

## Fuera de alcance
- `sitemap.xml` y `robots.txt`: siguen en el objetivo 7, que tendrá que incluir `/`.
- Las demás brechas de `docs/seo.md` (vista previa al compartir, íconos del sitio, datos
  estructurados de la clínica, página 404 propia y `noindex` de las rutas no indexables): siguen
  donde están.
- Precios en la página de inicio, agendar sin cuenta y cualquier formulario en la página de inicio.
- Aviso o consentimiento de cookies para el mapa de Google: basta con la nota de CA4.
- Editar desde la administración los textos de la página de inicio (presentación, contacto y
  horario se cambian en el código).
- Álbumes, vídeos, recortar o editar fotos en el navegador y textos largos por foto.
- Retirar `welcome.blade.php` (código muerto del esqueleto).

## Seguridad y privacidad
- Datos sensibles involucrados: ninguno de los de `docs/security.md`. Los servicios activos ya son
  información pública de la clínica. Las fotos de la galería son de las instalaciones y se
  publican para todo internet. El mapa incrustado hace que Google reciba la visita (dirección IP
  y navegador) de quien llega a esa sección. Por eso se avisa junto al mapa (CA4) y la excepción
  a la política de contenido se limita a Google Maps y a la página de inicio (CA5).
- Quién puede hacer qué: cualquiera ve la página de inicio; solo el administrador sube, ordena y
  quita fotos; los demás roles y los visitantes no pueden gestionar la galería.
- Casos de abuso:
  - Como usuario malintencionado con cuenta de administrador o con su sesión robada, intento subir
    un archivo ejecutable o enorme disfrazado de imagen, o llenar el servidor → se rechaza (CA13).
  - Como paciente, dentista o asistente, intento gestionar la galería o enviar la petición a mano
    → se niega (CA14).
  - Como visitante, intento sacar la ubicación de la clínica o del fotógrafo de los datos
    incrustados en las fotos → no los hay (CA15).
  - Como administrador descuidado o malintencionado, escribo código en la descripción para que se
    ejecute en la página pública → se muestra como texto (CA16).

## Auditoría
- Las fotos de la galería no son datos sensibles: subirlas, ordenarlas o quitarlas no exige
  registro de auditoría.
- El intento denegado de gestionar la galería (CA14) es un `access_denied`. Su registro formal
  queda para la spec 021, igual que en las specs 026, 028 y 030 (excepción P7).

## Diseño
Rediseño de la página de inicio sobre el sistema de diseño de Dentissa
([docs/design/system.md](../../design/system.md)). `/design-spec 038` genera 2–3 opciones antes de
`/plan`.

- **Qué debe transmitir:** cercanía, limpieza y confianza de una clínica real, con la misma
  identidad de marca que la aplicación. No debe parecerse al panel interno.
- **Qué se diseña:**
  - la página de inicio en escritorio y en móvil: cabecera con logo y navegación, presentación
    con llamada a crear la cuenta, servicios, galería, ubicación y contacto, y pie;
  - el menú del móvil;
  - el estado sin servicios y el estado sin fotos;
  - la pantalla de gestión de la galería del administrador: lista con orden, subir con
    descripción, quitar con confirmación y mensajes de error.
- **Contenido:** se conservan los textos de la página que subió el colaborador
  (`landing/index.blade.php`, rama `feat/landing-page-completa`, `3c8202b`) salvo los datos de
  contacto, que se sustituyen por los de CA4.

## Requisitos no funcionales
- Accesibilidad: WCAG 2.1 AA (P9), con contraste ≥ 4.5:1 en texto y ≥ 3:1 en bordes de control y
  foco, un solo título principal y títulos en orden, y textos alternativos útiles. Las imágenes
  decorativas no las anuncia el lector de pantalla.
- Descubribilidad (P10): `/` pasa a ser indexable junto con `/registro`, con Lighthouse SEO ≥ 90 y
  como mucho 100 kB de JavaScript transferido; el contenido principal llega en el HTML del servidor.
- Peso: la imagen principal de la presentación pesa como mucho 300 kB y la página de inicio sin
  galería, como mucho 1 MB transferido en la primera visita.
- Rendimiento de la galería: cada foto publicada pesa como mucho 500 kB y las que quedan fuera de
  la pantalla no se descargan hasta acercarse a ellas.
- Rendimiento del mapa: no se descarga hasta que el visitante se acerca a la sección de ubicación,
  así que no cuenta en la primera carga (ni en el presupuesto de JavaScript de P10).

## Preguntas abiertas
Ninguna.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-20 | contradicción | `docs/seo.md` y el objetivo 7 indexan solo `/registro`; la página de inicio es la cara pública | `/` es indexable; se actualiza `docs/seo.md` y el objetivo 7 deberá incluirla | usuario |
| 2026-10-20 | contradicción | La página del colaborador usa su propia paleta y estilos cargados de otro sitio; P9 exige el sistema de diseño | Rehacerla con el sistema de diseño, conservando secciones y textos; sin estilos de otros sitios | usuario · `docs/constitution.md` P9 |
| 2026-10-20 | brecha | Dirección y mapa no coinciden y el teléfono parece real; la constitución exige datos ficticios | Datos ficticios de CA4 con enlace «Cómo llegar» | usuario · `docs/constitution.md` Restricciones |
| 2026-10-20 | contradicción | Mapa incrustado frente a la política de contenido de la 031 (solo recursos propios y de fuentes) | Mapa de Google incrustado: excepción solo para Google Maps y solo en la página de inicio, con nota de que lo sirve Google; amplía 031 CA1 (reemplaza la decisión anterior de no incrustarlo) | usuario · `docs/specs/031-endurecer-produccion/spec.md` CA1 |
| 2026-10-20 | brecha | Servicios escritos a mano y galería vacía | Servicios activos del catálogo; galería que gestiona el administrador | usuario |
| 2026-10-20 | brecha | ¿Dividir en página de inicio y galería (16 criterios, dos capacidades)? | Una sola spec (asumido el coste de más rondas de `/plan` y `/analyze`) | usuario |
| 2026-10-20 | implícita | Con sesión, `/` lleva al panel del rol | Se mantiene | `routes/web.php` (comportamiento actual) |
| 2026-10-20 | implícita | Registro formal del acceso denegado | Spec 021, excepción P7 como en 026/028/030 | `docs/specs/028-reprogramar-citas/spec.md` → Auditoría |
| 2026-10-20 | supuesto | Qué datos de cada servicio se publican | Nombre y descripción, sin precio ni duración | razonable: los precios cambian y se confirman en la cita |
| 2026-10-20 | supuesto | Límites de la galería | JPG, PNG o WebP, ≤ 5 MB por archivo subido, máximo 12 fotos | razonable para una clínica y el disco del servidor |
| 2026-10-20 | supuesto | Pesos de la página y de las fotos publicadas | Imagen principal ≤ 300 kB, página sin galería ≤ 1 MB, foto publicada ≤ 500 kB | razonable (la imagen actual pesa 1,2 MB) |
| 2026-10-20 | supuesto | Título y descripción de `/` | Título de la página del colaborador; descripción de CA12 (100 caracteres) | razonable; se confirma al aprobar |
| 2026-10-20 | supuesto | Fotos donde se reconozca a pacientes | La gestión recuerda no subirlas; no se comprueba automáticamente | razonable: datos de salud (`docs/security.md`) |

## Notas para /plan
- Punto de partida: `resources/views/landing/index.blade.php` de `origin/feat/landing-page-completa`
  (`3c8202b`, 363 líneas). En `main` está la versión anterior (merge `f096e17`). Usa Tailwind por
  CDN, `onclick` en línea, un `iframe` de Google Maps y secciones con `opacity: 0` hasta que actúa
  el JavaScript (rompe CA6). Las imágenes son `public/images/cover-bg.jpg` (1,2 MB),
  `logo-icon.png`, `logo-icon1.png`, `logo-main.png` y `logo-rosa.png` (424 kB). Hay que revisar
  cuáles se usan.
- CSP de `docker/nginx/seguridad/comunes.conf` (spec 031, ya en `main`): hoy `default-src 'self'`
  bloquea el `iframe`. Hay que añadir `frame-src https://www.google.com` (o el origen exacto del
  embed) **solo** en la respuesta de `/` (CA5); el resto de rutas no cambia. El `iframe` va con
  `loading="lazy"`, un `title` accesible y `referrerpolicy` coherente con la cabecera de la 031.
  Su URL debe apuntar a la dirección ficticia de CA4 (la del colaborador apunta a otra). Si la
  031 se libera antes, la 038 parte de su CSP. Si se libera después, hay que conciliar las dos
  ramas.
- `tests/Feature/ProxyHttpsTest.php` comprueba la redirección a https con `/`: hay que pasarla a
  otra ruta que redirija al login. Hoy son los 2 fallos que quedan en `main` (ficha
  `docs/changes/2026-10-20-fix-marcadores-conflicto-main.md`). El merge ya adaptó `ExampleTest`.
- Las fotos subidas no van al repositorio. En producción necesitan un volumen persistente
  (`docker-compose.prod.yml`, ruta protegida) y hay que decidir si entran en las copias
  (`deploy/respaldar.sh` solo copia la base) y en `verificar.sh`.
- Quitar los metadatos (CA15) y limitar el peso (≤ 500 kB) exige volver a codificar la imagen al
  publicarla. Hay que preferir lo que ya trae la imagen de PHP antes que una dependencia nueva.
- Rutas de la gestión dentro del grupo `admin` (AGENTS.md), con Gate según ADR 0003.
- Actualizar `docs/seo.md` (fila de `/`, reglas del sitio) y el criterio del objetivo 7 en
  `docs/roadmap.md`. El `noindex` de las rutas no indexables sigue en el objetivo 7.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-10-20 | Mapa de Google incrustado en la ubicación: CA4, CA5 y CA6, seguridad, rendimiento; `extends` 031 | Petición del usuario antes de aprobar |
| 2026-10-20 | Spec creada en `draft` | La página de inicio llegó a `main` fuera del flujo (merge `f096e17`); el usuario pidió regularizarla y añadir la galería |
