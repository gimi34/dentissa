---
id: 040
slug: promociones
status: approved
created: 2026-10-20
extends: [038]
---

# 040 · Promociones en la página de inicio

## Problema
La clínica quiere anunciar promociones (por ejemplo, un descuento en la primera limpieza) en su
página de inicio (038) para atraer pacientes nuevos, sin tocar código cada vez. Una promoción
publicada tiene que decir sus condiciones y su vigencia, y una caducada que siga a la vista engaña
al paciente.

## Historias de usuario
- Como visitante, quiero ver las promociones vigentes con sus condiciones y hasta cuándo valen, para
  decidir si agendo una cita.
- Como administrador, quiero crear, editar y retirar promociones con sus fechas, para que se
  muestren solo mientras están vigentes.

## Criterios de aceptación
- [ ] CA1 · Dadas promociones vigentes, cuando un visitante abre `/`, entonces ve, después del avance
  de servicios, la sección «Promociones» con como mucho 3, primero las que vencen antes, cada una con
  su título, su descripción, sus condiciones y su vigencia escrita como «Válida del 1 al 30 de
  noviembre de 2026».
- [ ] CA2 · Dada una promoción cuya fecha de fin ya pasó, que aún no empieza o que el administrador
  retiró, cuando un visitante abre `/`, entonces no la ve; el último día de vigencia sí se muestra
  (fechas de la zona horaria de la clínica).
- [ ] CA3 · Sin promociones vigentes, cuando un visitante abre `/`, entonces la sección
  «Promociones» no aparece.
- [ ] CA4 · Dado un administrador, cuando crea una promoción con título (hasta 80 caracteres),
  descripción (hasta 300), condiciones (hasta 300), fecha de inicio y fecha de fin, entonces aparece
  en `/` en cuanto está vigente, y la gestión indica si se ve ahora, si está programada, si venció
  o si queda fuera porque ya hay 3 vigentes que vencen antes.
- [ ] CA5 · Dado un administrador, cuando edita o retira una promoción (retirar pide confirmación),
  entonces `/` refleja el cambio en la siguiente visita.
- [ ] CA6 · Dado un administrador, cuando guarda una promoción sin condiciones, sin fechas o con la
  fecha de fin anterior a la de inicio, entonces se rechaza con «Escribe las condiciones de la
  promoción.», «Indica desde y hasta cuándo vale la promoción.» o «La fecha de fin debe ser igual
  o posterior a la de inicio.», según el caso, y no se publica nada.
- [ ] CA7 · Dado un visitante con JavaScript desactivado, o en una pantalla de 320 a 1440 px,
  cuando mira las promociones, entonces las ve completas, sin desplazamiento horizontal de la
  página y con contraste AA.
- [ ] CA8 (abuso) · Dado un dentista, un asistente, un paciente o un visitante sin sesión, cuando
  intenta abrir la gestión de promociones o enviar directamente una alta, una edición o una
  retirada, entonces se le niega (sin sesión, al inicio de sesión) y nada cambia.
- [ ] CA9 (abuso) · Dado un texto de promoción con etiquetas o código, cuando la página de inicio lo
  muestra, entonces aparece como texto literal y no se ejecuta nada.

## Fuera de alcance
- Cupones o códigos, aplicar el descuento a una cita o al cobro, y saber quién usó una promoción.
- Imágenes en las promociones, cintillos, contadores de tiempo, ventanas emergentes y colores de
  oferta (`docs/design/anti-cliches.md`).
- Precios en el catálogo y en `/servicios` (038): las cifras solo aparecen dentro del texto de una
  promoción.
- Avisos por correo o notificaciones de promociones.
- Una página propia de promociones.

## Seguridad y privacidad
- Datos sensibles involucrados: ninguno. Las promociones son información pública de la clínica.
- Quién puede hacer qué: cualquiera ve las promociones vigentes; solo el administrador las crea,
  edita y retira.
- Casos de abuso:
  - Como paciente, dentista o asistente, intento crear o cambiar una promoción, o enviar la petición
    a mano → se niega (CA8).
  - Como administrador descuidado o malintencionado, escribo código en el texto para que se ejecute
    en la página pública → se muestra como texto (CA9).
  - Como administrador con prisa, intento publicar una promoción sin condiciones o sin vigencia → se
    rechaza (CA6).

## Auditoría
- Las promociones no son datos sensibles: crearlas, editarlas o retirarlas no exige registro de
  auditoría.
- El intento denegado de gestionarlas (CA8) es un `access_denied`. Su registro formal queda para la
  spec 021, igual que en las specs 026, 028 y 030 (excepción P7).

## Diseño
Se diseña junto con la página de inicio en `/design-spec 038`: la sección «Promociones» de `/`, sobria
y sin estética de oferta, y la gestión del administrador (lista con su estado, crear y editar,
retirar con confirmación y errores). Sistema: [docs/design/system.md](../../design/system.md).

## Requisitos no funcionales
- Accesibilidad: WCAG 2.1 AA (P9). El estado de cada promoción en la gestión se dice con texto, no
  solo con color.
- Las promociones llegan en el HTML del servidor (P10); no añaden JavaScript.

## Preguntas abiertas
Ninguna.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-20 | contradicción | `anti-cliches.md` prohíbe «promociones, descuentos y precios en cintillo» y la 038 no publica precios | Sección sobria con condiciones y vigencia visibles; sigue prohibido el cintillo, los contadores y los colores de oferta (se ajusta `anti-cliches.md`) | usuario |
| 2026-10-20 | brecha | ¿Quién gestiona las promociones? | El administrador (crear, editar, retirar), con fechas de vigencia | usuario |
| 2026-10-20 | brecha | ¿Pueden mostrar precios o descuentos? | Sí, dentro del texto de la promoción, con condiciones y vigencia obligatorias | usuario |
| 2026-10-20 | brecha | Promociones dentro de la 038 o aparte | Spec propia | usuario |
| 2026-10-20 | supuesto | Condiciones y vigencia obligatorias | Toda promoción publicada dice sus condiciones y su vigencia | Ley Federal de Protección al Consumidor (promociones); a confirmar con la redacción vigente |
| 2026-10-20 | supuesto | Cuántas se muestran y en qué orden | Como mucho 3 vigentes, primero las que vencen antes | razonable: página de presentación sin saturar |
| 2026-10-20 | supuesto | Largo de los textos | Título 80, descripción 300, condiciones 300 caracteres | razonable para una tarjeta legible en el móvil |
| 2026-10-20 | implícita | Registro formal del acceso denegado | Spec 021, excepción P7 como en 026/028/030 | `docs/specs/028-reprogramar-citas/spec.md` → Auditoría |

## Notas para /plan
- Tabla nueva de promociones (migración) con fechas de inicio y fin y la marca de retirada. Las
  fechas se comparan en la zona horaria de la clínica (`config/clinica.php`,
  `CLINICA_ZONA_HORARIA`), como la agenda.
- Rutas de la gestión dentro del grupo `admin` (AGENTS.md), con Gate según ADR 0003.
- Depende de la 038 (la página de inicio): se implementa después.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-10-20 | Spec aprobada (`approved`) con los supuestos de Decisiones | Aprobación del usuario |
| 2026-10-20 | Spec creada en `draft` | El usuario pidió promociones en la página de inicio durante `/design-spec 038` |
