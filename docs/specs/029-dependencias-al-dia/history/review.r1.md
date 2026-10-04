---
spec: 029-dependencias-al-dia
verdict: changes_requested
round: 1
date: 2026-10-03
base: cc1bb85
head: 436d20a
human_signoff: no requerido
---

# Review · 029 Dependencias sin avisos de seguridad conocidos

## Resumen
**changes_requested**, solo por un menor que el usuario decide corregir antes de liberar: R1, el
docblock del test, que es inexacto y tiene una línea demasiado larga. No hay bloqueantes ni
importantes.

Los siete criterios se cumplen, con su test o su comprobación declarada. La actualización de
`composer.lock` (41 paquetes) se contrastó contra el lock anterior y contra Packagist.

El diff de código ocupa 1.4 KB (solo el test) y el cambio real está en `composer.lock`. Por eso la
review la hizo un único subagente independiente con los encargos A, C, D y S juntos. Sin interfaz,
no hubo revisor de accesibilidad.

Conteo: bloqueante 0 · importante 0 · menor 1

## Verificación automática
| Comando | Resultado |
|---|---|
| `docker compose exec app php artisan test` | ✅ 152 passed (1207 assertions); línea base de T001: 152 |
| `vendor/bin/pint --test tests/Feature/DependenciasSegurasTest.php` | ✅ PASS |
| `python .ai/bin/aidd.py validate docs/specs/029-dependencias-al-dia` | ✅ 0 errores |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `DependenciasSegurasTest::test_las_dependencias_del_servidor_estan_en_versiones_corregidas` (falla con el lock de `cc1bb85`); `composer audit --locked` sin filtro | ✅ | Las mínimas coinciden con los rangos afectados de Packagist; en el lock, v12.69.2, 3.36.0 y 3.0.57 |
| CA2 | `npm audit` (comprobación declarada, P2 1.2.1); test del frontend de la 024 | ✅ | 0 vulnerabilidades |
| CA3 | `php artisan test` (comprobación de T022) | ✅ | 152, igual que la línea base |
| CA4 | comparación de `composer.lock` y `composer.json` (T021) | ✅ | 41 cambiados, todos de `packages`, en el cierre transitivo de las tres bibliotecas; 0 nuevos, 0 retirados, 0 saltos mayores; `composer.json` y `content-hash` iguales |
| CA5 | verificación manual del usuario (T023) | ✅ | |
| CA6 | fechas y origen en Packagist (T021) | ✅ | La más reciente, `symfony/finder` v7.4.20 (2026-09-26 06:31 UTC), con 7.6 días frente a T020 |
| CA7 | `composer audit` sin filtro y `docs/security.md` (T040) | ✅ | EX1 anotada y RS17.a "en curso (spec 029)"; la última frase se comprueba en T098 (A12) |

## Conformidad con el plan
- Cambios fuera del plan: ninguno. `scope.md`: 0 archivos sin tarea; T001 y T002 solo dejan notas.
- Partes del plan sin implementar: ninguna. `composer.json`, `package.json`, `package-lock.json`, el CI y `.ai/project.yaml` no cambian, como dice el plan.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 | ✅ | Spec, plan y tareas aprobados; `/analyze` en `pass` (ronda 2) |
| P2 | ✅ | CA1 con un test que falla antes (T010); CA2–CA7 con comprobaciones declaradas y anotadas (constitución 1.2.1) |
| P3, P4, P7, P8, P9 | ➖ | Sin cambios en el código de la aplicación, rutas, datos, logs ni interfaz |
| P5 | ✅ | gitleaks del rango: 0 |
| P6 | ✅ | `composer audit` (con y sin filtro) y `npm audit`: 0 |

Sin dependencias directas nuevas: no hace falta ADR.

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks (`cc1bb85..HEAD`) | ✅ 0 hallazgos |
| semgrep | ➖ Sin cambios en `app/` |
| composer audit `--locked` (CI: `--ignore-severity=low --ignore-severity=medium`; y sin filtro) | ✅ "No security vulnerability advisories found" en los dos |
| npm audit | ✅ 0 |
| trivy fs HIGH,CRITICAL | 9 altas, todas en `vendor/mockery/mockery/docs/requirements.txt`: falso positivo ya justificado en la 027 (027/R20; `vendor/` no se versiona) |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A03:2025 Software Supply Chain Failures | ✅ | 0 avisos; 41 cambios transitivos de las tres bibliotecas, sin paquetes nuevos ni plugins, todos con ≥ 7×24 h; el origen en GitHub coincide con Packagist (`source.reference`, `dist.reference` y `dist.url`, contrastados por el revisor en 7 paquetes y en T021 en los 41) |
| A08:2025 Software/Data Integrity Failures | ✅ | Ningún paquete cambiado declara `scripts` ni es `composer-plugin`; `allow-plugins` no cambia |
| A07:2025 Authentication Failures | ✅ | Login con Google verificado (T023). El cambio de Laravel 12.69 en la cookie de "recordarme" no afecta: la app no usa "recordarme" |
| A05:2025 Injection | ➖ | Sin código nuevo. Laravel 12.67 endurece la regla `in`, y es compatible con las reglas actuales (`in:1` y roles) |
| A02:2025 Security Misconfiguration | ✅ | Sin cambios de configuración. CVE-2026-102279 (página de depuración) queda corregido |
| A09:2025 Logging Failures | ➖ | Monolog sube de versión menor sin cambios de configuración |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: no aplica.
- Datos sensibles en logs del código tocado: ninguno (sin logs nuevos).
- `request_id` presente en los logs nuevos: no hay logs nuevos.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | menor | tests/Feature/DependenciasSegurasTest.php:11-12 | El docblock dice que "casi todas" las mínimas corrigen avisos críticos o altos y que `follow-redirects` es la excepción. Con la 029, tres de las cinco del servidor corrigen avisos bajos o medios. La línea 11 mide 137 caracteres, frente a unos 85 en el resto (Pint no reajusta comentarios) | Reformular: la 024 corrige críticos o altos (salvo `follow-redirects`) y la 029, bajos y medios; reajustar el párrafo |

Observaciones del revisor sin hallazgo:
- El CI sigue ignorando los avisos bajos y medios. Es una decisión de la 024, D10, y queda fuera de alcance en la 029.
- Monolog 3.11 añade `RedactingFormatter`, que podría servir para P8 en una spec futura.

## Preparación para release
- Rollback factible: sí. Volver al tag anterior restaura `composer.lock`, pero reabre RS17 y los avisos de EX1.
- Migraciones: no hay.
- Feature flags: no aplica.
- Docs actualizadas: `security.md` (T091: RS17.a "en curso" y EX1 anotada); `architecture.md` sin cambios (T090).
- SCA: 0 avisos de cualquier severidad.
- Cobertura de riesgos: RS17.a está implementada y probada, y queda "en curso" hasta la release, que la pasa a mitigada y retira EX1 (T098). Ningún riesgo figura como mitigado antes de tiempo.

## Tareas añadidas
- T024 ← R1

## Aceptados sin tarea
Ninguno.
