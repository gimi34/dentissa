---
id: 039
slug: galeria-trabajos
status: approved
created: 2026-10-20
extends: [038]
---

# 039 · Galería de trabajos

## Problema
La página de inicio (038) presenta la clínica, pero no enseña su trabajo. La clínica quiere mostrar
fotos de tratamientos hechos en la consulta sin tocar código. Muchas de esas fotos muestran la boca
o los dientes de pacientes, que son datos de salud, y publicarlas en internet sin consentimiento o
de forma que se reconozca al paciente lo expondría.

## Historias de usuario
- Como visitante, quiero ver algunos trabajos de la clínica en la página de inicio y todos en su
  propia página, para confiar en su calidad.
- Como administrador, quiero subir, ordenar y quitar fotos de trabajos, dejando constancia del
  consentimiento cuando aparece un paciente, para mostrar el trabajo de la clínica sin exponer a
  nadie.
- Como persona que busca una clínica dental, quiero encontrar la galería en un buscador con un
  título y una descripción claros.

## Criterios de aceptación
### Páginas públicas
- [ ] CA1 · Dada una galería con fotos, cuando un visitante abre `/`, entonces ve, después del
  avance de servicios, un carrusel con las fotos en el orden fijado por el administrador, cada una
  con su descripción visible, botones «Anterior» y «Siguiente» que se usan con el teclado, «Foto n
  de N», sin avance automático, y un enlace «Ver toda la galería» a `/galeria`.
- [ ] CA2 · Dada una galería con fotos, cuando un visitante abre `/galeria`, entonces ve todas las
  fotos publicadas en el mismo orden, cada una con su descripción, y un enlace de vuelta al inicio;
  con la galería vacía ve «Aún no hay fotos de nuestros trabajos.».
- [ ] CA3 · Dada una galería sin fotos, cuando un visitante abre `/`, entonces ni el carrusel ni el
  enlace a la galería aparecen (ni recuadros vacíos ni marcadores de «Foto»).
- [ ] CA4 · Dado un visitante con JavaScript desactivado, cuando abre `/` o `/galeria`, entonces
  ve todas las fotos (en el carrusel, desplazándolo a mano) con su descripción, y los enlaces
  funcionan.
- [ ] CA5 · Dado un visitante en una pantalla de 320 a 1440 px de ancho, cuando recorre el carrusel
  o `/galeria` con el dedo, el ratón o solo el teclado, entonces la página no se desplaza en
  horizontal, cada control mide al menos 44 px, el foco se ve, y si pidió reducir el movimiento
  el carrusel cambia de foto sin animación.
- [ ] CA6 · Dado un buscador o un visitante, cuando pide `/galeria`, entonces la respuesta trae en
  el HTML del servidor el título «Nuestros trabajos · Dentissa», la descripción «Fotos de
  tratamientos hechos en la consulta Dentissa en Lázaro Cárdenas.», una sola dirección canónica
  absoluta y ninguna indicación de no indexar; título y descripción son distintos de los de las
  demás rutas públicas.
- [ ] CA7 · Dado un usuario con sesión de cualquier rol, cuando abre `/galeria`, entonces la ve
  igual que un visitante.

### Gestión
- [ ] CA8 · Dado un administrador, cuando sube una foto en JPG, PNG o WebP de hasta 5 MB con su
  descripción e indica que no aparece ningún paciente, entonces la foto se publica en el carrusel y
  en `/galeria`, con esa descripción como texto alternativo, en la última posición.
- [ ] CA9 · Dado un administrador, cuando sube una foto e indica que aparece la boca o los dientes
  de un paciente, entonces solo se publica si marca «Tengo el consentimiento escrito del paciente
  para publicar esta foto y no se le puede reconocer»; la gestión muestra junto a esa foto quién
  declaró el consentimiento y cuándo.
- [ ] CA10 · Dado un administrador con varias fotos subidas, cuando cambia su orden o quita una
  (tras confirmarlo), entonces el carrusel y `/galeria` muestran las fotos en el nuevo orden y sin
  la quitada en la siguiente visita, y la foto quitada deja de poder abrirse por su dirección.
- [ ] CA11 · Dado un administrador, cuando intenta subir una foto sin descripción, sin decir si
  aparece un paciente o con paciente y sin marcar el consentimiento, entonces se rechaza con el
  mensaje «Escribe una descripción de la foto.», «Indica si en la foto aparece un paciente.» o
  «Marca que tienes el consentimiento escrito del paciente.», según el caso, y no se publica nada.

### Abuso
- [ ] CA12 (abuso) · Dado un administrador, cuando sube un archivo que no es una imagen aunque
  termine en `.jpg`, una imagen de otro formato, una de más de 5 MB o una cuando ya hay 12 fotos,
  entonces se rechaza con un mensaje que dice el motivo y no se publica ni se guarda nada.
- [ ] CA13 (abuso) · Dado un dentista, un asistente, un paciente o un visitante sin sesión, cuando
  intenta abrir la gestión de la galería o enviar directamente una subida, un cambio de orden o una
  baja, entonces se le niega (sin sesión, al inicio de sesión) y la galería no cambia.
- [ ] CA14 (abuso) · Dada una foto con su ubicación u otros datos incrustados por la cámara,
  cuando se publica, entonces la versión que descarga un visitante no conserva esos datos.
- [ ] CA15 (abuso) · Dada una descripción con etiquetas o código, cuando una página pública la
  muestra, entonces aparece como texto literal y no se ejecuta nada.

## Fuera de alcance
- Fotos de las instalaciones o de la entrada (decisión del usuario: solo trabajos).
- Carrusel que avanza solo, comparador interactivo de antes y después, álbumes, vídeos, filtros
  por categoría, recortar o editar fotos en el navegador y textos largos por foto.
- Guardar el documento del consentimiento del paciente: lo conserva la clínica fuera del sistema;
  aquí solo queda la declaración del administrador (CA9).
- Detectar automáticamente si una foto permite reconocer a alguien.
- `sitemap.xml`: objetivo 7, que tendrá que incluir `/galeria`.

## Seguridad y privacidad
- Datos sensibles involucrados: **salud** (`docs/security.md`) en las fotos que muestran la boca o
  los dientes de un paciente. Se publican para todo internet, así que exigen la declaración de
  consentimiento escrito y que no se pueda reconocer al paciente (CA9). Las fotos sin pacientes
  (coronas, prótesis, modelos, laboratorio) no son datos sensibles.
- Quién puede hacer qué: cualquiera ve el carrusel y `/galeria`; solo el administrador sube, ordena
  y quita fotos.
- Casos de abuso:
  - Como usuario malintencionado con cuenta de administrador o con su sesión robada, intento subir
    un archivo ejecutable o enorme disfrazado de imagen, o llenar el servidor → se rechaza (CA12).
  - Como paciente, dentista o asistente, intento gestionar la galería o enviar la petición a mano
    → se niega (CA13).
  - Como administrador con prisa, intento publicar la foto de un paciente sin su consentimiento →
    no se publica sin la declaración, y la declaración queda con mi nombre y la fecha (CA9, CA11).
  - Como visitante, intento sacar la ubicación de la clínica o del fotógrafo de los datos
    incrustados en las fotos → no los hay (CA14).
  - Como administrador descuidado o malintencionado, escribo código en la descripción para que se
    ejecute en una página pública → se muestra como texto (CA15).

## Auditoría
- Publicar una foto con paciente es una escritura de datos de salud (`sensitive_write`). Esta spec
  guarda quién declaró el consentimiento y cuándo (CA9). El registro formal de auditoría (almacén
  solo-anexado, con resultado e identificador de petición) queda para la spec 022, como en la 026 y
  la 028 (excepción P7).
- El intento denegado de gestionar la galería (CA13) es un `access_denied`. Su registro formal
  queda para la spec 021, igual que en las specs 026, 028 y 030 (excepción P7).

## Diseño
Ronda 2 con `/design-spec 039` (2026-11-07), por criterios y sobre la página de inicio actual (038,
`c5` «arcada»): el carrusel dentro de `/`, la página `/galeria`, el estado sin fotos y la gestión del
administrador (subir con descripción, la pregunta del paciente y la casilla de consentimiento,
ordenar, quitar con confirmación y mensajes de error). La ronda 1 (diseñada dentro de la 038, opción
B, ronda 2) queda en [design/history/ronda-1/](design/history/ronda-1/design.md).
Sistema: [docs/design/system.md](../../design/system.md). Documento de diseño: [design.md](design.md).

## Criterios de diseño
Modo: `/` y `/galeria` persuadir · gestión operar · umbral 80 · mínimo por punto 3 · presupuesto 5
candidatos (`.ai/project.yaml → design`). Se miden con `evaluate.mjs`, pantalla por pantalla con
`design/medir.mjs`, a 320, 390, 900 y 1440 px las públicas y a 320, 390 y 1440 px la gestión
([design/criterios.json](design/criterios.json)). Cada candidato parte de la página de inicio actual
con el carrusel añadido; las fotos son dibujos propios (`data-origen="dibujada"`) con descripciones
de ejemplo hasta que el administrador suba las reales.

| ID | Pantalla | Qué se mide | Objetivo | Sale de |
|---|---|---|---|---|
| K1 | `/` | Orden de las secciones | «Ver todos los servicios» → «Ver toda la galería» → «Cómo llegar» | CA1 |
| K2 | `/` | Textos del carrusel | «Anterior», «Siguiente», «Foto 1 de 6», «Ver toda la galería» y las descripciones | CA1, CA4 |
| K3 | `/galeria` | Título y vuelta | «Nuestros trabajos» en la primera pantalla; «Volver al inicio» y las descripciones | CA2, CA6 |
| K4 | `/galeria` vacía | Texto exacto | «Aún no hay fotos de nuestros trabajos.» | CA2 |
| K5 | gestión | Acción principal | «Subir foto» sin desplazarse | CA8 |
| K6 | gestión | Consentimiento | la casilla de CA9 y «Consentimiento declarado por» en la tarjeta de la foto | CA9 |
| K7 | gestión con error | Mensajes de CA11 | los tres, visibles | CA11 |
| K8 | gestión al quitar | Confirmación | «¿Quitar esta foto?», «Quitar» y «Cancelar» | CA10 |
| K9 | todas | Peso de la primera visita | ≤ 1 MB, con las fotos fuera de pantalla en carga diferida | RNF, P10 |
| K10 | todas | JavaScript de la ruta | ≤ 100 kB | P10 |
| K11 | todas | Estabilidad visual (CLS) | ≤ 0.1 | modo |
| K12 | todas | Líneas de cada titular (`h1`–`h3`) | ≤ 3 en las públicas, ≤ 2 en la gestión | modo |
| K13 | todas | Imágenes de muestra | 0 | criterios §7 |

Puertas: las de `design-spec/references/criterios.md` §2; `G-red` admite solo `www.google.com` (el
mapa de `/`). CA4, CA5 y CA15 los cubren las puertas (`G-sin-js`, `G-desborde`, `G-toque`, `G-foco`,
`G-reducido`); CA3 es la página de inicio actual sin cambios; CA7 y CA12–CA14 no se ven en el diseño
(los prueban sus tests). Rúbrica: `jerarquia` 20, `primera_pantalla` 15, `sistema` 25, `lectura` 15,
`plantilla` 15, `riqueza` 10. Movimiento: solo el cambio de foto del carrusel, sin avance
automático y sin animación con movimiento reducido.

## Requisitos no funcionales
- Accesibilidad: WCAG 2.1 AA (P9). El carrusel no se mueve solo (WCAG 2.2.2) y anuncia qué foto se
  muestra.
- Descubribilidad (P10): `/galeria` indexable, con Lighthouse SEO ≥ 90 y como mucho 100 kB de
  JavaScript transferido; el contenido principal llega en el HTML del servidor.
- Rendimiento: cada foto publicada pesa como mucho 500 kB, y las que quedan fuera de la pantalla
  (en el carrusel y en `/galeria`) no se descargan hasta acercarse a ellas.

## Preguntas abiertas
Ninguna.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-20 | brecha | ¿Qué fotos muestra la galería? | Fotos de trabajos hechos en la consulta, no de las instalaciones ni de la entrada | usuario (`/design-spec 038`, ronda 1) |
| 2026-10-20 | brecha | Las fotos de trabajos pueden mostrar la boca de pacientes (datos de salud) | Se admiten las dos clases: sin pacientes, sin más; con paciente, solo con la declaración de consentimiento escrito y sin que se le pueda reconocer, guardando quién la declaró y cuándo | usuario |
| 2026-10-20 | brecha | ¿Carrusel o galería completa en la página de inicio? | Carrusel en `/` y todas las fotos en `/galeria` | usuario (`/design-spec 038`, ronda 1) |
| 2026-10-20 | brecha | Galería dentro de la 038 o aparte | Spec propia, separada de la 038 | usuario |
| 2026-10-20 | diseño | Diseño de la página | Diseñada en la 038 (opción B, ronda 2): [design.md](design.md) | usuario (`/design-spec 038`) |
| 2026-10-20 | implícita | Registro formal del acceso denegado y de la escritura de salud | Specs 021 y 022, excepción P7 como en 026/028/030 | `docs/specs/028-reprogramar-citas/spec.md` → Auditoría |
| 2026-10-20 | supuesto | Límites de la galería | JPG, PNG o WebP, ≤ 5 MB por archivo subido, máximo 12 fotos | razonable para una clínica y el disco del servidor |
| 2026-10-20 | supuesto | Comportamiento del carrusel | Una foto principal a la vez con «Anterior», «Siguiente» y «Foto n de N», sin avance automático; sin JavaScript se desplaza a mano | WCAG 2.2.2 (P9) |
| 2026-10-20 | supuesto | Título y descripción de `/galeria` | Los de CA6 | razonable; se confirman al aprobar |
| 2026-11-07 | diseño | El diseño de la ronda 1 se hizo con la página de inicio de la opción B, que la 038 sustituyó (ronda 6, `c5`) | Nuevo ciclo `/design-spec 039` con los criterios de «Criterios de diseño» (`sistema` con peso 25 para que encaje en la página actual); la ronda 1 se archiva | usuario |
| 2026-11-07 | diseño | Diseño de la ronda 2 | Acta aprobada: `c3` (80/100), refinado con las tres observaciones del evaluador. K11 del inicio (CLS de la página actual) aceptado como anterior a la 039, con un `/fix` de la 038 aparte: [design.md](design.md) | usuario |

## Notas para /plan
- Las fotos subidas no van al repositorio. En producción necesitan un volumen persistente
  (`docker-compose.prod.yml`, ruta protegida) y hay que decidir si entran en las copias
  (`deploy/respaldar.sh` solo copia la base) y en `verificar.sh`.
- Quitar los metadatos (CA14) y limitar el peso (≤ 500 kB) exige volver a codificar la imagen al
  publicarla. Hay que preferir lo que ya trae la imagen de PHP antes que una dependencia nueva.
- La declaración de consentimiento (CA9) es un dato nuevo de la foto: si hay paciente, quién y
  cuándo. Es una migración.
- El carrusel, sin JavaScript, es una fila con desplazamiento horizontal y `scroll-snap` dentro de su
  propio contenedor (no desborda la página). Con JavaScript, los botones y «Foto n de N» con una
  región `aria-live="polite"`. Así no depende de una librería.
- Rutas de la gestión dentro del grupo `admin` (AGENTS.md), con Gate según ADR 0003. `/galeria` es
  pública, fuera de los grupos por rol.
- Clasificar en `docs/security.md` las fotos de trabajos con paciente como datos de salud publicados
  con consentimiento. Añadir la fila de `/galeria` a `docs/seo.md`.
- Depende de la 038 (la página de inicio): se implementa después.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-10-20 | Spec aprobada (`approved`) con los supuestos de Decisiones | Aprobación del usuario |
| 2026-10-20 | Spec creada en `draft`, separada de la 038 | División decidida por el usuario al añadir promociones |
