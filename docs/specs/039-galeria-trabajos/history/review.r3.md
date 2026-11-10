---
spec: 039-galeria-trabajos
verdict: approved
round: 3
date: 2026-11-09
base: 499ecb7
head: f57c06d
human_signoff: no requerido
---

# Review · 039 Galería de trabajos

## Resumen
`approved` (ronda 3, `--rerun`). R34, R35 y R37 de la ronda 2 están corregidos (T051–T057) y no
aparece nada nuevo. R36 sigue aceptado para el roadmap.
Revisión sin subagentes, porque el diff es pequeño: `code.diff` de 5,2 KB con una vista y un test, más
los documentos. Releí los archivos desde cero.
Conteo: bloqueante 0 · importante 0 · menor 0. Fuera de la spec: tests intermitentes de la 041 en la suite completa (ver Verificación automática)

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (suite completa, en `f57c06d`) | ⚠ tres pasadas: 845 en verde en la segunda; 2 fallos en la primera (sin nombres: la salida estaba recortada) y 1 en la tercera, `RecuperarContrasenaTest::test_una_cuenta_activa_recibe_el_enlace` («The expected [App\Mail\EnlaceRestablecimiento] mailable was not sent», línea 167). Es código de la spec 041 que la rama no toca (`git diff cf2a57d..HEAD` no cambia nada de la recuperación). Es el mismo intermitente que ya anotó la ronda 2, ahora en otro test de la 041: va a un `/fix` aparte y no es un hallazgo de la 039 |
| `php artisan test --filter=GestionGaleriaTest` (segunda pasada de los tests cambiados) | ✅ 15/15 |
| `node --test tests/js/publico.test.mjs` | ✅ 16/16 (sin cambios desde la ronda 2) |
| `vendor/bin/pint --test tests/Feature/Galeria/GestionGaleriaTest.php` | ✅ |
| `python .ai/bin/aidd.py validate docs/specs/039-galeria-trabajos` | ✅ 0 errores |
| `design/medir.mjs`, cuatro pantallas de gestión (T057) | ✅ todas las puertas y KPIs |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1–CA7 | sin cambios desde la ronda 2 | ✅ | |
| CA8 | `GestionGaleriaTest::test_la_ayuda_de_la_foto_avisa_del_limite_de_los_png`, `SubidaFotoGaleriaTest` | ✅ | la ayuda avisa del límite antes de subir (R37) |
| CA9 | `GestionGaleriaTest` | ✅ | |
| CA10 | `GestionGaleriaTest::test_quitar_pide_confirmacion`, `test_con_el_dialogo_abierto_el_fondo_queda_inerte` | ✅ | el diálogo, sin `aria-modal`, tiene nombre, descripción y el foco inicial en «Cancelar» (R34) |
| CA11 | sin cambios | ✅ | |
| CA12 | `SubidaFotoGaleriaTest` (PNG de más de 4000 px; más de 5 MB), `GestionGaleriaTest` (ayuda) | ✅ | por encima de 20 MB sigue el 413 de nginx (R36, aceptado) |
| CA13–CA16 | sin cambios | ✅ | |

## Seguimiento de la ronda 2
| ID | Estado | Evidencia |
|---|---|---|
| R34 | corregido | `resources/views/galeria/index.blade.php:120` tiene `role="dialog"`, `aria-labelledby` y `aria-describedby`, sin `aria-modal`; «Cancelar» con `autofocus` (:124). `test_quitar_pide_confirmacion` lo comprueba con XPath sobre el propio diálogo: nombre, descripción, sin `aria-modal` y foco. T051 falló antes de T052 |
| R35 | corregido | `plan.md` (Cambios por módulo, Pantallas, TM3, D9, D17, Trazabilidad, Rollout e Impacto), ADR 0009 con dos enmiendas fechadas, `architecture.md`, `deployment.md` y `security.md` (superficie de ataque con los 4000 px de los PNG). Las únicas menciones de 6 MB que quedan son históricas: las enmiendas y las notas de tareas ya hechas (T003, T039) |
| R36 | aceptado | sin cambios (roadmap) |
| R37 | corregido | la ayuda del campo dice «PNG hasta 4000 píxeles por lado» (`index.blade.php:94`), enlazada con `aria-describedby`. `spec.md` lo recoge con una fila en Decisiones y otra en Historial. T056 falló antes de T057 |

## Conformidad con el plan
- Cambios fuera del plan: ninguno (`scope.md`: 0 archivos sin tarea). T053–T055 son solo de
  documentación y no aparecen en `code.diff`.
- `design.md` y `docs/design/system.md` describen el diálogo como página de confirmación sin
  `aria-modal`, con la decisión `refinado` registrada.
- Documentos que no reflejan el código: ninguno.

## Constitution Check (sobre el código)
Sin cambios respecto a la ronda 2. La Definición de terminado vuelve a ✅, porque los documentos ya
están al día (R35).

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks 8.21.2 (`499ecb7..HEAD`, 8 commits) | ✅ sin fugas |
| semgrep 1.99.0 `p/php` (vista y test tocados) | ✅ 0 hallazgos (23 reglas) |
| `composer audit` / `npm audit` | sin cambios en los lockfiles desde la ronda 1. EX4 (Symfony) vence el 2026-11-11 |

El diff solo cambia un atributo ARIA y un texto de ayuda fijo, sin datos del usuario: no abre
superficie nueva.

## Observabilidad
Sin cambios desde la ronda 1.

## SEO
Sin cambios: el diff solo toca la gestión (`/admin/galeria`), que no se indexa.

## Hallazgos
Ninguno nuevo.

## Preparación para release
- Rollback, migraciones y feature flags: sin cambios desde la ronda 1. El rollback es factible: la
  entrega solo tiene *expand* y no lleva flags.
- `docs/architecture.md` y `docs/deployment.md` reflejan los cambios (T090, T091, T054).
- SCA: EX4 vence el 2026-11-11. Antes del `/release` hay que hacer el `/chore` de Symfony 7.4.23
  (condición de T096).
- **La rama no contiene v0.14.2:** antes del `/release` hay que fusionar `main`, para no deshacer en
  producción el arreglo del botón de alta de pacientes.

## Tareas añadidas
Ninguna.

## Aceptados sin tarea
Sigue aceptado **R36** de la ronda 2 (`history/review.r2.md`): va al roadmap con la clave `039/R36`.
También siguen vigentes los aceptados de la ronda 1 (`history/review.r1.md`), R14–R33.
