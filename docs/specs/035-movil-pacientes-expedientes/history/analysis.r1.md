---
result: fail
round: 1
mode: full
constitution_version: 1.3.0
date: 2026-10-16
spec_sha: c7631cc96a85
plan_sha: 07a0ac896291
tasks_sha: 80987a71508e
---

# Análisis · 035 Móvil para pacientes y expedientes del personal

## Resumen
`fail`: hay 6 hallazgos ALTOS que el usuario decide corregir. Entre ellos:
- valores sin token en las iniciales del perfil (A1);
- una rejilla del perfil que colocaría mal la edad (A2);
- etiquetas que cambiarían el escritorio (A3);
- migas por debajo de 44 px (A4);
- una mitigación sin tarea (A5);
- el foco casi invisible de los formularios tocados (A6).

Ningún CRÍTICO. Todos los criterios tienen tareas de test e implementación. Las correcciones son
localizadas (`/plan --fix` y `/tasks --fix`), más dos líneas de `design.md` y la fuente de los
supuestos de la spec.

Análisis hecho por un subagente de contexto limpio; cada hallazgo se verificó después en el código
(`pacientes/show.blade.php:43`, los `<style>` de los formularios en `pacientes/create.blade.php:202-203`,
`expedientes/create.blade.php:141-142` y `expedientes/show.blade.php:218-219`, las migas sin `.migas`,
`TOQUE_MAIN` de `capturar.mjs:643` y las reglas de `PacientesController:90-91`).

Conteo: CRÍTICA 0 · ALTA 6 · MEDIA 9 · BAJA 5

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 11 / 11 |
| Amenazas con control y test | 0 / 0 (sin amenazas: el plan no toca datos, permisos ni entradas) |
| Principios de la constitución evaluados | 10 / 10 |

## Seguimiento de rondas anteriores
Primera ronda.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Inconsistencia | ALTA | plan D12, «Cambios a incorporar»; T001, T012, T020 | `.perfil-avatar` va a `app.css` «con los mismos valores». Su `style=""` de hoy lleva `font-size:22px` y `font-weight:600`, que ningún token cubre; `literales()` los rechaza, así que fallaría T012 o cambiaría el escritorio | En el escritorio, las iniciales mantienen 22 px y peso 600 con tokens nuevos; en el móvil miden 17 px y el nombre 16 px, como `opcion-c.html` (decisión del usuario). Test: el bloque 035 sin literales y los tokens iguales a `system.md` (T012); a 1440, igual a la línea base (`--medir`) → `/plan --fix`, `/tasks --fix` |
| A2 | Inconsistencia | ALTA | plan D12 (`.perfil-cab`); T022 | `.perfil-cab` es una rejilla de 2 columnas con 4 hijos (avatar, nombre, edad, tipo de sangre): la edad caería en la columna de 48 px. La maqueta agrupa el texto en un solo bloque | El nombre, la edad y el tipo de sangre quedan a la derecha de las iniciales, en un único bloque. Test: `.perfil-cab` con el avatar y un solo hermano (T010) y `--medir`, que comprueba la edad a la derecha de las iniciales a 390 → `/plan --fix`, `/tasks --fix` |
| A3 | Inconsistencia | ALTA | plan D7; T024 | Los cuatro títulos de `expedientes/show` pasan de `<div>` a `<label>`, que es inline y pierde el `margin-bottom:12px`: el escritorio cambia | Las etiquetas nuevas se ven igual que los títulos de hoy en los dos modos. Test: su clase del bloque 035 da `display: block` con tokens (T012); a 1440, igual a la línea base (`--medir`) → `/plan --fix`, `/tasks --fix` |
| A4 | Cobertura | ALTA | plan D9, D10; T022–T026 | Las migas de las fichas y los formularios son `div` con `style=""` y enlaces sin `.migas`, de 15–18 px de alto. `TOQUE_MAIN` (`.main a`) los mide, así que CA9 fallaría y nada las adapta | Las migas de las pantallas incluidas usan el componente de la 033 (`.migas`, `.migas-actual`) y su enlace mide al menos 44 × 44 en el móvil. Test: marcado sin `style=""` (T010) y toque en `--medir` → `/plan --fix`, `/tasks --fix` |
| A5 | Cobertura de riesgos | ALTA | plan R1 | R1 se da por mitigado con «la comprobación WebKit de la 034 se repite en la ficha», pero ninguna tarea la hace | La ficha del paciente a 390 en Playwright WebKit muestra la fila de acciones sin desbordar, con `:has()` y sin él (comprobación con control positivo en T022), o R1 deja de citarla → `/plan --fix`, `/tasks --fix` |
| A6 | Constitución P9 | ALTA | spec Fuera de alcance; plan D9, R4 | Los campos de los tres formularios cuyo marcado se cambia conservan el foco de su `<style>` propio: borde `--p300` (2,2:1) y halo del 7 %. En pantallas modificadas no cumplen 2.4.7/1.4.11, y no hay decisión del usuario que lo acepte | Los campos de esos formularios muestran el foco del sistema (`--focus-ring`, ≥ 3:1), que es la única diferencia del escritorio que admite CA10 (decisión del usuario). Test: la regla de foco del bloque 035 con tokens (T012) y el foco visible en `--medir` → `/plan --fix`, `/tasks --fix`. La spec mantiene «Fuera de alcance» para los focos no tocados |
| A7 | Duplicación y conflicto | MEDIA | plan D11; T041 | La 014 también cambia `pacientes/create` y `expedientes/create`, que se solapan con D5 y D7; D11 y T041 solo citan `expedientes/show` | La nota de la 014 en el roadmap cubre las tres vistas y conserva `for`/`id`, `aria-describedby` y `.acciones-formulario` (T041) → `/plan --fix`, `/tasks --fix` |
| A8 | Duplicación y conflicto | MEDIA | plan Rollout; D10 | La rama 020 añade «Auditoría» al menú y un foco en `citas.css`. Si entra antes, la comparación con v0.8.0 fallaría en el escritorio del admin | La 035 sale antes que la 020, y la 020 se reconstruye después sobre `main` (decisión del usuario); Rollout y roadmap lo dicen → `/plan --fix` (y la nota de T041) |
| A9 | Inconsistencia | MEDIA | design.md contra plan, T001, T012 | design.md lista 3 tokens; el plan, 4: falta `--perfil-avatar-w` | design.md lista los mismos tokens que el plan, incluidos los de A1 → edición de `design.md` («Cambios a incorporar») |
| A10 | Inconsistencia | MEDIA | design.md contra D12, T010 | design.md pide `aria-hidden` en las iniciales; D12 solo lo pone en los iconos | Las iniciales llevan `aria-hidden="true"`. Test: en T010 → `/plan --fix`, `/tasks --fix` |
| A11 | Cobertura | MEDIA | CA6; plan D7; T011 | El controlador valida `password`, `fecha_nacimiento`, `telefono` y otros campos, y `proxima_visita` en el expediente, pero la vista solo muestra los errores de nombre, apellido y correo | Cada campo que el controlador valida muestra su mensaje junto al campo, con el texto de la validación y sin reglas nuevas (decisión del usuario). Test: un envío con errores en cada campo validado (T011) → `/plan --fix`, `/tasks --fix` |
| A12 | Cobertura | MEDIA | plan D6; CA8 | D6 no cubre el título de la barra (el nombre del paciente, fuera de `.content`) ni el nombre de las migas (hijo flex) | El título de la barra y el nombre de las migas parten con una palabra de 30 letras a 320 sin ensanchar la página. Test: palabras largas de `--medir` sobre esos dos textos (T014) → `/plan --fix`, `/tasks --fix` |
| A13 | Constitución P2 | MEDIA | plan Trazabilidad (CA9), Constitution Check P2 | CA9 solo se verifica con `--medir` y no se encuadra en el supuesto de P2 1.2.1 | La Trazabilidad y P2 declaran que CA9 se comprueba con el comando `--medir` (comportamiento en el navegador), como en la 034 → `/plan --fix` |
| A14 | Ambigüedad | MEDIA | spec Decisiones (3 `supuesto`); design.md Decisiones | Hay supuestos con fuente «agente (revisar)» que fijan el orden de CA1 y CA4 y el alcance de D12 | Cada supuesto queda con su fuente confirmada: el usuario aprobó la spec y el diseño con ellos a la vista (2026-10-16) → edición de la fuente en spec y `design.md` |
| A15 | Prueba débil | MEDIA | T013 | Con el fondo del botón transparente, el contraste del icono depende del fondo de `.topbar`, que ningún test fija | `.topbar` sigue con `background: var(--white)`. Test: aserción en T013 → `/tasks --fix` |
| A16 | Ambigüedad | BAJA | CA7; D10 | «Texto e icono centrados» y «grupos alineados» no tienen tolerancia | Tolerancia de 2 px en `--medir` → aceptado como nota de T014 |
| A17 | Inconsistencia | BAJA | plan Constitution Check P2 contra Estrategia; tasks | P2 dice que `MovilPersonalTest` cambia; la Estrategia y T020/T021, que sigue igual | El texto dice en todos los sitios que `MovilPersonalTest` no cambia → `/plan --fix` |
| A18 | Constitución P6 | BAJA | T002 contra T040 | T040 compara `trivy fs` con T002, que no lo ejecuta | T002 registra también `trivy fs` → `/tasks --fix` |
| A19 | Inconsistencia | BAJA | plan Cambios por módulo (Tests) | Cita «033 CA2», cuando el aspecto del menú es de la 034 CA9 y del botón de cerrar de la 033 (que mantiene 033 CA3) | Referencia corregida → `/plan --fix` |
| A20 | Inconsistencia | BAJA | D12 contra `opcion-c.html` | La maqueta usa iniciales de 17 px y nombre de 16 px en el móvil; D12 deja 22 y 20 px | Con A1: iniciales de 17 px y nombre de 16 px en el móvil (decisión del usuario) → `/plan --fix` |

## Decisiones pendientes del usuario
Resueltas el 2026-10-16:
- **A6** → corregir en la 035 el foco de los campos de los formularios tocados (foco del sistema).
- **A11** → mostrar el mensaje de cada campo validado junto a su campo, sin reglas nuevas.
- **A1 · A20** → tokens nuevos para el tamaño y el peso de las iniciales (22 px y 600 en el
  escritorio, como hoy; 17 px en el móvil) y nombre de 16 px en el móvil, como `opcion-c.html`.
- **A8** → la 035 sale antes que la 020.

## Aceptados
Aceptados por el usuario el 2026-10-16 (resolución por defecto, sin otra vuelta de plan):
- **A16** → nota de T014: tolerancia de 2 px para «centrado» y «alineado».
