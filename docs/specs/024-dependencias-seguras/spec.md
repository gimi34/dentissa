---
id: 024
slug: dependencias-seguras
status: approved
created: 2026-09-29
extends: []
---

# 024 · Dependencias sin vulnerabilidades críticas ni altas

## Problema
Las bibliotecas de terceros de las que depende la aplicación, tanto las del servidor como las
herramientas del frontend, tienen vulnerabilidades conocidas de severidad crítica y alta. Todas
tienen corrección publicada. Con datos de salud y un objetivo de verificación ASVS nivel 2, la
constitución (P6) impide liberar cualquier versión mientras existan: la spec 020 ya está aprobada
y no se puede liberar por este motivo. Esta spec actualiza esas dependencias sin cambiar el
comportamiento de la aplicación y atiende RS16 por completo.

## Historias de usuario
- Como responsable del proyecto, quiero que ninguna dependencia tenga vulnerabilidades críticas ni altas, para poder liberar versiones y no exponer datos de salud.
- Como integrante del equipo, quiero que la actualización no rompa nada, para liberar sin regresiones.
- Como paciente o miembro del personal, quiero seguir entrando con mi cuenta de Google igual que antes.

## Criterios de aceptación
- [x] CA1 · Dado el proyecto actualizado, cuando se ejecuta la verificación de vulnerabilidades de las dependencias del servidor, entonces no reporta ninguna vulnerabilidad crítica ni alta.
- [x] CA2 · Dado el proyecto actualizado, cuando se ejecuta la verificación de vulnerabilidades de las dependencias del frontend, incluidas las que solo se usan en desarrollo, entonces no reporta ninguna vulnerabilidad crítica ni alta.
- [x] CA3 · Dada la actualización, cuando se ejecuta la suite completa de pruebas, entonces pasan todas, sin fallos nuevos respecto a la línea base anterior a la actualización.
- [x] CA4 · Dada la actualización, cuando se construyen los recursos del frontend, entonces la construcción termina sin errores.
- [x] CA5 · Dada la actualización, cuando una persona inicia sesión con su cuenta de Google en el entorno local, entonces entra igual que antes (verificación manual, porque depende del proveedor externo).
- [x] CA6 · Dada la actualización, cuando se comparan las versiones instaladas con las anteriores, entonces ni el framework del servidor ni ninguna dependencia que la aplicación ejecuta en producción cambia de versión mayor; solo las herramientas de construcción del frontend pueden hacerlo.
- [x] CA7 (abuso) · Dado un atacante que publica una versión maliciosa o un paquete con un nombre parecido, cuando se actualizan las dependencias, entonces no se añade ninguna dependencia directa nueva y cada versión que cambia, sea de una dependencia directa o de una de las que estas arrastran, lleva al menos 7 días publicada en el registro oficial.

## Fuera de alcance
- Instalar o ejecutar las herramientas de detección de secretos, análisis estático y análisis de contenedores (020/R26 → roadmap).
- Cambiar la versión del lenguaje del servidor o de su framework, y alinear la versión declarada con la que exige el registro de versiones instaladas (objetivo 5).
- Retirar las herramientas del frontend que hoy casi no se usan (roadmap, "Restos del esqueleto de Laravel").
- Corregir las vulnerabilidades medias o moderadas que no se resuelvan con la misma actualización: no bloquean la release (P6).
- Automatizar la verificación periódica de dependencias.

## Seguridad y privacidad
- Datos sensibles involucrados: ninguno directamente. La spec reduce la exposición de todos los datos de la aplicación, incluidos los de salud y las credenciales, a fallos de componentes de terceros.
- Quién puede hacer qué: no cambia ningún permiso ni flujo de ningún rol.
- Casos de abuso:
  - Como atacante, publico una versión maliciosa de una dependencia o un paquete con un nombre parecido para que entre en la actualización → no se añade ninguna dependencia directa nueva y cada versión que cambia, directa o arrastrada por otra, lleva al menos 7 días publicada (CA7).

## Cobertura de riesgos
| Corrección | Alcance | Criterios / motivo y destino |
|---|---|---|
| RS16.a — actualizar las dependencias vulnerables del servidor a versiones corregidas dentro de las restricciones declaradas | dentro | CA1, CA3, CA5, CA6, CA7 |
| RS16.b — aplicar las correcciones de las dependencias del frontend, revisando los saltos de versión mayor | dentro | CA2, CA4, CA6, CA7 |
| RS16.c — volver a verificar ambas sin hallazgos críticos ni altos | dentro | CA1, CA2 |

## Auditoría
No aplica: la spec no cambia datos sensibles, autenticación ni permisos. El acceso con Google no
cambia de comportamiento; CA5 solo comprueba que no hay regresión.

## Requisitos no funcionales
- Ningún cambio de comportamiento visible para ningún rol: la suite completa y la construcción del frontend lo verifican (CA3, CA4).

## Preguntas abiertas

## Supuestos
- Se permiten saltos de versión mayor solo en las herramientas de construcción del frontend, y solo si la construcción y la suite siguen pasando; nunca en el framework del servidor ni en dependencias que se ejecutan en producción (decisión del usuario, 2026-09-29).
- Las vulnerabilidades de dependencias que solo se usan en desarrollo se corrigen con el mismo criterio, sin excepciones (decisión del usuario, 2026-09-29).
- Recuento de partida (2026-09-29): servidor, 9 altas y 7 medias; frontend, 2 críticas, 6 altas y 1 moderada.
- Mientras las herramientas de secretos, análisis estático y contenedores no estén instaladas (020/R26), `/release` dejará constancia de su ausencia; esta spec no las instala.
- La spec queda enlazada desde "Pendientes y deuda" del roadmap, sin objetivo propio (decisión del usuario, 2026-09-29).
- La antigüedad mínima de 7 días aplica a toda versión que cambie, directa o arrastrada por otra dependencia; si alguna no la cumple, se usa la versión corregida anterior o se espera (decisión del usuario, 2026-09-29).

## Notas para /plan
- Servidor (RS16.a): `league/commonmark` y `guzzlehttp/guzzle`, transitivas de Laravel. Actualizar dentro de las restricciones de `composer.json` (p. ej. `composer update league/commonmark guzzlehttp/guzzle --with-dependencies`) y ejecutar Composer dentro del contenedor `app`, porque `composer.lock` exige PHP ≥ 8.4.1.
- Frontend (RS16.b): `concurrently`, `shell-quote`, `axios`, `vite`, `form-data`, `nanoid`, `picomatch`, `postcss` y `follow-redirects`. Probar primero `npm audit fix`; `--force` solo para saltos mayores de herramientas de construcción (p. ej. `vite`). Hoy solo `welcome.blade.php` usa Vite.
- Verificación: `composer audit`, `npm audit --audit-level=high`, `php artisan test`, `npm run build` y un login con Google en local (Socialite usa Guzzle).
- Antigüedad mínima de versiones nuevas: `security.agent.min_package_age_days: 7` en `.ai/project.yaml` (`shared/agent-security.md` §2).
- Al liberar, `docs/security.md` actualiza RS16.a, RS16.b y RS16.c por corrección.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-29 | Creación | /specify, riesgo RS16 (bloquea `/release 020`) |
| 2026-09-29 | Aprobada por el usuario | /specify |
| 2026-09-29 | CA7 y su caso de abuso cubren toda versión que cambie, directa o transitiva; la spec deja de citar por su identificador el riesgo de versión del lenguaje del servidor (sigue fuera de alcance, objetivo 5). Vuelve a `draft`. | /analyze 024 ronda 1: A02, A12 (decisión del usuario, 2026-09-29) |
| 2026-09-29 | Aprobada de nuevo por el usuario | /specify --edit |
