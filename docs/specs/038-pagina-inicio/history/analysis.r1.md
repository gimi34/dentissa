---
result: fail
round: 1
mode: full
constitution_version: 1.3.0
date: 2026-10-20
spec_sha: 3672869d6dd0
plan_sha: 19705d468541
tasks_sha: 097dfbb951b5
---

# Análisis · 038 Página de inicio pública

## Resumen
`fail`: no hay hallazgos CRÍTICOS, pero sí 5 ALTOS que conviene corregir antes de `/implement`.

Los ALTOS son:
- el requisito previo de la 031 sin decidir (A1);
- enlaces de la navegación que no existen en la 038 (A3);
- el peso del mapa contra el presupuesto de P10 sin medir (A4);
- los títulos en orden y los nombres accesibles verificados solo a mano (A11);
- la prohibición de literales de P9 que el test actual no puede comprobar bien (A5, MEDIA con efecto en A11).

Cuatro hallazgos dependen de decisiones del usuario. El análisis lo hizo un subagente independiente
(perfil `critical`) y los hallazgos se verificaron contra los archivos.

Conteo: CRÍTICA 0 · ALTA 5 · MEDIA 8 · BAJA 6

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 10 / 10 (CA8 «todos los enlaces funcionan» sin test, A3) |
| Amenazas con control y test | 6 / 6 |
| Principios de la constitución evaluados | 10 / 10 (P2, P9 y P10 con observaciones) |

## Seguimiento de rondas anteriores
Primera ronda.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Cobertura de riesgos · conflicto | ALTA | plan D13 y Riesgos; T004 | El requisito previo de `/release 031` está sin decidir: «el usuario elige» entre un `/fix` en `main` o adelantar D10. Mientras, `main` está en rojo (2 fallos de `ProxyHttpsTest`), la v0.10.0 serviría en `/` la landing sin estilos y `verificar.sh` fallaría en «/ redirige a https». T004, que bloquea T015 y T028–T030, depende de esa salida | Decisión del usuario; registrarla en Decisiones del plan y crear el `/fix` o ajustar las tareas |
| A2 | Inconsistencia (orden) | MEDIA | T016, T027 | T016 (`ProxyHttpsTest`/`ExampleTest`) y T027 (retirar la landing) no dependen de T004. Si A1 se resuelve con un `/fix` en `main` que toque esos archivos, habrá conflicto al traer `main` y «hoy falla en `main`» deja de ser cierto | Deben ser coherentes con la salida de A1: dependen de T004, o llevan una nota que diga qué hacer si el `/fix` ya los cambió. Test: suite completa tras T004 · `/tasks 038 --fix` |
| A3 | Cobertura · ambigüedad | ALTA | `opcion-b.html#inicio-solo038-m` (referencia de T025); layout (plan, T023); CA4, CA8 | La maqueta «solo 038» enlaza `#promociones` y `#trabajos`, que no existen en la 038, y «Servicios» apunta a `#servicios`, que desaparece sin servicios activos. CA8 exige que todos los enlaces funcionen y ningún test lo comprueba | La navegación de la 038 queda fijada (Servicios → `/servicios`, Ubicación → `#ubicacion`, «Iniciar sesión»/«Ir a mi panel», «Crear cuenta»; Promociones y Trabajos llegan con la 040 y la 039). Test: en `/` y `/servicios`, con y sin servicios activos, cada `href="#id"` tiene su `id` y cada ruta interna responde 200 o 302 · `/plan 038 --fix` + `/tasks 038 --fix` |
| A4 | Ambigüedad · P10 | ALTA | RNF «Rendimiento del mapa»; Trazabilidad; T010, T042 | «No se descarga hasta acercarse» solo se comprueba con `loading="lazy"`. Chromium carga los `iframe` perezosos a unos 1250–2500 px del viewport y la página de la 038 es corta, así que el mapa (y el JavaScript de Google) probablemente entra en la primera carga. No está dicho si cuenta para los 100 kB de JS y el 1 MB. T042 no pide medirlo con nginx (y su CSP) | Medir en T042 los bytes de JS y el total de `/` servida por nginx local, con caché vacía, a 390 y 1440 px, anotando si el `iframe` entra en la primera carga; y decidir qué pasa si entra · decisión del usuario + `/plan 038 --fix` (y `/specify --edit` si cambia el RNF) |
| A5 | Viabilidad del test · P9 | MEDIA | T014; `tests/Feature/Concerns/ReglasCss.php` (`literales()`) | `literales()` marca como literal cualquier `font-family` distinto de `inherit`, así que `font-family: var(--font-publica)` haría fallar T014 aunque cumpla P9. Además no detecta duraciones (`0.25s`), `rem`/`em` ni `cubic-bezier(` | `publico.css` sin literales de color, tamaño, peso, familia, duración ni curva fuera de `@font-face` y `@media`, admitiendo `var(--…)`. Test: `PaginasPublicasCssTest` con un control positivo por patrón; `ReglasCss.php` se declara en la tarea si se amplía · `/tasks 038 --fix` |
| A6 | Ambigüedad (contrato del test) | MEDIA | plan (`publico.css`); T014 | T014 se escribe antes que `publico.css` y comprueba `--touch-min` y el foco en «puertas, botones, enlaces de la cabecera y `summary`», pero ni el plan ni las tareas fijan los selectores | El plan lista las clases de los componentes (las de `opcion-b.html`: `.lb-placa`, `.lb-puertas a`, `.pub-bar`, `.pub-menu summary`, `.lb-pie`, `.c-btn`, `.c-link`…). Test: T014 sobre esos selectores · `/plan 038 --fix` |
| A7 | Inconsistencia (tareas) | MEDIA | T021, T022, T023 | Criterios de hecho circulares: T021 se cierra cuando pasan casos de T012 «cuando la ruta existe (T022)», y T022 depende de T021. T023 se cierra «con las vistas de T025/T026», que dependen de T023. T022 crea «vistas mínimas» sin declararlas (pasaría de 3 archivos) | Cada tarea se cierra con lo ya hecho: T021 con un test propio de `porCategoria()` (o su criterio pasa a T022), sin vistas mínimas sin declarar, y T023 con un caso sobre una vista de prueba · `/tasks 038 --fix` |
| A8 | Constitución P2 | MEDIA | T016 | Las pruebas de `ProxyHttpsTest` sobre `/admin` pasan desde el principio: son caracterización de 030 CA2, y T016 no pide «comprobado rompiendo» | T016 cierra con «comprobado rompiendo `trustProxies` (las dos pruebas en `/admin` fallan)». Test: `ProxyHttpsTest` · `/tasks 038 --fix` |
| A9 | Inconsistencia (`extends`) | MEDIA | `extends: [001, 031]`; plan D10; T016, T030, T041 | La 038 cambia la ruta con la que la 030 comprueba su CA2 (test y `verificar.sh`), y la 030 no consta. 038 CA7 amplía 031 CA1, y ninguna tarea lo anota en la 031 (T041 solo anota la 001) | Una fila en Decisiones de la spec dice que 030 CA2 no cambia de comportamiento, solo la ruta de prueba (o `extends` incluye la 030). T041 añade Historial en 030 y 031. Comprobación: `aidd.py validate` · `/specify --edit` + `/tasks 038 --fix` |
| A10 | Seguridad (documentación) | MEDIA | `docs/security.md` → superficie de ataque; T090–T091 | Ninguna tarea registra `GET /` y `GET /servicios` como entradas públicas, el riesgo aceptado TM6 (sin límite propio) ni que el mapa envía la IP y el navegador del visitante a Google | `docs/security.md` lo recoge. Comprobación: `validate` y `/review` · `/tasks 038 --fix` |
| A11 | Constitución P9 (WCAG) | ALTA | RNF Accesibilidad; Trazabilidad; T013, T031 | «Títulos en orden» y «decorativos ocultos» se verifican solo con NVDA a mano, aunque se pueden comprobar en el HTML del servidor | En `/` y `/servicios` los títulos no saltan de nivel; el logo lleva `role="img"` con nombre, el `iframe` lleva `title` y lo decorativo `aria-hidden`/`alt=""`. Test: `PaginasPublicasTest::test_titulos_en_orden` y `test_nombres_accesibles`; NVDA queda como verificación complementaria · `/tasks 038 --fix` |
| A12 | Ambigüedad (arnés) | BAJA | Estrategia de pruebas; T010, T012 | `dentissa_test` se carga del volcado con 15 servicios activos en 8 categorías. No se aclara que los casos de vacío desactivan todos los servicios en la transacción ni que el orden se compruebe entre categorías propias del test | Nota en T010 y T012 |
| A13 | Ambigüedad (datos) | BAJA | plan «Datos que se leen», D7 | La base ordena sin distinguir mayúsculas ni acentos, pero el plan agrupa en PHP por la cadena exacta tras `trim()`: «Ortodoncia» y «ortodoncia» darían dos puertas. Tampoco se define qué pasa con una categoría real llamada «Otros servicios» | Nota en T021: agrupar sin distinguir mayúsculas (mostrando la primera grafía) y fundir «Otros servicios» con las vacías; un caso en `ServiciosPublicosTest` |
| A14 | Inconsistencia diseño · plan | BAJA | `design.md` (Puerta con contador en `/servicios`); T026 | T026 no incluye el número de servicios por categoría que muestra el índice | Nota en T026 y un caso en T012 |
| A15 | Ambigüedad | BAJA | T025; plan, Accesibilidad | El `h2` del aviso de la puerta no tiene texto fijado (la maqueta: «Aviso de la puerta»). «Cómo llegar» aparece dos veces con destinos distintos (`#ubicacion` y Google Maps) | Nota en T025: el `h2` dice «Aviso de la puerta»; el de la placa pasa a «Ver la ubicación» y solo el de la dirección es «Cómo llegar» (Google). El test de TM5 busca el enlace por su `href` |
| A16 | Duplicación · conflicto | BAJA | `routes/web.php` (plan 020); T013 frente a la 039 | La 020 también toca `routes/web.php` (conflicto de texto menor). Los invariantes «ningún `<script>`» y «ningún recurso externo salvo el mapa» de T013 los tendrá que relajar la 039 (carrusel) | → spec 039: su plan modifica esos casos de `PaginasPublicasTest` en lugar de duplicarlos |
| A17 | P10 · documentación | BAJA | P10 «(hoy solo `/registro`)»; `seo.md` SE3.a y «sin barra final»; T040 | T040 no actualiza SE3.a («la única ruta indexable») ni aclara que la canónica de la raíz lleva barra (`https://dentissa.app/`). El paréntesis de P10 queda desfasado | Nota en T040 (SE3.a y la barra de la raíz); la enmienda PATCH de P10 es decisión del usuario |
| A18 | Seguridad (ruta protegida) | MEDIA | T041 (`AGENTS.md`) | `AGENTS.md` es una ruta protegida fuera de `/init` (`shared/agent-security.md` §4). D2 aprobó el layout, no editar `AGENTS.md` | Decisión del usuario; T041 anota la aprobación |
| A19 | Ambigüedad (test de CA10) | BAJA | T013, T010 | En los tests `APP_URL` es la local, pero CA10 pide la canónica de producción. «Nada las referencia» fallaría si busca en `docs/`. El «falla por…» de T010 está mal: `/` existe y hoy sirve `landing.index` | Nota en T013 (fijar `app.url` a `https://dentissa.app` en el test, canónica exacta, un `X-Forwarded-Host` ajeno no la cambia; buscar referencias solo en `resources/` y `public/`) y en T010 (falla porque `/` sirve `landing.index`) |

## Decisiones pendientes del usuario
Resueltas el 2026-10-20:
- **A1:** un `/fix` en `main` antes de `/release 031` devuelve `/` al login (plan D13).
- **A4:** excepción de P10 para el JavaScript de terceros del mapa, medida en T042 (plan, Constitution Check).
- **A17:** la enmienda PATCH de P10 queda en el roadmap.
- **A18:** el usuario aprueba que T041 edite `AGENTS.md`.

## Aceptados
Aceptados por el usuario el 2026-10-20 como notas de tarea (sin otra vuelta de plan):
- **A12** → nota de T010, T012: los casos de vacío desactivan todos los servicios en la transacción; el orden se comprueba con categorías propias
- **A13** → nota de T012, T021: agrupación sin distinguir mayúsculas ni acentos; «Otros servicios» se funde con las vacías
- **A14** → nota de T012, T026: el índice de `/servicios` muestra el número de servicios por categoría
- **A15** → nota de T010, T025: «Aviso de la puerta» como `h2`; «Ver la ubicación» en la placa y «Cómo llegar» solo en la dirección
- **A16** → roadmap: el plan de la 039 modifica los casos «ningún script» y «ningún recurso externo» de `PaginasPublicasTest` en lugar de duplicarlos
- **A17** → nota de T040: corrección del sitemap y barra de la raíz en `docs/seo.md`; enmienda de P10 → roadmap
- **A19** → nota de T010, T013: canónica de producción fijada en el test, búsqueda de referencias solo en `resources/` y `public/`, motivo del fallo de T010

Corregidos en `/plan 038 --fix` y `/tasks 038 --fix` (pendientes de comprobar en la ronda 2): A1, A2, A3, A4, A5, A6, A7, A8, A9, A10, A11, A18.
