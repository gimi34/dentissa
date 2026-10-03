---
result: pass
round: 1
mode: full
constitution_version: 1.2.1
date: 2026-10-03
spec_sha: 01a463b82df3
plan_sha: 8b336c021cd7
tasks_sha: bb70f00379ce
---

# Análisis · 029 Dependencias sin avisos de seguridad conocidos

## Resumen
`pass`. El análisis independiente, completo y hecho por un subagente, no encuentra críticos ni altos.

Encuentra 6 medios y 5 bajos, todos verificados contra los archivos. Ninguno cambia el diseño:
- comprobaciones más estrictas en T021 (dependencias arrastradas, origen, plugins, árbol limpio,
  unidad de los 7 días);
- la asignación de CA7 a la release;
- la precisión del umbral de CA1 si apareciera un aviso nuevo;
- correcciones de redacción.

El usuario los acepta como notas de tarea.

Conteo: CRÍTICA 0 · ALTA 0 · MEDIA 6 · BAJA 5

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 6 / 7 (CA7 se cumple en la release, T098: A1) |
| Amenazas con control y test | 4 / 4 |
| Principios de la constitución evaluados | 9 / 9 |

## Seguimiento de rondas anteriores
Primera ronda.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Inconsistencia / Cobertura | MEDIA | tasks → Cobertura (CA7 → T040, T091); plan D6 | CA7 (EX1 retirada, RS17 mitigado) solo se cumple en `/release`, en T098. T040 y T091 se ejecutan antes, con EX1 vigente y RS17.a "en curso". | T040 no declara cubrir CA7; T098 lo cumple y lo comprueba: EX1 fuera de "Excepciones aceptadas", RS17.a mitigada con su versión y `composer audit --locked` sin filtro sin avisos sin registrar. → nota de T040, T098 |
| A2 | Cobertura | MEDIA | tasks T098; plan → Cambios por módulo (`docs/roadmap.md`) | T098 no menciona el cambio del roadmap. La entrada de EX1 (`docs/roadmap.md:44-47`) sigue como pendiente. | En la release, la entrada de EX1 deja de figurar como pendiente y la de dependencias cita la 029 como atendida; se comprueba leyendo el roadmap. → nota de T098 |
| A5 | Ambigüedad | MEDIA | spec CA4 y Decisiones ("lo que arrastran"); plan D1 y D2; T021 | "Arrastrada" no está definido, y T021 compara el diff con la simulación de T002, que sale del mismo comando: la comprobación es circular. | Cada paquete cambiado es una dependencia, directa o transitiva, de alguna de las tres bibliotecas según el lock anterior; se comprueba con `composer why`/`depends` en T021. → nota de T021 |
| A6 | Ambigüedad | MEDIA | spec CA1 frente a Decisiones (filas 2 y 3); plan D7 | CA1 exige cero avisos, pero las decisiones permiten cerrar con un aviso registrado como excepción; en ese caso CA1 fallaría. | Si al implementar aparece un aviso sin versión corregida con 7 días, la tarea se bloquea → `/specify --edit 029` (CA1). Hoy no ocurre: las tres tienen versión corregida con ≥ 7 días. → nota de T020, T040 |
| A7 | Seguridad | MEDIA | plan → Estrategia CA6 y TM1; T002, T021 | El campo `time` de Packagist sale de la fecha del commit o tag, que el autor puede retrodatar. Tampoco se comprueba que `source.reference` y `dist` del lock coincidan con Packagist (lección de 024/R8). | Para cada versión cambiada, `source.reference` y `dist` del lock coinciden con los de Packagist (T021). Se acepta como limitación que `time` no prueba la fecha real de publicación. → nota de T021 |
| A10 | Constitución (P2) | MEDIA | plan y tasks → Constitution Check P2, Trazabilidad de CA2 | CA2 se presenta como caracterización "comprobada en la 024", sin rotura comprobada en la 029. | CA2 es una comprobación declarada (P2 1.2.1, datos de un servicio externo): `npm audit` con 0, anotado en T020 y T040. El test del frontend de la 024 queda como apoyo. → nota de T040 |
| A3 | Inconsistencia | BAJA | T091 frente a plan → Cambios por módulo | T091 añade a la fila de EX1 una nota que el plan no prevé. | Se mantiene la nota (no adelanta la retirada) y se anota la diferencia. → nota de T091 |
| A4 | Inconsistencia | BAJA | plan → Modelo de amenazas, TM1 ("(T022)") | Las comprobaciones de CA4 y CA6 están en T021, no en T022. | Se toma T021 como la tarea de TM1. → nota de T021 |
| A8 | Seguridad | BAJA | plan TM2; T021 | Comprobar el tipo plugin solo en los paquetes nuevos no basta: un paquete existente podría pasar a `composer-plugin`. | Ninguna versión cambiada es `composer-plugin` ni `composer-installer`, y `config.allow-plugins` no cambia (T021). → nota de T021 |
| A9 | Seguridad | BAJA | plan → Enfoque y P5; `composer.json` → `post-update-cmd`, `post-autoload-dump` | `composer update` ejecuta scripts de la raíz (`vendor:publish --tag=laravel-assets --force`, `package:discover`) que pueden escribir fuera de `composer.lock`. | Tras T020, `git status` solo muestra `composer.lock`, o los archivos generados se revisan y se justifican (T021). → nota de T021 |
| A11 | Ambigüedad | BAJA | spec CA6; plan D1 y D2; T002, T021 | La regla de los 7 días no fija la unidad ni el momento. | Al menos 7×24 h entre el `time` de Packagist (UTC) y la ejecución de T020, anotando ambas marcas por versión. → nota de T002, T021 |

## Decisiones pendientes del usuario
Ninguna.

## Aceptados
Aceptados por el usuario el 2026-10-03:
- **A1** → nota de T040, T098: T040 no cubre CA7; T098 lo cumple y lo comprueba (EX1 fuera de "Excepciones aceptadas", RS17.a mitigada con su versión, `composer audit --locked` sin filtro sin avisos sin registrar).
- **A2** → nota de T098: en la release, la entrada de EX1 del roadmap deja de figurar como pendiente y la de dependencias cita la 029 como atendida.
- **A3** → nota de T091: la nota en la fila de EX1 no adelanta su retirada, que sigue en la release; difiere del plan solo en esa línea.
- **A4** → nota de T021: es la tarea de las comprobaciones de TM1 (el plan dice T022 por error).
- **A5** → nota de T021: cada paquete cambiado es dependencia, directa o transitiva, de alguna de las tres bibliotecas según el lock anterior (`composer why`/`depends`).
- **A6** → nota de T020, T040: si aparece un aviso sin versión corregida con ≥ 7 días, la tarea se bloquea → `/specify --edit 029` (CA1).
- **A7** → nota de T021: `source.reference` y `dist` de cada versión cambiada coinciden con Packagist; limitación del `time` aceptada y anotada.
- **A8** → nota de T021: ninguna versión cambiada es `composer-plugin` ni `composer-installer`, y `config.allow-plugins` no cambia.
- **A9** → nota de T021: tras T020, `git status` solo muestra `composer.lock`, o los archivos generados se revisan y se justifican.
- **A10** → nota de T040: CA2 es una comprobación declarada (`npm audit` con 0), según P2 1.2.1.
- **A11** → nota de T002, T021: ≥ 7×24 h entre el `time` de Packagist (UTC) y la ejecución de T020, con ambas marcas anotadas.
