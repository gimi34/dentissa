---
id: 038
slug: pagina-inicio
status: draft
created: 2026-10-20
extends: [001, 031]
---

# 038 · Página de inicio pública con galería de trabajos

## Problema
Quien llega a la dirección principal de Dentissa sin sesión no ve nada de la clínica: hoy se le
manda directo al inicio de sesión (001 CA4). Un colaborador subió a la rama principal, fuera del
flujo, una página de inicio que no cumple las reglas del proyecto: usa una paleta y unos estilos
ajenos al sistema de diseño que se cargan de otro sitio (la política de seguridad de la 031 los
bloquea y la página se vería sin estilos), muestra una dirección y un teléfono que no son
ficticios y no coinciden con el mapa, lista servicios escritos a mano que no son los del catálogo
y tiene una galería con recuadros vacíos. Los pacientes potenciales necesitan una presentación
breve y fiable de la clínica que los lleve a crear su cuenta: quiénes somos, qué atendemos, qué
trabajos hacemos y dónde estamos. La clínica necesita poder mostrar fotos de sus trabajos sin tocar
código y sin exponer a sus pacientes.

## Historias de usuario
- Como visitante sin cuenta, quiero conocer en una sola página breve quiénes son, qué atienden,
  algunos de sus trabajos y cómo contactarlos, para decidir si me registro y agendo una cita.
- Como visitante interesado, quiero ver todos los servicios y todas las fotos de trabajos en sus
  propias páginas, sin saturar la página de inicio.
- Como paciente o miembro del personal con sesión, quiero que la dirección principal me siga
  llevando a mi panel para no dar un paso de más.
- Como administrador, quiero subir, ordenar y quitar fotos de trabajos, dejando constancia del
  consentimiento cuando aparece un paciente, para mostrar el trabajo de la clínica sin exponer a
  nadie.
- Como persona que busca una clínica dental en un buscador, quiero encontrar la página de inicio,
  los servicios y la galería con títulos y descripciones que expliquen qué es Dentissa.

## Criterios de aceptación
### Página de inicio
- [ ] CA1 · Dado un visitante sin sesión, cuando abre `/`, entonces ve la página de inicio de la
  clínica (sin redirección) en este orden: la presentación con el logo, «Agendar mi primera cita»
  (lleva al registro) y el horario y el teléfono; «Sobre nosotros»; el avance de servicios; el
  carrusel de trabajos; ubicación y contacto. Ve además enlaces visibles a «Iniciar sesión» y a
  crear una cuenta (reemplaza 001 CA4).
- [ ] CA2 · Dado un usuario con sesión de cualquier rol, cuando abre `/`, `/servicios` o `/galeria`,
  entonces ve la página pedida (son públicas); solo `/` lo lleva a su panel, como hasta ahora.
- [ ] CA3 · Dado un visitante, cuando mira «Sobre nosotros», entonces lee exactamente este texto:
  «Somos una consulta de odontología integral en el centro de Lázaro Cárdenas. Atendemos a toda la
  familia, desde la primera revisión de los niños hasta la ortodoncia y las prótesis de los
  adultos, en un mismo lugar y con el mismo equipo.» y «Antes de cada tratamiento te explicamos qué
  vamos a hacer, cuánto dura y qué opciones tienes, para que decidas con calma. Y para que no
  tengas que llamar en horario de trabajo, puedes crear tu cuenta y agendar tus citas en línea.»
- [ ] CA4 · Dado un catálogo con servicios activos e inactivos, cuando un visitante abre `/`,
  entonces el avance de servicios muestra solo los nombres de las categorías que tienen algún
  servicio activo, sin servicios sueltos ni precios, y un enlace «Ver todos los servicios» a
  `/servicios`; si no hay ningún servicio activo, el avance no aparece.
- [ ] CA5 · Dado un visitante, cuando mira la sección de contacto, entonces ve exactamente la
  dirección «Av. Insurgentes 450, Col. Centro, Lázaro Cárdenas, Mich., C.P. 60950», el teléfono
  «753 000 0000» (que en un móvil se marca con un toque) y el horario «Lunes a viernes 9:00–20:00 ·
  Sábados 9:00–14:00», un mapa de Google incrustado que marca esa misma dirección, una nota visible
  junto al mapa que dice que lo sirve Google, y un enlace «Cómo llegar» que abre esa dirección en un
  mapa aparte.

### Servicios y galería públicas
- [ ] CA6 · Dado un catálogo con servicios activos e inactivos, cuando un visitante abre
  `/servicios`, entonces ve cada servicio activo con su nombre y su descripción, agrupados por
  categoría, sin precio y sin ningún servicio inactivo, y un enlace de vuelta al inicio; si no hay
  ninguno activo, ve «Pronto publicaremos nuestros servicios.».
- [ ] CA7 · Dada una galería con fotos, cuando un visitante abre `/`, entonces ve un carrusel con
  las fotos en el orden fijado por el administrador, cada una con su descripción visible, botones
  «Anterior» y «Siguiente» que se usan con el teclado, sin avance automático, y un enlace «Ver toda
  la galería» a `/galeria`.
- [ ] CA8 · Dada una galería con fotos, cuando un visitante abre `/galeria`, entonces ve todas las
  fotos publicadas en el mismo orden, cada una con su descripción, y un enlace de vuelta al
  inicio; con la galería vacía ve «Aún no hay fotos de nuestros trabajos.».
- [ ] CA9 · Dada una galería sin fotos, cuando un visitante abre `/`, entonces ni el carrusel ni el
  enlace a la galería aparecen (ni recuadros vacíos ni marcadores de «Foto»).

### Todas las páginas públicas
- [ ] CA10 · Dado un visitante, cuando abre `/`, `/servicios` o `/galeria`, entonces el navegador
  solo pide recursos a los sitios que ya usa el resto de la aplicación y, en `/` y para el mapa, a
  Google Maps, y no aparece ningún recurso bloqueado por la política de seguridad. El resto de las
  pantallas siguen sin poder mostrar contenido de otros sitios (amplía 031 CA1 solo en `/`).
- [ ] CA11 · Dado un visitante con JavaScript desactivado, cuando abre `/`, `/servicios` o
  `/galeria`, entonces todo el contenido (textos, categorías, servicios, todas las fotos del
  carrusel desplazándolo a mano, dirección, teléfono, horario y enlaces) es visible y todos los
  enlaces funcionan; solo el mapa incrustado puede no mostrarse.
- [ ] CA12 · Dado un visitante en una pantalla de 320 a 1440 px de ancho, cuando recorre `/`,
  `/servicios` o `/galeria` con el dedo, el ratón o solo el teclado, entonces no hay desplazamiento
  horizontal de la página, cada destino de toque mide al menos 44 px, el foco se ve en todo
  elemento interactivo, el menú se abre y se cierra con el teclado, y si el visitante pidió reducir
  el movimiento no hay animaciones.

### Gestión de la galería
- [ ] CA13 · Dado un administrador, cuando sube una foto en JPG, PNG o WebP de hasta 5 MB con su
  descripción e indica que no aparece ningún paciente, entonces la foto se publica en el carrusel y
  en `/galeria`, con esa descripción como texto alternativo, en la última posición.
- [ ] CA14 · Dado un administrador, cuando sube una foto e indica que aparece la boca o los dientes
  de un paciente, entonces solo se publica si marca «Tengo el consentimiento escrito del paciente
  para publicar esta foto y no se le puede reconocer»; la gestión muestra junto a esa foto quién
  declaró el consentimiento y cuándo.
- [ ] CA15 · Dado un administrador con varias fotos subidas, cuando cambia su orden o quita una
  (tras confirmarlo), entonces el carrusel y `/galeria` muestran las fotos en el nuevo orden y sin
  la quitada en la siguiente visita, y la foto quitada deja de poder abrirse por su dirección.
- [ ] CA16 · Dado un administrador, cuando intenta subir una foto sin descripción, sin decir si
  aparece un paciente o con paciente y sin marcar el consentimiento, entonces se rechaza con el
  mensaje «Escribe una descripción de la foto.», «Indica si en la foto aparece un paciente.» o
  «Marca que tienes el consentimiento escrito del paciente.», según el caso, y no se publica nada.

### Descubribilidad
- [ ] CA17 · Dado un buscador o un visitante, cuando pide `/`, `/servicios` o `/galeria`, entonces
  cada respuesta trae en el HTML del servidor su título y su descripción (`/`: «Dentissa · Tu
  sonrisa, nuestra prioridad» y «Clínica dental Dentissa en Lázaro Cárdenas: conoce nuestros
  servicios, crea tu cuenta y agenda tu cita en línea.»; `/servicios`: «Servicios · Dentissa» y
  «Todos los tratamientos de la consulta Dentissa en Lázaro Cárdenas, de la revisión general a la
  ortodoncia y las prótesis.»; `/galeria`: «Nuestros trabajos · Dentissa» y «Fotos de tratamientos
  hechos en la consulta Dentissa en Lázaro Cárdenas.»), una sola dirección canónica absoluta a su
  ruta en el dominio de producción y ninguna indicación de no indexar; títulos y descripciones son
  distintos entre sí y de los de `/registro`.

### Abuso
- [ ] CA18 (abuso) · Dado un administrador, cuando sube un archivo que no es una imagen aunque
  termine en `.jpg`, una imagen de otro formato, una de más de 5 MB o una cuando ya hay 12 fotos,
  entonces se rechaza con un mensaje que dice el motivo y no se publica ni se guarda nada.
- [ ] CA19 (abuso) · Dado un dentista, un asistente, un paciente o un visitante sin sesión, cuando
  intenta abrir la gestión de la galería o enviar directamente una subida, un cambio de orden o una
  baja, entonces se le niega (sin sesión, al inicio de sesión) y la galería no cambia.
- [ ] CA20 (abuso) · Dada una foto con su ubicación u otros datos incrustados por la cámara,
  cuando se publica, entonces la versión que descarga un visitante no conserva esos datos.
- [ ] CA21 (abuso) · Dada una descripción con etiquetas o código, cuando una página pública la
  muestra, entonces aparece como texto literal y no se ejecuta nada.

## Fuera de alcance
- `sitemap.xml` y `robots.txt`: siguen en el objetivo 7, que tendrá que incluir `/`, `/servicios`
  y `/galeria`.
- Las demás brechas de `docs/seo.md` (vista previa al compartir, íconos del sitio, datos
  estructurados de la clínica, página 404 propia y `noindex` de las rutas no indexables): siguen
  donde están.
- Precios, agendar sin cuenta y cualquier formulario en las páginas públicas.
- Aviso o consentimiento de cookies para el mapa de Google: basta con la nota de CA5.
- Editar desde la administración los textos públicos (presentación, «Sobre nosotros», contacto y
  horario se cambian en el código) y elegir qué servicios se destacan.
- Carrusel que avanza solo, comparador interactivo de antes y después, álbumes, vídeos, filtros
  por categoría en la galería, recortar o editar fotos en el navegador y textos largos por foto.
- Guardar el documento del consentimiento del paciente: lo conserva la clínica fuera del sistema;
  aquí solo queda la declaración del administrador (CA14).
- Detectar automáticamente si una foto permite reconocer a alguien.
- Presentar al equipo con nombres, fotos o cédulas (los datos del proyecto son ficticios).
- Retirar `welcome.blade.php` (código muerto del esqueleto).

## Seguridad y privacidad
- Datos sensibles involucrados: **salud** (`docs/security.md`) en las fotos de trabajos que muestran
  la boca o los dientes de un paciente. Se publican para todo internet, así que exigen la
  declaración de consentimiento escrito y que no se pueda reconocer al paciente (CA14). Las fotos
  sin pacientes (coronas, prótesis, modelos, laboratorio) no son datos sensibles. Los servicios
  activos ya son información pública de la clínica. El mapa incrustado hace que Google reciba la
  visita (dirección IP y navegador) de quien llega a esa sección. Por eso se avisa junto al mapa
  (CA5) y la excepción a la política de contenido se limita a Google Maps y a `/` (CA10).
- Quién puede hacer qué: cualquiera ve `/`, `/servicios` y `/galeria`; solo el administrador sube,
  ordena y quita fotos; los demás roles y los visitantes no pueden gestionar la galería.
- Casos de abuso:
  - Como usuario malintencionado con cuenta de administrador o con su sesión robada, intento subir
    un archivo ejecutable o enorme disfrazado de imagen, o llenar el servidor → se rechaza (CA18).
  - Como paciente, dentista o asistente, intento gestionar la galería o enviar la petición a mano
    → se niega (CA19).
  - Como administrador con prisa, intento publicar la foto de un paciente sin su consentimiento →
    no se publica sin la declaración, y la declaración queda con mi nombre y la fecha (CA14, CA16).
  - Como visitante, intento sacar la ubicación de la clínica o del fotógrafo de los datos
    incrustados en las fotos → no los hay (CA20).
  - Como administrador descuidado o malintencionado, escribo código en la descripción para que se
    ejecute en una página pública → se muestra como texto (CA21).

## Auditoría
- Publicar una foto con paciente es una escritura de datos de salud (`sensitive_write`). Esta spec
  guarda quién declaró el consentimiento y cuándo (CA14). El registro formal de auditoría (almacén
  solo-anexado, con resultado e identificador de petición) queda para la spec 022, como en la 026 y
  la 028 (excepción P7).
- El intento denegado de gestionar la galería (CA19) es un `access_denied`. Su registro formal
  queda para la spec 021, igual que en las specs 026, 028 y 030 (excepción P7).

## Diseño
Rediseño de la página de inicio sobre el sistema de diseño de Dentissa
([docs/design/system.md](../../design/system.md)). `/design-spec 038` genera las opciones antes de
`/plan`. La ronda 1 eligió como base la opción B («La puerta de la consulta»), y la ronda 2 la
adapta a esta versión de la spec.

- **Qué debe transmitir:** una presentación breve, cercana y fiable de una clínica real, con la
  misma identidad de marca que la aplicación. Debe mostrar el trabajo de la clínica sin saturar la
  página. No debe parecerse al panel interno.
- **Qué se diseña:**
  - la página de inicio en escritorio y en móvil: cabecera con logo y navegación, presentación
    con la placa y el logo (sin foto de la entrada), «Sobre nosotros», avance de servicios por
    categorías, carrusel de trabajos, ubicación y contacto, y pie;
  - las páginas `/servicios` y `/galeria`;
  - el menú del móvil;
  - los estados sin servicios y sin fotos;
  - la gestión de la galería del administrador: subir con descripción, la pregunta del paciente y
    la casilla de consentimiento, ordenar, quitar con confirmación y mensajes de error.
- **Contenido:** se conservan los textos de la página que subió el colaborador
  (`landing/index.blade.php`, rama `feat/landing-page-completa`, `3c8202b`), salvo los datos de
  contacto, que se sustituyen por los de CA5, y se añade «Sobre nosotros» (CA3).

## Requisitos no funcionales
- Accesibilidad: WCAG 2.1 AA (P9), con contraste ≥ 4.5:1 en texto y ≥ 3:1 en bordes de control y
  foco, un solo título principal por página y títulos en orden, y textos alternativos útiles. Las
  imágenes decorativas no las anuncia el lector de pantalla. El carrusel no se mueve solo (WCAG
  2.2.2) y anuncia qué foto se muestra («Foto 2 de 5»).
- Descubribilidad (P10): `/`, `/servicios` y `/galeria` pasan a ser indexables junto con
  `/registro`, con Lighthouse SEO ≥ 90 y como mucho 100 kB de JavaScript transferido por ruta; el
  contenido principal llega en el HTML del servidor.
- Peso: `/` sin fotos, como mucho 1 MB transferido en la primera visita.
- Rendimiento de las fotos: cada foto publicada pesa como mucho 500 kB, y las que quedan fuera de
  la pantalla (en el carrusel y en `/galeria`) no se descargan hasta acercarse a ellas.
- Rendimiento del mapa: no se descarga hasta que el visitante se acerca a la sección de ubicación,
  así que no cuenta en la primera carga (ni en el presupuesto de JavaScript de P10).

## Preguntas abiertas
Ninguna.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-20 | contradicción | `docs/seo.md` y el objetivo 7 indexan solo `/registro`; la página de inicio es la cara pública | `/` es indexable; se actualiza `docs/seo.md` y el objetivo 7 deberá incluirla | usuario |
| 2026-10-20 | contradicción | La página del colaborador usa su propia paleta y estilos cargados de otro sitio; P9 exige el sistema de diseño | Rehacerla con el sistema de diseño, conservando secciones y textos; sin estilos de otros sitios | usuario · `docs/constitution.md` P9 |
| 2026-10-20 | brecha | Dirección y mapa no coinciden y el teléfono parece real; la constitución exige datos ficticios | Datos ficticios de CA5 con enlace «Cómo llegar» | usuario · `docs/constitution.md` Restricciones |
| 2026-10-20 | contradicción | Mapa incrustado frente a la política de contenido de la 031 (solo recursos propios y de fuentes) | Mapa de Google incrustado: excepción solo para Google Maps y solo en `/`, con nota de que lo sirve Google; amplía 031 CA1 | usuario · `docs/specs/031-endurecer-produccion/spec.md` CA1 |
| 2026-10-20 | brecha | Servicios escritos a mano y galería vacía | Servicios del catálogo; galería que gestiona el administrador | usuario |
| 2026-10-20 | brecha | ¿Dividir en página de inicio y galería? | Una sola spec (asumido el coste de más rondas de `/plan` y `/analyze`); sigue así con las páginas `/servicios` y `/galeria` | usuario |
| 2026-10-20 | brecha | Página de presentación saturada (15 servicios y la galería entera en `/`) | `/` muestra un avance: categorías con «Ver todos los servicios» (`/servicios`) y un carrusel con «Ver toda la galería» (`/galeria`) | usuario (`/design-spec 038`, ronda 1) |
| 2026-10-20 | brecha | ¿Qué fotos muestra la galería? | Fotos de trabajos hechos en la consulta, no de las instalaciones ni de la entrada | usuario (`/design-spec 038`, ronda 1) |
| 2026-10-20 | brecha | Las fotos de trabajos pueden mostrar la boca de pacientes (datos de salud) | Se admiten las dos clases: sin pacientes, sin más; con paciente, solo con la declaración de consentimiento escrito y sin que se le pueda reconocer, guardando quién la declaró y cuándo | usuario |
| 2026-10-20 | brecha | ¿Qué muestra el avance de servicios? | Solo los nombres de las categorías con algún servicio activo | usuario |
| 2026-10-20 | brecha | Portada sin la foto de la entrada | Placa con el logo, el título, «Agendar mi primera cita» y el aviso de la puerta | usuario |
| 2026-10-20 | brecha | Texto de «Sobre nosotros» | El de CA3, propuesto sin datos inventados (sin cédulas, años ni premios) | usuario (lo propone Claude, se aprueba con la spec) |
| 2026-10-20 | implícita | Con sesión, `/` lleva al panel del rol | Se mantiene; `/servicios` y `/galeria` se ven igual con o sin sesión | `routes/web.php` (comportamiento actual) |
| 2026-10-20 | implícita | Registro formal del acceso denegado y de la escritura de salud | Specs 021 y 022, excepción P7 como en 026/028/030 | `docs/specs/028-reprogramar-citas/spec.md` → Auditoría |
| 2026-10-20 | supuesto | Qué datos de cada servicio se publican en `/servicios` | Nombre y descripción, agrupados por categoría, sin precio ni duración | razonable: los precios cambian y se confirman en la cita |
| 2026-10-20 | supuesto | Límites de la galería | JPG, PNG o WebP, ≤ 5 MB por archivo subido, máximo 12 fotos | razonable para una clínica y el disco del servidor |
| 2026-10-20 | supuesto | Pesos de la página y de las fotos publicadas | `/` sin fotos ≤ 1 MB, foto publicada ≤ 500 kB | razonable |
| 2026-10-20 | supuesto | Títulos y descripciones de las tres rutas | Los de CA17 | razonable; se confirman al aprobar |
| 2026-10-20 | supuesto | Comportamiento del carrusel | Una foto principal a la vez con «Anterior», «Siguiente» y «Foto n de N», sin avance automático; sin JavaScript se desplaza a mano | WCAG 2.2.2 (P9) |
| 2026-10-20 | supuesto | ¿Las páginas públicas cambian con sesión? | `/servicios` y `/galeria` se ven igual; la cabecera puede ofrecer «Ir a mi panel» en lugar de «Iniciar sesión» | razonable |

## Notas para /plan
- Punto de partida: `resources/views/landing/index.blade.php` de `origin/feat/landing-page-completa`
  (`3c8202b`, 363 líneas). En `main` está la versión anterior (merge `f096e17`). Usa Tailwind por
  CDN, `onclick` en línea, un `iframe` de Google Maps y secciones con `opacity: 0` hasta que actúa
  el JavaScript (rompe CA11). Las imágenes son `public/images/cover-bg.jpg` (1,2 MB, foto de banco:
  no se usa), `logo-icon.png`, `logo-icon1.png`, `logo-main.png` y `logo-rosa.png` (424 kB). Hay que
  revisar cuáles se usan.
- CSP de `docker/nginx/seguridad/comunes.conf` (spec 031, ya en `main`): hoy `default-src 'self'`
  bloquea el `iframe`. Hay que añadir `frame-src https://www.google.com` (o el origen exacto del
  embed) **solo** en la respuesta de `/` (CA10); el resto de rutas no cambia. El `iframe` va con
  `loading="lazy"`, un `title` accesible y `referrerpolicy` coherente con la cabecera de la 031.
  Su URL debe apuntar a la dirección ficticia de CA5 (la del colaborador apunta a otra). Si la
  031 se libera antes, la 038 parte de su CSP. Si se libera después, hay que conciliar las dos
  ramas.
- `tests/Feature/ProxyHttpsTest.php` comprueba la redirección a https con `/`: hay que pasarla a
  otra ruta que redirija al login. Hoy son los 2 fallos que quedan en `main` (ficha
  `docs/changes/2026-10-20-fix-marcadores-conflicto-main.md`). El merge ya adaptó `ExampleTest`.
- Las fotos subidas no van al repositorio. En producción necesitan un volumen persistente
  (`docker-compose.prod.yml`, ruta protegida) y hay que decidir si entran en las copias
  (`deploy/respaldar.sh` solo copia la base) y en `verificar.sh`.
- Quitar los metadatos (CA20) y limitar el peso (≤ 500 kB) exige volver a codificar la imagen al
  publicarla. Hay que preferir lo que ya trae la imagen de PHP antes que una dependencia nueva.
- La declaración de consentimiento (CA14) es un dato nuevo de la foto: si hay paciente, quién y
  cuándo. Es una migración (datos), dentro del flujo completo.
- El carrusel, sin JavaScript, es una fila con desplazamiento horizontal y `scroll-snap` dentro de su
  propio contenedor (no desborda la página, CA12). Con JavaScript, los botones y «Foto n de N» con
  una región `aria-live="polite"`. Así no depende de una librería.
- Rutas de la gestión dentro del grupo `admin` (AGENTS.md), con Gate según ADR 0003. `/servicios` y
  `/galeria` son públicas, fuera de los grupos por rol.
- Actualizar `docs/seo.md` (filas de `/`, `/servicios` y `/galeria`, reglas del sitio) y el criterio
  del objetivo 7 en `docs/roadmap.md`. El `noindex` de las rutas no indexables sigue en el objetivo 7.
- Clasificar en `docs/security.md` las fotos de trabajos con paciente como datos de salud publicados
  con consentimiento.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-10-20 | Vuelve a `draft`. Fotos de trabajos (no de instalaciones) con declaración de consentimiento si aparece un paciente; carrusel en `/` y páginas `/galeria` y `/servicios`; avance de servicios por categorías; «Sobre nosotros»; portada con placa y logo. Criterios renumerados (21) | Petición del usuario al revisar las opciones de `/design-spec 038`, ronda 1 |
| 2026-10-20 | Spec aprobada (`approved`) con los supuestos de Decisiones | Aprobación del usuario |
| 2026-10-20 | Mapa de Google incrustado en la ubicación: CA4, CA5 y CA6, seguridad, rendimiento; `extends` 031 | Petición del usuario antes de aprobar |
| 2026-10-20 | Spec creada en `draft` | La página de inicio llegó a `main` fuera del flujo (merge `f096e17`); el usuario pidió regularizarla y añadir la galería |
