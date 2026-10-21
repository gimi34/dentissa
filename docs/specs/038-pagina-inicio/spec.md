---
id: 038
slug: pagina-inicio
status: approved
created: 2026-10-20
extends: [001, 031]
---

# 038 · Página de inicio pública

## Problema
Quien llega a la dirección principal de Dentissa sin sesión no ve nada de la clínica: hoy se le
manda directo al inicio de sesión (001 CA4). Un colaborador subió a la rama principal, fuera del
flujo, una página de inicio que no cumple las reglas del proyecto: usa una paleta y unos estilos
ajenos al sistema de diseño que se cargan de otro sitio (la política de seguridad de la 031 los
bloquea y la página se vería sin estilos), muestra una dirección y un teléfono que no son
ficticios y no coinciden con el mapa y lista servicios escritos a mano que no son los del catálogo.
Los pacientes potenciales necesitan una presentación breve y fiable de la clínica que los lleve a
crear su cuenta: quiénes somos, qué atendemos y dónde estamos. La galería de trabajos (039) y las
promociones (040) se añaden a esta página en sus propias specs.

## Historias de usuario
- Como visitante sin cuenta, quiero conocer en una sola página breve quiénes son, qué atienden y
  cómo contactarlos, para decidir si me registro y agendo una cita.
- Como visitante interesado, quiero ver todos los servicios en su propia página, sin saturar la
  página de inicio.
- Como paciente o miembro del personal con sesión, quiero que la dirección principal me siga
  llevando a mi panel para no dar un paso de más.
- Como persona que busca una clínica dental en un buscador, quiero encontrar la página de inicio y
  los servicios con títulos y descripciones que expliquen qué es Dentissa.

## Criterios de aceptación
### Página de inicio
- [x] CA1 · Dado un visitante sin sesión, cuando abre `/`, entonces ve la página de inicio de la
  clínica (sin redirección) en este orden: la presentación con el logo, «Agendar mi primera cita»
  (lleva al registro) y el horario y el teléfono; «Sobre nosotros»; el avance de servicios;
  ubicación y contacto. Ve además enlaces visibles a «Iniciar sesión» y a crear una cuenta
  (reemplaza 001 CA4).
- [x] CA2 · Dado un usuario con sesión de cualquier rol, cuando abre `/`, entonces se le lleva a su
  panel, como hasta ahora; cuando abre `/servicios`, la ve igual que un visitante.
- [x] CA3 · Dado un visitante, cuando mira «Sobre nosotros», entonces lee exactamente este texto:
  «Somos una consulta de odontología integral en el centro de Lázaro Cárdenas. Atendemos a toda la
  familia, desde la primera revisión de los niños hasta la ortodoncia y las prótesis de los
  adultos, en un mismo lugar y con el mismo equipo.» y «Antes de cada tratamiento te explicamos qué
  vamos a hacer, cuánto dura y qué opciones tienes, para que decidas con calma. Y para que no
  tengas que llamar en horario de trabajo, puedes crear tu cuenta y agendar tus citas en línea.»
- [x] CA4 · Dado un catálogo con servicios activos e inactivos, cuando un visitante abre `/`,
  entonces el avance de servicios muestra solo los nombres de las categorías que tienen algún
  servicio activo, sin servicios sueltos ni precios, y un enlace «Ver todos los servicios» a
  `/servicios`; si no hay ningún servicio activo, el avance no aparece.
- [x] CA5 · Dado un visitante, cuando mira la sección de contacto, entonces ve exactamente la
  dirección «Av. Insurgentes 450, Col. Centro, Lázaro Cárdenas, Mich., C.P. 60950», el teléfono
  «753 000 0000» (que en un móvil se marca con un toque) y el horario «Lunes a viernes 9:00–20:00 ·
  Sábados 9:00–14:00», un mapa de Google incrustado que marca esa misma dirección, una nota visible
  junto al mapa que dice que lo sirve Google, y un enlace «Cómo llegar» que abre esa dirección en un
  mapa aparte.

### Servicios
- [x] CA6 · Dado un catálogo con servicios activos e inactivos, cuando un visitante abre
  `/servicios`, entonces ve cada servicio activo con su nombre y su descripción, agrupados por
  categoría, sin precio y sin ningún servicio inactivo, y un enlace de vuelta al inicio; si no hay
  ninguno activo, ve «Pronto publicaremos nuestros servicios.».

### Páginas públicas
- [x] CA7 · Dado un visitante, cuando abre `/` o `/servicios`, entonces el navegador solo pide
  recursos a los sitios que ya usa el resto de la aplicación y, en `/` y para el mapa, a Google
  Maps, y no aparece ningún recurso bloqueado por la política de seguridad. El resto de las
  pantallas siguen sin poder mostrar contenido de otros sitios (amplía 031 CA1 solo en `/`).
- [x] CA8 · Dado un visitante con JavaScript desactivado, cuando abre `/` o `/servicios`, entonces
  todo el contenido (textos, categorías, servicios, dirección, teléfono, horario y enlaces) es
  visible y todos los enlaces funcionan; solo el mapa incrustado puede no mostrarse.
- [x] CA9 · Dado un visitante en una pantalla de 320 a 1440 px de ancho, cuando recorre `/` o
  `/servicios` con el dedo, el ratón o solo el teclado, entonces no hay desplazamiento horizontal,
  cada destino de toque mide al menos 44 px, el foco se ve en todo elemento interactivo, el menú se
  abre y se cierra con el teclado, y si el visitante pidió reducir el movimiento no hay animaciones.

### Descubribilidad
- [x] CA10 · Dado un buscador o un visitante, cuando pide `/` o `/servicios`, entonces cada
  respuesta trae en el HTML del servidor su título y su descripción (`/`: «Dentissa · Tu sonrisa,
  nuestra prioridad» y «Clínica dental Dentissa en Lázaro Cárdenas: conoce nuestros servicios, crea
  tu cuenta y agenda tu cita en línea.»; `/servicios`: «Servicios · Dentissa» y «Todos los
  tratamientos de la consulta Dentissa en Lázaro Cárdenas, de la revisión general a la ortodoncia y
  las prótesis.»), una sola dirección canónica absoluta a su ruta en el dominio de producción y
  ninguna indicación de no indexar; títulos y descripciones son distintos entre sí y de los de
  `/registro`.

## Fuera de alcance
- La galería de trabajos (carrusel, `/galeria` y su gestión): spec 039.
- Las promociones: spec 040.
- `sitemap.xml` y `robots.txt`: siguen en el objetivo 7, que tendrá que incluir `/` y `/servicios`.
- Las demás brechas de `docs/seo.md` (vista previa al compartir, íconos del sitio, datos
  estructurados de la clínica, página 404 propia y `noindex` de las rutas no indexables): siguen
  donde están.
- Precios en el catálogo público, agendar sin cuenta y cualquier formulario en las páginas públicas.
- Aviso o consentimiento de cookies para el mapa de Google: basta con la nota de CA5.
- Editar desde la administración los textos públicos (presentación, «Sobre nosotros», contacto y
  horario se cambian en el código) y elegir qué servicios se destacan.
- Presentar al equipo con nombres, fotos o cédulas (los datos del proyecto son ficticios).
- Retirar `welcome.blade.php` (código muerto del esqueleto).

## Seguridad y privacidad
- Datos sensibles involucrados: ninguno de los de `docs/security.md`. Los servicios activos ya son
  información pública de la clínica. El mapa incrustado hace que Google reciba la visita (dirección
  IP y navegador) de quien llega a esa sección. Por eso se avisa junto al mapa (CA5) y la excepción
  a la política de contenido se limita a Google Maps y a `/` (CA7).
- Quién puede hacer qué: cualquiera ve `/` y `/servicios`; nadie escribe nada desde ellas.
- Casos de abuso: no aplica. Las páginas no reciben datos del visitante ni muestran datos
  sensibles. Lo único que se publica es el catálogo activo, que el administrador ya gestiona (009).

## Diseño
Rediseño de la página de inicio sobre el sistema de diseño de Dentissa
([docs/design/system.md](../../design/system.md)). `/design-spec 038` genera las opciones antes de
`/plan` y diseña a la vez lo que añaden la 039 y la 040, para que la página se piense entera. La
ronda 1 eligió como base la opción B («La puerta de la consulta») y la ronda 2 la adapta a las
tres specs. La ronda 3 (2026-10-21, motor creativo de AI-DD 1.16.0) rediseña la 038 en la línea de la
landing del colaborador: opción C «La línea de abound» con la ubicación de la opción D. La ronda 2
queda archivada y sigue siendo el diseño de la 039 y la 040 hasta su propio `/design-spec`.
Documento de diseño: [design.md](design.md).

- **Qué debe transmitir:** una presentación breve, cercana y fiable de una clínica real, con la
  misma identidad de marca que la aplicación. No debe parecerse al panel interno.
- **Qué se diseña (038):**
  - la página de inicio en escritorio y en móvil: cabecera con logo y navegación, presentación
    a pantalla completa con la foto, el logo, el horario y el teléfono (ronda 3), «Sobre nosotros»,
    avance de servicios por categorías, ubicación y contacto, y pie;
  - la página `/servicios`;
  - el menú del móvil;
  - el estado sin servicios activos.
- **Contenido:** se conservan los textos de la página que subió el colaborador
  (`landing/index.blade.php`, rama `feat/landing-page-completa`, `3c8202b`), salvo los datos de
  contacto, que se sustituyen por los de CA5, y se añade «Sobre nosotros» (CA3).

## Requisitos no funcionales
- Accesibilidad: WCAG 2.1 AA (P9), con contraste ≥ 4.5:1 en texto y ≥ 3:1 en bordes de control y
  foco, un solo título principal por página y títulos en orden. Las imágenes decorativas no las
  anuncia el lector de pantalla.
- Descubribilidad (P10): `/` y `/servicios` pasan a ser indexables junto con `/registro`, con
  Lighthouse SEO ≥ 90 y como mucho 100 kB de JavaScript transferido por ruta; el contenido principal
  llega en el HTML del servidor.
- Peso: `/`, como mucho 1 MB transferido en la primera visita.
- Rendimiento del mapa: no se descarga hasta que el visitante se acerca a la sección de ubicación,
  así que no cuenta en la primera carga (ni en el presupuesto de JavaScript de P10).

## Preguntas abiertas
Ninguna.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-20 | contradicción | `docs/seo.md` y el objetivo 7 indexan solo `/registro`; la página de inicio es la cara pública | `/` (y `/servicios`) indexables; se actualiza `docs/seo.md` y el objetivo 7 deberá incluirlas | usuario |
| 2026-10-20 | contradicción | La página del colaborador usa su propia paleta y estilos cargados de otro sitio; P9 exige el sistema de diseño | Rehacerla con el sistema de diseño, conservando secciones y textos; sin estilos de otros sitios | usuario · `docs/constitution.md` P9 |
| 2026-10-20 | brecha | Dirección y mapa no coinciden y el teléfono parece real; la constitución exige datos ficticios | Datos ficticios de CA5 con enlace «Cómo llegar» | usuario · `docs/constitution.md` Restricciones |
| 2026-10-20 | contradicción | Mapa incrustado frente a la política de contenido de la 031 (solo recursos propios y de fuentes) | Mapa de Google incrustado: excepción solo para Google Maps y solo en `/`, con nota de que lo sirve Google; amplía 031 CA1 | usuario · `docs/specs/031-endurecer-produccion/spec.md` CA1 |
| 2026-10-20 | brecha | Página de presentación saturada (15 servicios en `/`) | `/` muestra solo las categorías con «Ver todos los servicios» (`/servicios`) | usuario (`/design-spec 038`, ronda 1) |
| 2026-10-20 | brecha | Portada sin la foto de la entrada | Placa con el logo, el título, «Agendar mi primera cita» y el aviso de la puerta | usuario |
| 2026-10-20 | brecha | Texto de «Sobre nosotros» | El de CA3, propuesto sin datos inventados (sin cédulas, años ni premios) | usuario (lo propone Claude, se aprueba con la spec) |
| 2026-10-20 | brecha | Tamaño: con galería de trabajos y promociones la spec rondaba 26 criterios y tres capacidades | Dividir en 038 (esta), 039 galería de trabajos y 040 promociones; el diseño de las tres se hace junto en `/design-spec 038` | usuario |
| 2026-10-20 | diseño | Diseño de la página | Opción B «La puerta de la consulta», ronda 2: [design.md](design.md) | usuario (`/design-spec 038`) |
| 2026-10-20 | implícita | Con sesión, `/` lleva al panel del rol | Se mantiene; `/servicios` se ve igual con o sin sesión | `routes/web.php` (comportamiento actual) |
| 2026-10-20 | supuesto | Qué datos de cada servicio se publican en `/servicios` | Nombre y descripción, agrupados por categoría, sin precio ni duración | razonable: los precios cambian y se confirman en la cita |
| 2026-10-20 | supuesto | Títulos y descripciones de las dos rutas | Los de CA10 | razonable; se confirman al aprobar |
| 2026-10-20 | supuesto | Cabecera con sesión en `/servicios` | Ofrece «Ir a mi panel» en lugar de «Iniciar sesión» | razonable |
| 2026-10-21 | diseño | Rediseño tras la aprobación: la opción B es «muy básica» | Ronda 3 de `/design-spec` en la línea de la landing del colaborador: opción C con la ubicación de D (`design.md`); los criterios no cambian | usuario |

## Notas para /plan
- Punto de partida: `resources/views/landing/index.blade.php` de `origin/feat/landing-page-completa`
  (`3c8202b`, 363 líneas). En `main` está la versión anterior (merge `f096e17`). Usa Tailwind por
  CDN, `onclick` en línea, un `iframe` de Google Maps y secciones con `opacity: 0` hasta que actúa
  el JavaScript (rompe CA8). Las imágenes son `public/images/cover-bg.jpg` (1,2 MB, foto de banco:
  no se usa), `logo-icon.png`, `logo-icon1.png`, `logo-main.png` y `logo-rosa.png` (424 kB). Hay que
  revisar cuáles se usan y retirar las que sobren.
- CSP de `docker/nginx/seguridad/comunes.conf` (spec 031, ya en `main`): hoy `default-src 'self'`
  bloquea el `iframe`. Hay que añadir `frame-src https://www.google.com` (o el origen exacto del
  embed) **solo** en la respuesta de `/` (CA7); el resto de rutas no cambia. El `iframe` va con
  `loading="lazy"`, un `title` accesible y `referrerpolicy` coherente con la cabecera de la 031.
  Su URL debe apuntar a la dirección ficticia de CA5 (la del colaborador apunta a otra). Si la
  031 se libera antes, la 038 parte de su CSP. Si se libera después, hay que conciliar las dos
  ramas.
- `tests/Feature/ProxyHttpsTest.php` comprueba la redirección a https con `/`: hay que pasarla a
  otra ruta que redirija al login. Hoy son los 2 fallos que quedan en `main` (ficha
  `docs/changes/2026-10-20-fix-marcadores-conflicto-main.md`). El merge ya adaptó `ExampleTest`.
- La página deja huecos para la 039 (carrusel tras el avance de servicios) y la 040 (promociones
  tras el avance de servicios), sin marcadores visibles hasta que existan.
- `/servicios` es pública, fuera de los grupos por rol.
- Actualizar `docs/seo.md` (filas de `/` y `/servicios`, reglas del sitio) y el criterio del
  objetivo 7 en `docs/roadmap.md`. El `noindex` de las rutas no indexables sigue en el objetivo 7.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-10-20 | Spec aprobada (`approved`) con los supuestos de Decisiones | Aprobación del usuario |
| 2026-10-20 | Dividida: la galería pasa a la 039 y las promociones a la 040. Quedan 10 criterios: página de inicio con «Sobre nosotros» y avance de servicios, `/servicios`, portada con placa y logo | Petición del usuario al revisar las opciones de `/design-spec 038`, ronda 1, y al añadir promociones |
| 2026-10-20 | Vuelve a `draft`. Fotos de trabajos, carrusel, `/galeria`, `/servicios`, avance por categorías, «Sobre nosotros» | Petición del usuario al revisar las opciones de `/design-spec 038`, ronda 1 |
| 2026-10-20 | Spec aprobada (`approved`) con los supuestos de Decisiones | Aprobación del usuario |
| 2026-10-20 | Mapa de Google incrustado en la ubicación; `extends` 031 | Petición del usuario antes de aprobar |
| 2026-10-20 | Spec creada en `draft` | La página de inicio llegó a `main` fuera del flujo (merge `f096e17`); el usuario pidió regularizarla y añadir la galería |
| 2026-10-20 | Implementada (T001–T043, T090–T092; refinado D15 de la cabecera, el menú y el pie tras la revisión manual de T025) | /implement |
| 2026-10-21 | /review r1 = changes_requested (R1 bloqueante P9; R2–R4 importantes); vuelve a `approved` con T060–T068 | /review |
| 2026-10-21 | Implementada de nuevo tras /review r1 (T060–T068: R1–R8, R13, R14, R16, R18, R21, R23, R24, R27) | /implement |
| 2026-10-21 | /review r2 = changes_requested (R30 importante, R31 menor); vuelve a `approved` con T069 y T070 | /review |
| 2026-10-21 | Implementada de nuevo tras /review r2 (T069, T070: R30, R31) | /implement |
| 2026-10-21 | Rediseño de la ronda 3 (`design.md` 651e316, plan D16–D18); vuelve a `approved` con T071–T088. Criterios sin cambios | Petición del usuario: la opción B es «muy básica», seguir la línea de abound |
