---
spec: 039-galeria-trabajos
verdict: changes_requested
round: 2
date: 2026-11-09
base: a097006
head: 499ecb7
human_signoff: pending
---

# Review · 039 Galería de trabajos

## Resumen
`changes_requested` (ronda 2, `--rerun`). R1 a R13 están corregidos, salvo R3, que queda a medias: el
fondo de la vista ya es inerte, pero el layout no. No hay bloqueantes. Hay dos importantes nuevos:
R34, lo que queda de R3, y R35, los documentos que siguen diciendo 6 MB y no recogen el límite de los
PNG. Hay además dos menores. El usuario decidió corregir R34 quitando `aria-modal` (el diálogo es una
página de confirmación), corregir R35 y R37 y llevar R36 al roadmap (tareas T051–T057).
Revisión con un solo subagente (perfil `critical`) sobre el diff `a097006..HEAD` (37 KB), con los
encargos A, C+D, S y accesibilidad.
Conteo: bloqueante 0 · importante 2 · menor 2

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` | ✅ 844 en verde. En una pasada anterior falló una vez `RecuperarContrasenaVistasTest::test_el_foco_va_al_primer_campo_con_error` (spec 041, ajeno a la 039): pasa en aislado y en la siguiente pasada completa. Es un test intermitente previo, para un `/fix` aparte |
| `node --test tests/js/publico.test.mjs` (dos veces) | ✅ 16/16 |
| `vendor/bin/pint --test` (PHP tocados) | ✅ |
| `python .ai/bin/aidd.py validate docs/specs/039-galeria-trabajos` | ✅ 0 errores |
| `design/medir.mjs`, cuatro pantallas de gestión (T042) | ✅ todas las puertas y KPIs |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `CarruselInicioTest` (fotos desordenadas), `publico.test.mjs` | ✅ | R6, R8 y R12 corregidos |
| CA2 | `PaginaGaleriaTest` | ✅ | R9 corregido |
| CA3–CA7 | sin cambios desde la ronda 1 | ✅ | |
| CA8 | `SubidaFotoGaleriaTest` (PNG de 4000 px publicado) | ✅ | el límite de los PNG falta en la spec (R37) |
| CA9, CA10 | `GestionGaleriaTest` | ⚠ | diálogo con `aria-modal` y el layout tabulable (R34) |
| CA11 | `SubidaFotoGaleriaTest::test_cada_falta_sale_junto_a_su_campo_en_la_pagina`, `GestionGaleriaTest::test_el_aviso_de_error_recibe_el_foco_y_enlaza_cada_campo` | ✅ | R4 y R5 corregidos |
| CA12 | `SubidaFotoGaleriaTest` (PNG de más de 4000 px; más de 5 MB) | ✅ | R1 y R2 corregidos; por encima de 20 MB sigue el 413 (R36) |
| CA13 | `GestionGaleriaAccesoTest` (también sin `role:admin`) | ✅ | R11 corregido |
| CA14, CA16 | sin cambios | ✅ | |
| CA15 | `GestionGaleriaTest` (tres fotos y comillas) | ✅ | R7 corregido |

## Seguimiento de la ronda 1
| ID | Estado | Evidencia |
|---|---|---|
| R1 | corregido | `FotosGaleriaController::pngAcotado()` (cabecera, sin decodificar) tras `dimensions`; tests del rechazo, del PNG de 4000 px y de memoria |
| R2 | corregido | `client_max_body_size 20m` en las dos configuraciones y en `verificar.sh`; `ConfiguracionProduccionTest` |
| R3 | corregido a medias → R34 | intro y rejilla inertes; barra lateral y superior del layout no |
| R4–R13 | corregidos | tests nuevos o endurecidos, cada uno comprobado rompiendo lo que protege (notas de T041–T050) |

## Conformidad con el plan
- Cambios fuera del plan: ninguno; el límite de los PNG entró en el plan con T036.
- Documentos que no reflejan el código: R35.

## Constitution Check (sobre el código)
Sin cambios respecto a la ronda 1, salvo P3 (✅: la segunda capa ya tiene test, R11), P4 (✅: R1 corregido)
y la Definición de terminado (⚠: documentos desfasados, R35).

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks 8.21.2 (`f71c346..HEAD`) | ✅ sin fugas |
| semgrep 1.99.0 `p/php` (PHP tocados desde `a097006`) | ✅ 0 hallazgos |
| `composer audit` / `npm audit` | sin cambios en los lockfiles desde la ronda 1 |

La regla de los PNG solo lee la cabecera (`getimagesize`), va después de `mimes` y `dimensions` con
`bail` y su mensaje es fijo: no abre riesgo nuevo. Los 20 MB valen para todo el `server` de nginx (R36).

## Observabilidad
Sin cambios desde la ronda 1: eventos de auditoría emitidos y probados, logs sin datos sensibles y con `request_id`.

## SEO
Sin cambios en rutas públicas, metadatos ni HTML indexable desde la ronda 1, salvo el `srcset` (R9), que
ya no repite candidatos.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R34 | importante | `resources/views/galeria/index.blade.php:120`, `resources/views/layouts/app.blade.php:17,224-232` | Lo que queda de R3: el diálogo declara `aria-modal="true"`, pero la barra lateral, el botón de salir, «Menú» y «Subir foto» del layout siguen tabulables detrás del velo (WCAG 2.4.3). | Decisión del usuario: quitar `aria-modal`. El diálogo es una página de confirmación sin JavaScript, con el foco inicial en «Cancelar» |
| R35 | importante | `docs/specs/039-galeria-trabajos/plan.md:52,70,204,241,306,399,421`, `docs/architecture.md:233-234`, `docs/deployment.md:275`, `docs/security.md:68`, `docs/adr/0009-fotos-subidas-volumen-storage.md:72` | Los documentos siguen diciendo nginx a 6 MB y no recogen el límite de 4000 px de los PNG (TM3, superficie de ataque, Rollout, ADR). | Ponerlos al día |
| R36 | menor | `docker/nginx/dentissa.prod.conf:26-29`, `docker/nginx/dentissa.conf:21-25` | Los 20 MB valen para todo el `server`, también para las rutas sin sesión; el comentario dice que el resto lo frena la validación, que llega después de leer el cuerpo. Por encima de 20 MB sigue el 413 de nginx en inglés. | Acotar los 20 MB a `/admin/galeria` con un `location` propio, o una página 413 en español |
| R37 | menor | `docs/specs/039-galeria-trabajos/spec.md` (CA8, CA12), `resources/views/galeria/index.blade.php:94` | El límite de 4000 px de los PNG (decisión del usuario) no está en la spec, y la ayuda del campo no lo dice. | Anotarlo en la spec y en la ayuda |

## Preparación para release
- Rollback, migraciones y feature flags: sin cambios desde la ronda 1 (factible, solo *expand*, sin flags).
- SCA: EX4 vence el 2026-11-11; antes del `/release`, el `/chore` de Symfony 7.4.23 (condición de T096).
- **La rama no contiene v0.14.2:** antes del `/release` hay que fusionar `main`.

## Tareas añadidas
- T051, T052 ← R34 · T053, T054, T055 ← R35 (y la mención de `aria-modal` en el diseño) · T056, T057 ← R37

## Aceptados sin tarea
Aceptados por el usuario el 2026-11-09:
- **R36** → roadmap: los 20 MB de nginx valen para todo el servidor (acotarlos a `/admin/galeria`) y por encima de 20 MB sale el 413 en inglés.

Siguen vigentes los aceptados de la ronda 1 (`history/review.r1.md`): R14–R33.
