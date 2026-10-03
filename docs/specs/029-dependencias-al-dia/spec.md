---
id: 029
slug: dependencias-al-dia
status: approved
created: 2026-10-03
extends: []
---

# 029 · Dependencias sin avisos de seguridad conocidos

## Problema
Tres bibliotecas de terceros del servidor tienen avisos de seguridad con corrección publicada:
- dos de severidad baja, el framework y la biblioteca de archivos, que cubre la excepción EX1 hasta
  el 2026-10-31;
- una media, la biblioteca criptográfica (RS17), detectada en la review de la spec 027.

Si no se actualizan antes de esa fecha, la excepción vence y la próxima release no podría
justificar los avisos bajos. Además, la vulnerabilidad media, que la verificación automática del
CI no muestra, seguiría abierta.

Esta spec actualiza solo esas bibliotecas, sin cambiar el comportamiento de la aplicación. Atiende
RS17 por completo y retira EX1.

## Historias de usuario
- Como responsable del proyecto, quiero que las dependencias no tengan avisos de seguridad conocidos, para liberar sin excepciones vencidas.
- Como integrante del equipo, quiero que la actualización no cambie nada de lo que hace la aplicación, para liberar sin regresiones.
- Como paciente o miembro del personal, quiero seguir entrando con mi cuenta de Google igual que antes.

## Criterios de aceptación
- [x] CA1 · Dado el proyecto actualizado, cuando se ejecuta la verificación de vulnerabilidades de las dependencias del servidor, entonces no reporta ningún aviso de ninguna severidad (crítica, alta, media ni baja).
- [x] CA2 · Dado el proyecto actualizado, cuando se ejecuta la verificación de vulnerabilidades de las dependencias del frontend, entonces no reporta ninguna vulnerabilidad, igual que antes de la actualización.
- [x] CA3 · Dada la actualización, cuando se ejecuta la suite completa de pruebas, entonces pasan todas, sin fallos nuevos respecto a la línea base anterior a la actualización.
- [x] CA4 · Dada la actualización, cuando se comparan las dependencias instaladas con las anteriores, entonces solo cambian las tres bibliotecas con aviso y las que estas arrastran; ninguna cambia de versión mayor y no se añade ninguna dependencia directa.
- [x] CA5 · Dada la actualización, cuando una persona inicia sesión con su cuenta de Google en el entorno local, entonces entra igual que antes (verificación manual, porque depende del proveedor externo).
- [x] CA6 (abuso) · Dado un atacante que publica una versión maliciosa o un paquete con un nombre parecido, cuando se actualizan las dependencias, entonces cada versión que cambia, directa o arrastrada, lleva al menos 7 días publicada en el registro oficial.
- [ ] CA7 · Dada la actualización, cuando se revisan las excepciones y los riesgos de seguridad, entonces la verificación de dependencias no deja ningún aviso sin registrar, la excepción EX1 indica que sus avisos quedan corregidos por esta spec y que se retira al liberarla, y la corrección RS17.a figura en curso por esta spec. Al liberar, EX1 sale de las excepciones vigentes y RS17.a pasa a mitigada.

## Fuera de alcance
- Actualizar el resto de las dependencias del servidor o del frontend que no tienen avisos (decisión del usuario, 2026-10-03).
- Cambiar la versión del lenguaje del servidor o la versión mayor de su framework (objetivo 5).
- Retirar las herramientas del frontend que casi no se usan, o la fijación de versión que dejó la spec 024 (roadmap, "Restos del esqueleto de Laravel").
- Automatizar la verificación periódica de dependencias, o hacer que el CI muestre los avisos medios y bajos.
- Excluir del análisis de contenedores los archivos de documentación de terceros (027/R20, roadmap).

## Seguridad y privacidad
- **Datos sensibles:** ninguno directamente. La spec reduce la exposición de todos los datos de la aplicación, incluidos los de salud y las credenciales, a fallos de componentes de terceros.
- **Quién puede hacer qué:** no cambia ningún permiso ni flujo de ningún rol.
- **Casos de abuso:**
  - Como atacante, publico una versión maliciosa de una dependencia o un paquete con un nombre parecido para que entre en la actualización → solo cambian las bibliotecas con aviso y lo que estas arrastran, sin dependencias directas nuevas (CA4), y cada versión que cambia lleva al menos 7 días publicada (CA6).

## Cobertura de riesgos
| Corrección | Alcance | Criterios / motivo y destino |
|---|---|---|
| RS17.a — actualizar la biblioteca criptográfica a una versión corregida | dentro | CA1, CA4, CA6, CA7 |
| EX1 — avisos bajos del framework y de la biblioteca de archivos, excepción que vence el 2026-10-31 | dentro | CA1, CA3, CA4, CA5, CA6, CA7 |

## Auditoría
No aplica: la spec no cambia datos sensibles, autenticación ni permisos. El acceso con Google no
cambia de comportamiento; CA5 solo comprueba que no hay regresión.

## Requisitos no funcionales
- Ningún cambio de comportamiento visible para ningún rol: la suite completa lo verifica (CA3).
- La spec se libera antes del 2026-10-31, fecha en que vence EX1.

## Preguntas abiertas

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-03 | brecha | ¿Qué se actualiza: solo lo vulnerable o todas las dependencias del servidor? | Solo las tres bibliotecas con aviso y lo que arrastran | usuario |
| 2026-10-03 | brecha | ¿Cuándo se considera cerrada? | Sin ningún aviso de ninguna severidad al liberar. Un aviso nuevo sin versión corregida que cumpla los 7 días se registra como excepción con fecha | usuario |
| 2026-10-03 | brecha | ¿Y si una versión corregida no cumple los 7 días a tiempo para el 2026-10-31? | Se espera dentro del plazo. Si no llega, se usa la versión corregida anterior que sí cumpla, o se renueva EX1 solo para ese paquete, con fecha nueva y aprobación del usuario | usuario |
| 2026-10-03 | implícita | Antigüedad mínima de las versiones nuevas | 7 días | `.ai/project.yaml` (`security.agent.min_package_age_days`), `shared/agent-security.md` §2 |
| 2026-10-03 | implícita | ¿Puede cambiar de versión mayor el framework o una dependencia de producción? | No | `docs/specs/024-dependencias-seguras/spec.md` (Supuestos, decisión del usuario 2026-09-29) |
| 2026-10-03 | implícita | Las vulnerabilidades medias y bajas no bloquean una release; solo las críticas y altas | Se corrigen igualmente en esta spec (umbral de cero avisos, decisión del usuario) | `docs/constitution.md` P6 |
| 2026-10-03 | brecha | CA7 describía un estado que solo existe después de liberar, así que no podía comprobarse al implementar | CA7 comprueba lo que deja la implementación (ningún aviso sin registrar, EX1 anotada, RS17.a en curso); la retirada de EX1 y RS17.a mitigada son pasos de la release | usuario (/analyze A1, 2026-10-03) |
| 2026-10-03 | supuesto | ¿Pertenece a un objetivo del roadmap? | Se enlaza desde "Pendientes y deuda", sin objetivo propio, como la 024 | `docs/roadmap.md` |

## Notas para /plan
- Recuento de partida (2026-10-03, `composer audit --locked`):
  - `laravel/framework` 12.62.0: CVE-2026-102279, baja, corregida en 12.69.0;
  - `league/flysystem` 3.35.1: CVE-2026-102601, baja, corregida en 3.35.3;
  - `phpseclib/phpseclib` 3.0.55: CVE-2026-84308, media, corregida en 3.0.57.
  - `npm audit`: 0.
- Según EX1, Laravel 12.69.3 se publicó alrededor del 2026-09-29 y actualizarlo arrastraba 42 paquetes. Comprobar la fecha de cada versión y el árbol que cambia (por ejemplo, `composer update laravel/framework league/flysystem phpseclib/phpseclib --with-dependencies --dry-run`). Composer se ejecuta dentro del contenedor `app`, porque `composer.lock` exige PHP ≥ 8.4.1.
- Verificación:
  - `composer audit --locked`, sin `--ignore-severity`, para CA1;
  - `npm audit`;
  - `php artisan test`;
  - un login con Google en local, porque Socialite usa Guzzle.
- Al liberar:
  - en `docs/security.md`, retirar EX1 de "Excepciones aceptadas";
  - RS17.a → mitigada, y quitar las menciones a EX1 como vigente en el texto del riesgo de dependencias;
  - en el roadmap, la deuda "dependencias" queda atendida por la 029.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-10-03 | Creación | /specify: EX1 vence el 2026-10-31 y RS17 (review de la 027) |
| 2026-10-03 | Aprobada | Decisión del usuario |
| 2026-10-03 | CA7 se reescribe para que se compruebe al implementar; la parte de la release queda como paso de `/release` (T098). Vuelve a `draft` | /analyze A1, decisión del usuario |
| 2026-10-03 | Aprobada de nuevo | Decisión del usuario |
