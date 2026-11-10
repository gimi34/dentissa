---
spec: 039-galeria-trabajos
verdict: approved
round: 4
date: 2026-11-10
base: f57c06d
head: 2607606
human_signoff: no requerido
---

# Review · 039 Galería de trabajos

## Resumen
`approved` (ronda 4, `--rerun`, decisión del usuario). Revisa solo lo que entró en la rama al fusionar
`main` dos veces:
- **`254aa32`:** v0.14.2, el arreglo del botón de alta de pacientes, y el chore de Symfony 7.4.23
  (PR #69).
- **`2607606`:** v0.15.0, la spec 044, liberada mientras tanto por otra sesión.

No hay hallazgos. La 039 no deshace nada de producción, la fusión se resolvió sin perder ninguna de las
dos specs y todas las comprobaciones pasan. La única excepción es K11 del inicio, el CLS de la página
actual, ya aceptado (plan D14).
Revisión sin subagentes: las rutas en común se revisaron a mano, releyendo los archivos.
Conteo: bloqueante 0 · importante 0 · menor 0

## Qué entró y cómo se resolvió
- **Primera fusión (`254aa32`):**
  - sin conflictos;
  - `composer.json`, `composer.lock`, `pacientes/index.blade.php` y `PacientesBotonAltaTest` son
    idénticos a los de `main` (`git diff origin/main HEAD` vacío en cada uno);
  - `scope.md` lista `composer.json` y `PacientesBotonAltaTest.php` sin tarea, porque vienen de
    `main`: son la ficha del fix (v0.14.2) y la del chore 2026-11-10.
- **Segunda fusión (`2607606`):** seis conflictos, todos de suma, resueltos conservando las dos partes:
  - `PaginasPublicasController::inicio` pasa a la vista `fotos` (039) y `panel` (044);
  - `MovilPacienteTest`: el menú del admin con «Galería» después de «Servicios» y, en los cuatro roles,
    «Ver sitio web» al final;
  - `publico.test.mjs`: los tests del carrusel (039) y los del aviso (044), 22 en total;
  - `architecture.md`: la fila de Páginas públicas de la 044 y la de Galería de la 039;
  - `system.md` y `system.html`: la 044 liberó el sistema **1.12.0**, así que la galería pasa a la
    **1.13.0**. El texto de la 039 y la fila de Decisiones lo explican, y `plan.md` y los comentarios
    de `app.css` también lo dicen.
- **Fusiones automáticas en rutas comunes, revisadas:**
  - `layouts/app.blade.php`: «Galería» en «Clínica» y la sección «Sitio» con «Ver sitio web»;
  - `routes/web.php`: las rutas públicas de la galería, con `whereUuid`/`whereIn`, conviven con el
    `fallback` 404 de la 044;
  - `publico.js` y `publico.css`: el carrusel y el aviso, sin conflictos;
  - `publico/inicio.blade.php`.
  - Las fotos que no existen dan la 404 propia de la 044.

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (suite completa, en `2607606`) | ✅ 911 en verde (13 998 aserciones). Antes de la segunda fusión, 847 |
| `node --test tests/js/publico.test.mjs` | ✅ 22/22 |
| `vendor/bin/pint --test` (PHP de los conflictos) | ✅ |
| `python .ai/bin/aidd.py validate` (todo el repo) | ✅ 33 specs, 9 fichas, 0 errores |
| `node docs/design/capturar.mjs --medir` (dos pasadas; base `dentissa_capturas` con seis fotos publicadas; línea base v0.15.0, la versión en producción) | ✅ «todo en orden» las dos veces; `docs/design/capturas/` sin cambios |
| `design/medir.mjs`, las siete pantallas renderizadas por la app con seis fotos | ✅ todas las puertas y KPIs, salvo K11 del inicio (0,212). Es el CLS de la página actual, ya aceptado en el plan (D14) y destinado a un `/fix` de la 038; el valor no cambia con la 044 |

## Criterios de aceptación
Sin cambios desde la ronda 3: CA1–CA16 ✅, con sus tests en verde en la suite fusionada.

## Seguimiento de la ronda 3
Sin hallazgos abiertos. R36 sigue aceptado para el roadmap.

## Conformidad con el plan
- Cambios fuera del plan: ninguno propio de la 039; lo nuevo viene de `main`, ya revisado y liberado.
- La versión del sistema de diseño cambia de 1.12.0 a 1.13.0 solo por la numeración, y el plan lo
  explica. Los tokens y los componentes no cambian.

## Constitution Check (sobre el código)
Sin cambios desde la ronda 3.

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks 8.21.2 (`f57c06d..HEAD`, 61 commits) | ✅ sin fugas |
| semgrep 1.99.0 `p/php` (PHP de los conflictos) | ✅ 0 hallazgos |
| `composer audit --locked` | ✅ sin avisos: EX4 retirada (chore 2026-11-10) |
| `npm audit --audit-level=high` | ✅ 0 vulnerabilidades |

## Observabilidad
Sin cambios: los eventos de auditoría de la galería siguen emitiéndose y probándose en la suite fusionada.

## SEO
`/galeria` y sus fotos, sin cambios. `seo.md` se fusionó solo, con las filas de la 039 y las de la 044.

## Hallazgos
Ninguno.

## Preparación para release
- La rama contiene v0.15.0, la versión en producción: el `/release` no deshace nada.
- Rollback, migraciones (`galeria_fotos`, solo *expand*) y flags: sin cambios.
- SCA limpio, sin excepciones de Symfony.
- La versión sería la **v0.16.0** (funcionalidad nueva).

## Tareas añadidas
Ninguna.

## Aceptados sin tarea
Siguen aceptados R14–R33 de la ronda 1 (`history/review.r1.md`) y R36 de la ronda 2
(`history/review.r2.md`). `/release` los lleva al roadmap.
