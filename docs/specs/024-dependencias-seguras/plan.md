---
spec: 024-dependencias-seguras
status: approved   # draft | approved | blocked
created: 2026-09-29
---

# Plan · 024 Dependencias sin vulnerabilidades críticas ni altas

## Enfoque técnico
Actualización acotada de las dependencias vulnerables, sin tocar código de la aplicación. En el
servidor, `composer update` solo de `league/commonmark` y `guzzlehttp/guzzle` con sus
dependencias, dentro de las restricciones de `composer.json`: todas las subidas son menores o de
parche. En el frontend, `npm audit fix` y fijar `vite` en 8.3.0 (la 8.3.1 aún no cumple los 7 días
de antigüedad), sin saltos de versión mayor en ninguna dependencia directa. Los "tests" son las
verificaciones que hoy fallan (`composer audit`, `npm audit --audit-level=high`) y deben pasar
después; la suite completa, la construcción de assets y un login con Google comprueban que no
hay regresiones.

## Constitution Check
| Principio | Resultado | Justificación / ajuste |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 024 `approved` el 2026-09-29. |
| P2 Test que falla antes y pasa después | ✅ | No cambia comportamiento. Las verificaciones de CA1 y CA2 fallan hoy (`composer audit`: 9 altas; `npm audit`: 2 críticas y 6 altas) y deben pasar tras la actualización; la suite completa (línea base de la rama) protege contra regresiones. No hacen falta tests PHPUnit nuevos. |
| P3 Autorización en servidor, por rol y recurso | ➖ | No cambia ninguna ruta ni permiso. |
| P4 Entrada validada y SQL con bindings | ➖ | Sin entradas nuevas ni SQL. |
| P5 Nada sensible en el repo | ✅ | Solo cambian lockfiles y el rango de `vite` en `package.json`; sin credenciales. |
| P6 Sin vulnerabilidades críticas/altas en producción | ✅ | Es el objeto de la spec: CA1 y CA2. |
| P7 Auditoría de datos sensibles | ➖ | Sin accesos nuevos a datos sensibles. |
| P8 Correlación y logs limpios | ➖ | Sin logs nuevos. |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `composer.lock` | `guzzlehttp/guzzle` 7.13.2 → 7.15.5, `league/commonmark` 2.8.2 → 2.10.3, y con ellas `guzzlehttp/promises` 2.5.0 → 2.5.3, `guzzlehttp/psr7` 2.12.3 → 2.13.1, `nette/schema` v1.3.5 → v1.3.6, `nette/utils` v4.1.4 → v4.1.5, `symfony/polyfill-intl-idn` v1.38.1 → v1.42.0, `symfony/polyfill-intl-normalizer` v1.38.0 → v1.42.0 (simulación `--dry-run` del 2026-09-29) | Medio: Guzzle es el cliente HTTP de Socialite (login con Google) |
| `package-lock.json` | `npm audit fix`: `axios` 1.13.6 → 1.20.0, `concurrently` 9.2.1 → 9.2.4, `form-data` 4.0.5 → 4.0.6, `follow-redirects` 1.15.11 → 1.16.0, `nanoid` 3.3.11 → 3.3.19, `picomatch` 4.0.3 → 4.0.7 y 2.3.1 → 2.3.2, `postcss` 8.5.8 → 8.5.28, `shell-quote` 1.8.3 → 1.9.0, `rolldown` 1.0.0-rc.10 → 1.2.11, y transitivas nuevas de dependencias existentes (`lightningcss*`, `agent-base`, `https-proxy-agent`, `debug`, `ms`); `vite` 8.0.1 → **8.3.0** | Bajo: solo `welcome.blade.php` usa Vite |
| `package.json` | Rango de `vite` `^8.0.0` → `^8.3.0` (al fijar 8.3.0); el resto de rangos no cambia | Bajo |
| `docs/security.md` | RS16.a, RS16.b y RS16.c → "en curso (spec 024)"; `/release` las pasa a mitigadas por corrección | Bajo |

## Contratos y datos
Sin cambios de rutas, respuestas, modelo de datos ni migraciones. Sin cambios de pantallas: los
assets construidos por Vite solo los usa `welcome.blade.php`, que ninguna ruta sirve.

## Estrategia de pruebas
- **Verificación de seguridad (CA1, CA2):** `docker compose exec app composer audit` y
  `npm audit --audit-level=high` sin críticas ni altas. Hoy fallan: es el "rojo" de P2.
- **Regresión (CA3):** `php artisan test` completo contra `dentissa_test`, con la misma línea
  base que la rama de partida (`chore/ai-dd-init`: 22 tests en verde).
- **Construcción (CA4):** `npm run build` sin errores (línea base del 2026-09-29: construye en ~2 s).
- **Login con Google (CA5):** verificación manual del usuario en local, anotada con fecha
  (decisión del usuario, 2026-09-29).
- **Versiones mayores (CA6):** comparación antes y después de `composer show --direct` (y de
  `laravel/framework`) y de `npm ls --depth=0`: ninguna dependencia de servidor cambia de versión
  mayor; en el frontend tampoco (solo `vite` cambia de menor).
- **Cadena de suministro (CA7):** `package.json` y `composer.json` no ganan dependencias directas;
  cada versión nueva de una directa lleva al menos 7 días publicada según el registro (`npm view
  <paquete> time`): `axios@1.20.0` (2026-08-26), `concurrently@9.2.4` (2026-07-11) y `vite@8.3.0`
  (2026-09-10). El diff del lockfile no añade paquetes con scripts de instalación
  (`hasInstallScript`).

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Tampering: una versión maliciosa o un paquete de nombre parecido entra como dependencia directa | A03:2025 | `package.json`, `composer.json` | Ninguna dependencia directa nueva; versiones nuevas de directas con ≥ 7 días; solo el registro oficial, con la integridad del lockfile | Comprobación CA7: diff de `require`/`devDependencies` vacío (salvo el rango de `vite`) y fechas de publicación ≥ 7 días |
| TM2 | Elevation of privilege: una transitiva nueva ejecuta código al instalarse | A03:2025 | `package-lock.json` | Actualizar con `--ignore-scripts` y revisar que el lockfile no añade paquetes con `hasInstallScript` | Comprobación sobre el diff del lockfile: 0 entradas nuevas con `hasInstallScript` |
| TM3 | Denial of service / tampering por un cambio de comportamiento en un componente actualizado (cliente HTTP del OAuth, parser Markdown) | A08:2025 | `guzzlehttp/guzzle`, `league/commonmark` | Solo subidas menores o de parche en el servidor; suite completa; login con Google | CA3 (`php artisan test`), CA5 (login manual) |
| TM4 | Vulnerabilidades conocidas en componentes (residuales) | A03:2025 | dependencias de servidor y frontend | Actualización a versiones corregidas | CA1 (`composer audit`), CA2 (`npm audit --audit-level=high`) |

Casos de abuso de la spec → amenazas: CA7 → TM1, TM2.

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: dependencias del servidor sin críticas ni altas | `composer.lock` | Verificación `composer audit` (hoy: 9 altas) |
| CA2: dependencias del frontend sin críticas ni altas, incluidas las de desarrollo | `package-lock.json`, `package.json` | Verificación `npm audit --audit-level=high` (hoy: 2 críticas y 6 altas) |
| CA3: suite completa sin fallos nuevos | `composer.lock` | `php artisan test` frente a la línea base de la rama |
| CA4: construcción del frontend sin errores | `package-lock.json`, `package.json` | `npm run build` |
| CA5: login con Google igual que antes | `composer.lock` (Guzzle) | Verificación manual del usuario en local |
| CA6: sin saltos mayores del framework ni de dependencias de producción | `composer.lock`, `package-lock.json` | Comparación de versiones mayores antes y después (`composer show --direct`, `npm ls --depth=0`) |
| CA7 (abuso): sin directas nuevas y versiones nuevas de directas con ≥ 7 días | `package.json`, `composer.json` | Comprobación de directas y de fechas de publicación (TM1) |
| TM1 | directas sin cambios; fechas ≥ 7 días | Comprobación CA7 |
| TM2 | `--ignore-scripts`; sin `hasInstallScript` nuevos | Comprobación sobre el diff del lockfile |
| TM3 | subidas menores; suite; login | `php artisan test`; login manual |
| TM4 | versiones corregidas | `composer audit`; `npm audit --audit-level=high` |

## Observabilidad
No aplica: la spec no cambia datos sensibles, autenticación ni permisos, y no añade logs, eventos
de auditoría ni métricas.

## Rollout
- Feature flag: no aplica. No hay comportamiento nuevo que encender o apagar; una dependencia no
  se puede activar a medias.
- Orden de despliegue: un único paso. En el servidor, `composer install --no-dev` con el lock
  nuevo; en assets, `npm ci && npm run build`. Sin migraciones.
- Compatibilidad: sin cambios de contrato; la versión anterior y la nueva sirven las mismas
  respuestas.
- Rollback: redeploy del tag anterior, que restaura los lockfiles anteriores. Vuelve a dejar las
  vulnerabilidades, así que solo se usa ante una regresión, y hay que reabrir RS16.
- Métricas a vigilar tras el deploy: errores 500 y fallos del login con Google en el log.
- Integración (decisión del usuario, 2026-09-29): se implementa en la rama
  `feature/024-dependencias-seguras`, creada desde `chore/ai-dd-init` con los commits de la spec y
  este plan traídos con `cherry-pick`. El orden de integración es `chore/ai-dd-init` → `main`,
  luego 024 y luego 020 (su `/release` exige dependencias limpias).

## Decisiones (→ ADR si son arquitectónicas)
- D1 · Servidor: `composer update league/commonmark guzzlehttp/guzzle --with-dependencies`
  dentro del contenedor `app` (el lock exige PHP ≥ 8.4.1, RD7), sin tocar `composer.json`. Se
  descarta un `composer update` completo, que arrastraría Laravel y el resto sin necesidad. Cubre
  RS16.a.
- D2 · Frontend: `npm audit fix --ignore-scripts` y después `npm install --save-dev vite@8.3.0
  --ignore-scripts`. La 8.3.1, que elegiría la corrección automática, se publicó el 2026-09-24 y
  no cumple los 7 días; la 8.3.0 ya corrige la vulnerabilidad (rango afectado 8.0.0–8.0.15)
  (decisión del usuario, 2026-09-29). Simulación sobre una copia del lockfile: 0
  vulnerabilidades, sin saltos mayores en directas y sin cambios de rango salvo `vite`. Cubre
  RS16.b.
- D3 · Transitivas nuevas (`lightningcss*`, `agent-base`, `https-proxy-agent`, `debug`, `ms`,
  `proxy-from-env` 2.x): llegan por dependencias directas existentes al actualizarse, no se
  añaden a mano. Se revisan con TM2 (sin scripts de instalación nuevos). `shared/agent-security.md`
  §2 aplica a dependencias directas nuevas, y no hay ninguna.
- D4 · Verificación del login con Google (CA5): manual, por el usuario en local (decisión del
  usuario, 2026-09-29).
- D5 · Sin tests PHPUnit nuevos: no hay comportamiento nuevo que probar; las verificaciones de
  seguridad hacen de "test que falla antes" (P2).
- D6 · Cobertura de riesgos: RS16.a, RS16.b y RS16.c dentro (spec 024 completa); pasan a "en
  curso (spec 024)" en la implementación y a mitigadas en `/release`. RD7.a sigue pendiente
  (objetivo 5).
- D7 · Rama y orden de integración: ver Rollout (decisión del usuario, 2026-09-29).

## Impacto en arquitectura
- `docs/architecture.md`: sin cambios (no cita versiones de dependencias).
- `docs/security.md → Riesgos conocidos → RS16`: estado por corrección (T091) y actualizar el
  recuento de la línea base.

## Riesgos y mitigaciones
- Una subida menor de Guzzle cambia algún detalle del OAuth con Google → login manual (CA5) antes
  de dar la tarea por hecha; si falla, se revierte solo `composer.lock` y se reabre el plan.
- `vite` 8.0 → 8.3 rompe la construcción → `npm run build` (CA4). Solo afecta a
  `welcome.blade.php`, sin uso.
- Aparece una vulnerabilidad nueva antes de liberar (p. ej. en `vite@8.3.0`) → `/release` vuelve
  a ejecutar `composer audit` y `npm audit`; si aparece, se reevalúa con 8.3.1, que ya tendrá más
  de 7 días.
- Paquetes `extraneous` en `node_modules` (`@emnapi/*`, `@napi-rs/wasm-runtime`, `@tybys/wasm-util`)
  ajenos al lockfile → no afectan a `npm audit` (lee el lockfile). Se limpian con `npm ci` al
  reinstalar.
- Rollback reintroduce las vulnerabilidades → documentado en Rollout; RS16 se reabre si se usa.
