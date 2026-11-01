---
spec: 038-pagina-inicio
verdict: approved
round: 3
date: 2026-10-21
base: e78622a
head: 1faf931
human_signoff: no requerido
---

# Review · 038 Página de inicio pública

## Resumen
`approved`. Los dos hallazgos de la ronda 2 están corregidos (T069, T070) y no hay hallazgos
nuevos. Rerun sobre `e78622a..1faf931`: 6 líneas de código en dos archivos, revisadas sin
subagente releyendo los archivos del disco (contrato del rerun). Rondas anteriores:
[history/review.r1.md](../history/review.r1.md) y [history/review.r2.md](../history/review.r2.md).
Conteo: bloqueante 0 · importante 0 · menor 0

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (suite completa) | ✅ 404 pasan (0 fallos) |
| `pint --test tests/Feature/PaginasPublicasCssTest.php` | ✅ |
| `python .ai/bin/aidd.py validate docs/specs/038-pagina-inicio` | ✅ 0 errores |
| gitleaks 8.21.2 (`e78622a..HEAD`) | ✅ sin fugas |
| Comprobación en el navegador (Playwright 1.63.0), descripción del primer servicio vaciada en la página | ✅ los tres `<dt>` en la primera columna a 1440 px (x = 454; antes, x = 790) y a 390 px (x = 16) |

El diff no toca PHP de la aplicación, vistas, rutas, nginx, scripts ni dependencias: semgrep,
`composer audit`, `npm audit`, `--medir` y Lighthouse no cambian respecto de la ronda 2 (todos en
verde, `e78622a`).

## Seguimiento de la ronda 2
| ID | Estado | Evidencia |
|---|---|---|
| R30 | corregido | `public/css/publico.css:260`, `.lb-lista dt { grid-column: 1; … }`; `PaginasPublicasCssTest::test_una_descripcion_vacia_no_deja_hueco` lo exige; medido en el navegador a 1440 y 390 px |
| R31 | corregido | `public/css/publico.css:1-5`, el comentario nombra los factores de `calc()` sobre `--touch-min` y `--space-8` (R25) |

## Hallazgos
Ninguno.

## Preparación para release
Sin cambios desde la ronda 1: rollback con `deploy/volver.sh` al tag anterior, sin migraciones ni
feature flags, `architecture.md` y `deployment.md` al día, sin avisos de SCA y ningún riesgo
declarado mitigado de más.

## Aceptados sin tarea
Aceptados por el usuario el 2026-10-21 (ronda 1):
- **R9** → roadmap: trait de tests de las páginas públicas (`xpath()`, `crearServicio()`, alta de usuario).
- **R10** → roadmap: sacar `panelDelRol()` del controlador (modelo o `app/Autorizacion/`).
- **R11** → roadmap: `--green-texto` se usa con la 039 y la 040.
- **R12** → roadmap: aclarar en la spec que el horario de CA5 se ve en el aviso de la puerta y en el pie.
- **R15** → roadmap: `sandbox` en el `iframe` del mapa, probando que sigue funcionando.
- **R17** → roadmap: nombre del enlace del logo con la marca (WCAG 2.5.3).
- **R19** → roadmap: enlace «Saltar al contenido» en el layout público.
- **R20** → roadmap: `aria-current="page"` en la navegación pública.
- **R22** → roadmap: título del aviso más descriptivo que «Aviso de la puerta» (cambia texto de la spec).
- **R25** → roadmap: tokens de tamaño propios para logo, mapa, placa y menú en lugar de múltiplos de `--touch-min`.
- **R26** → roadmap: simplificar las reglas de foco de `publico.css`.
- **R28** → roadmap: título de `/servicios` más descriptivo (cambia texto de la spec).
- **R29** → roadmap: `id` y `aria-labelledby` en los `h2` del aviso y del pie.
