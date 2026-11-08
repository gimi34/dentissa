---
result: fail
round: 1
mode: full
constitution_version: 1.3.0
date: 2026-11-08
spec_sha: 1623177086f6
plan_sha: ca4d08d49edd
tasks_sha: b431a0d98644
---

# Análisis · 039 Galería de trabajos

## Resumen
`fail`: no hay hallazgos críticos, pero hay seis altos que el usuario decide corregir. El más
importante es que la cabecera de caché del contrato de `/galeria/fotos/{foto}` no puede salir tal
como está (`PreventBackHistory` la pisa y falla con respuestas de archivo). Los demás: un log sin
definir, el escaneo de las imágenes nuevas, el test de P9 en las vistas nuevas, la memoria del peor
caso y el peso de la primera visita (K9). Todos se corrigen con `/plan 039 --fix` y
`/tasks 039 --fix`; las cuatro decisiones del usuario están tomadas.
Análisis hecho por un subagente independiente (perfil `critical`); cada hallazgo verificado en su
archivo.
Conteo: CRÍTICA 0 · ALTA 6 · MEDIA 8 · BAJA 6

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 16 / 16 |
| Amenazas con control y test | 7 / 9 (TM5 sin test en la gestión, A9; TM8 con el control en conflicto con `PreventBackHistory`, A1 y A10) |
| Principios de la constitución evaluados | 10 / 10 |

## Seguimiento de rondas anteriores
Primera ronda.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Inconsistencia / Seguridad | ALTA | plan → Contratos (`GET /galeria/fotos/{foto}`), ADR 0009 → Entrega, T014, T024; `bootstrap/app.php:37-39`, `app/Http/Middleware/PreventBackHistory.php:15-19` | `PreventBackHistory` va en todo el grupo `web` y pisa `Cache-Control` con `no-store`, así que la cabecera del contrato nunca saldría. Además usa `withHeaders()`, que no existe en `BinaryFileResponse` ni en `StreamedResponse`: servir el archivo así daría un 500. Con `max-age=3600`, una foto quitada seguiría viéndose hasta una hora, en contra de CA10. | Decisión del usuario: revalidar siempre. `/galeria/fotos/{id}` responde 200 con `Cache-Control: no-cache` y `ETag`, 304 si el `If-None-Match` coincide y 404 en cuanto se quita (test en `PaginaGaleriaTest`: cabeceras exactas, 304 y 404 tras quitar). `/plan --fix` y `/tasks --fix` |
| A2 | Constitución P8 | ALTA | plan → P8 y Observabilidad («un solo log nuevo»), plan → Riesgos, T021 | El log `warning` que se emite si falla el borrado del archivo tras el *commit* no tiene nombre, campos ni test. | El plan declara su nombre, nivel y campos (sin la ruta del archivo ni la descripción). Un test simula el fallo del borrado y comprueba el log. `/plan --fix` y `/tasks --fix` |
| A3 | Seguridad / Constitución P6 | ALTA | plan → P6 y Riesgos («EX2 ya cubre…»), T030 (`trivy fs`), `docs/security.md` EX2 | `trivy fs` no revisa los paquetes del sistema de la imagen construida. EX2 es una lista cerrada y no cubre avisos futuros de paquetes nuevos. | Una tarea ejecuta `trivy image` sobre `app` antes y después de T002 y anota la diferencia. Un aviso alto o crítico nuevo se corrige o lo registra el usuario como excepción. Se quita del plan la frase sobre EX2. `/plan --fix` y `/tasks --fix` |
| A4 | Constitución P9 | ALTA | T017, T025, T027, T028; `tests/Feature/PaginasPublicasCssTest.php:144-148, 257-260` | Ninguna tarea añade las vistas nuevas al test que prohíbe `style=""` y `<style>`. | `PaginasPublicasCssTest::VISTAS` (o un test equivalente para la gestión) incluye `publico/galeria.blade.php`, `publico/_trabajo.blade.php` y `galeria/index.blade.php`, y `GaleriaCssTest` cubre `.pub-solo-lector`. `/tasks --fix` |
| A5 | Seguridad (DoS) | ALTA | plan → `ProcesadorFoto`, D9, Riesgos; T010, T020; `docker/nginx/php/local.ini` (`memory_limit = 256M`, también en producción) | 6000 × 6000 son unos 144 MB en GD. Rotar o recortar crea otra imagen completa y supera 256M. Es un error fatal que no captura `FotoNoProcesable`, así que la subida da 500 en vez del mensaje de CA12. | El plan fija el presupuesto de memoria del peor caso admitido. Test en `ProcesadorFotoTest`: 6000 × 6000 con orientación EXIF 6 bajo 256M pasa o se rechaza con el mensaje de CA12, nunca con 500. `/plan --fix` y `/tasks --fix` |
| A6 | Inconsistencia / RNF | ALTA | spec → K9; RNF (≤ 500 kB); T014; design.md → K9 medido con dibujos | En el peor caso, `/galeria` descarga más de 1,6 MB en la primera visita, por encima de K9. Un móvil descarga la versión de 1600 px. | Decisión del usuario: cada foto genera también una versión de 800 × 600 (≤ 150 kB) servida con `srcset`. K9 se comprueba con `medir.mjs` sobre `/galeria` con fotos del tamaño máximo. Se ajusta el ADR 0009. `/plan --fix` y `/tasks --fix` |
| A7 | Inconsistencia | MEDIA | spec → RNF (carga diferida); plan → Trazabilidad RNF; T015 | La primera foto del carrusel, sin `lazy`, se descarga en la primera visita de `/` aunque la sección quede muy por debajo de la primera pantalla. | Decisión del usuario (con A6): todas las fotos de `/` cargan en diferido. `CarruselInicioTest` comprueba que ninguna lleva carga inmediata. `/plan --fix` y `/tasks --fix` |
| A8 | Datos | MEDIA | plan → Contratos (`consentimiento_usuario_id` `uuid`); T004; `usuarios.id char(36)` | En MariaDB 10.11, `uuid()` crea una columna `uuid` nativa, incompatible como clave foránea con `char(36)`. Las migraciones existentes usan `char(…, 36)`. | El contrato fija un tipo compatible con `usuarios.id` (y con `id`). Lo comprueban `migrate` y `rollback` de T004. `/plan --fix` y `/tasks --fix` |
| A9 | Cobertura (TM5) | MEDIA | TM5; T012, T025 | No hay test de que la descripción salga escapada en la gestión (tarjeta y diálogo de quitar). | Un caso en `GestionGaleriaTest` con una descripción que lleva etiquetas: sale escapada en `admin.galeria.index` y en `admin.galeria.quitar`. `/tasks --fix` |
| A10 | Seguridad (TM8) | MEDIA | TM8, Riesgos; spec → Seguridad | Las fotos con paciente pueden indexarse en buscadores de imágenes, que las conservarían después de retirar el consentimiento. Que Cloudflare no las guarde en caché es un supuesto sin comprobar. | Decisión del usuario: las fotos con paciente llevan `X-Robots-Tag: noindex` y las demás no (test de cabeceras). T095 anota `cf-cache-status` de `/galeria/fotos/*`. `/plan --fix` y `/tasks --fix` |
| A11 | Cobertura de riesgos | MEDIA | spec → Notas para /plan (`verificar.sh`); plan D4 | El plan no decide si `verificar.sh` comprueba GD con JPEG y WebP, el directorio escribible y el límite de cuerpo de nginx. | Decisión del usuario: dentro. Una tarea añade las tres comprobaciones a `verificar.sh`, con su test en `ConfiguracionProduccionTest`. `/plan --fix` y `/tasks --fix` |
| A12 | Cobertura (despliegue) | MEDIA | plan → P6 y Riesgos (EX4 vence el 2026-11-11); T096, T097 | Ninguna tarea de despliegue registra que antes hace falta el `/chore` de Symfony 7.4.23. | T096 lleva como condición `composer audit` limpio sin EX4. `/tasks --fix` |
| A13 | Ambigüedad | MEDIA | encabezado de tasks.md; T025, T027, T028, T030; `criterios.json` K2, K3b, K6 | K2, K3b y K6 sobre la app necesitan seis fotos con descripciones exactas y una con consentimiento. Ninguna tarea dice cómo se preparan esos datos ni cómo se retiran. | Una tarea fija la preparación y la limpieza de esos datos de medición (sin dejar nada en `dentissa`), con `medir.mjs` en verde para K2, K3b y K6. `/tasks --fix` |
| A14 | Dependencias | MEDIA | T021 (depende de T016), T023 (de T011 y T013) | Ni T021 ni T023 dependen de T012, el test de CA9 y CA10, en contra de P2. | T021 y T023 dependen de T012, y su «hecho cuando» incluye los casos de T012 que no son de vista. `/tasks --fix` |
| A15 | Conflicto (040) | BAJA | 039 CA1 y 040 CA1; plan D12 | Las dos specs sitúan su sección «después del avance de servicios». | Aceptado (ver abajo) |
| A16 | Referencias | BAJA | plan → Observabilidad, D4 | `observability.md` se actualiza en T091 y el roadmap en T031, no en T090. | `/plan --fix` |
| A17 | Terminología | BAJA | spec → Auditoría frente a plan y T016 | Nombres distintos para el mismo evento entre la spec y el plan. | Aceptado (ver abajo) |
| A18 | P3 | BAJA | plan → P3; T013 | El caso permitido del admin queda repartido entre T011 y T012, en contra de lo que dice el plan. | `/plan --fix`: el plan cita dónde se prueba cada caso permitido |
| A19 | Ambigüedad | BAJA | plan D13, `Galeria::mover`; índice único en `posicion` | El intercambio y la compactación bajo un índice único necesitan un orden o un valor temporal. D13 no tiene test. | Aceptado (ver abajo) |
| A20 | Diseño | BAJA | design.md → Decisiones; `criterios.json` (`quitar` elimina la barra lateral); T026 | La entrada «Galería» del menú del escritorio queda fuera de lo que mide `medir.mjs`. | Aceptado (ver abajo) |

## Decisiones pendientes del usuario
Ninguna: las cuatro se tomaron el 2026-11-08.
- A1: revalidar siempre (`no-cache` con `ETag`, 304 y 404 al quitar).
- A10: `X-Robots-Tag: noindex` solo en las fotos con paciente.
- A6 y A7: versión de 800 px con `srcset`; todas las fotos de `/` en diferido.
- A11: las comprobaciones de `verificar.sh` entran en la 039.

## Aceptados
Aceptados por el usuario el 2026-11-08:
- **A15** → nota de T015: `CarruselInicioTest` comprueba el orden relativo (la sección va después del avance de servicios), no la adyacencia, para que la 040 pueda colocar la suya. El plan de la 040 decidirá su posición respecto a «Nuestros trabajos».
- **A17** → nota de T016: los tests usan el vocabulario del registro de la 020 (`acceso_denegado` y los eventos `galeria_foto_*`). La spec describe el comportamiento; la correspondencia queda en el plan.
- **A19** → nota de T021, T012: `mover` y `quitar` respetan el índice único de `posicion`. `GestionGaleriaTest` prueba subir, bajar y quitar en el centro y en los dos extremos. La concurrencia del límite de 12 (D13) queda sin test automático, con `lockForUpdate` revisado en `/review`.
- **A20** → nota de T026: la entrada del menú del escritorio la mide `capturar.mjs --medir` y la verificación manual; `medir.mjs` mide solo el contenido (DS1).
