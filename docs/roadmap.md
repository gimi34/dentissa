---
updated: 2026-09-28
---

# Roadmap

**Etapa actual:** MVP (proyecto académico; entrega aproximada: fin de semestre, diciembre de 2026)

Objetivos aceptados por el usuario en `/init` (decisión del usuario, 2026-09-28).

## Objetivos
| # | Objetivo | Criterio de éxito | Spec | Estado |
|---|---|---|---|---|
| 1 | Esquema reproducible y repo sin datos sensibles | Un clon limpio levanta la base completa con `php artisan migrate --seed` sin el volcado, y los tests usan esa base. El historial de git ya no contiene el volcado con datos ni las contraseñas del README. Riesgos: RD2.a, RD2.b, RD2.c, RD2.d, RS6.a, RS6.b, RS6.d; fuera: RS6.c → objetivo 5 | 013 (parte 1) | pendiente |
| 2 | Autorización por alcance y sesiones seguras | Cada acción sobre pacientes, expedientes y citas tiene test por rol (permitido y 403) según la regla confirmada; `/cliente` exige su rol; login y registro limitados. Riesgos: RS1.a, RS1.b, RS1.c, RS1.d, RS1.e, RS2.a, RS2.b, RS3.a, RS3.b, RS4.a, RS4.b, RS4.c, RS5.a, RS5.b, RS5.c, RS9.a, RS9.b, RS10.a, RS10.b, RS11.a, RS13.a, RS14.a | 014 (RS1); 015–019 previstas | pendiente |
| 3 | Auditoría, correlación y logs limpios | Cada evento obligatorio de `docs/observability.md` tiene un test que comprueba su registro; toda respuesta lleva `X-Request-Id`; ningún log contiene datos sensibles. Riesgos: OB1.a, OB1.b, OB1.c, OB1.d, OB1.e, OB1.f, OB1.g, OB1.h, OB2.a, OB2.b, OB3.a, OB3.b, OB4.a, OB5.a, OB5.b, OB5.c, OB6.a; fuera: OB4.b → objetivo 5, OB6.b → objetivo 5 | 020 (OB1.a, OB1.c, OB1.g, OB1.h, OB2); 021–023 previstas | pendiente |
| 4 | Monolito modular con regla de agenda única | La regla de agenda vive en un solo sitio con pruebas propias, la doble reserva es imposible y una prueba de arquitectura vigila los módulos Agenda, Pacientes, Clínica e Identidad. Riesgos: RS12.a, RS12.b, RS12.c, RS15.a; fuera: RS15.b → objetivo 5 | [025](specs/025-agenda-unica/spec.md) (regla de agenda); [026](specs/026-estados-cita/spec.md) (estados); 027 prevista; 013 (parte 3) | pendiente |
| 5 | Staging y producción con rollback | Staging y prod desplegados en una plataforma gratuita (ADR), con backup previo, rollback ensayado en staging y checklist de configuración verificado. Riesgos: RD1.a, RD1.b, RD1.c, RD3.a, RD3.b, RD3.c, RD4.a, RD5.a, RD6.a, RD7.a, RS6.c, RS7.a, RS7.b, RS7.c, RS7.d, RS8.a, RS8.b, RS15.b, OB4.b, OB6.b, OB7.a; fuera: RS6.a, RS6.b y RS6.d → objetivo 1, RS15.a → objetivo 4, OB4.a → objetivo 3, OB6.a → objetivo 3 | — | pendiente |

<!-- Un objetivo que atiende riesgos (RS/OB/RD) cita **cada** corrección del riesgo: las que
     cubre y las que deja fuera con su destino. Nunca resume un riesgo citando solo su número. -->

## Próxima etapa
Para pasar de MVP a "entrega académica": objetivos 1, 2 y 3 cumplidos, staging desplegado
(objetivo 5) y la constitución aprobada.

## Pendientes y deuda
- Dependencias con vulnerabilidades conocidas (riesgo de dependencias en `docs/security.md`,
  detectado en `/plan 014`): spec propia de actualización antes de cualquier release (decisión
  del usuario, 2026-09-28). Se atiende en la spec [024](specs/024-dependencias-seguras/spec.md),
  sin objetivo propio (decisión del usuario, 2026-09-29). Hecho: liberada en v0.1.0 (2026-10-02);
  RS16 mitigado.
- 024/R8: la evidencia de T022 no registra que `integrity` y `resolved` coincidan con el registro
  (comprobado en `/review`: coinciden las 48). En próximas specs de dependencias, compararlos con
  `npm view <p>@<v> dist.integrity dist.tarball` (/review 024, aceptado sin tarea).
- 020 antes de `/release 020` (plan de la 024, Rollout pasos 4 y 5): reconstruir `feature/020-auditoria-base`
  sobre `main` sin sus copias de los commits de la 024 y comprobar que no cambian `docs/specs/024-dependencias-seguras`,
  la fila 024 de `docs/specs/README.md` ni su enlace aquí; después, que el merge de la 024 es ancestro del commit
  que se libera y que `composer audit --ignore-severity=low --ignore-severity=medium` y
  `npm audit --audit-level=high` terminan con código 0, es decir, 0 críticas y 0 altas (decisión del
  usuario, 2026-09-29; comando de la spec 024, D10).
- EX1 (`docs/security.md`, spec 024, D10): actualizar `laravel/framework` a ≥ 12.69.0 y
  `league/flysystem` a ≥ 3.35.3 antes del 2026-10-31. Al vencer, el usuario decide si se actualizan
  en una spec propia o se renueva EX1 con fecha y aprobación nuevas; lo comprueba el primer `/plan`
  o `/release` posterior al 2026-10-31 (decisión del usuario, 2026-09-29).
- Retirar `overrides.rolldown` (fijado en 1.2.9) de `package.json` cuando `rolldown` 1.2.11 o
  posterior lleve al menos 7 días publicada, para que `npm audit fix` pueda volver a actualizarlo
  (spec 024, D2; /review 024 R7).
- `aidlc/spaces/default/intents/260920-monolito-modular/` puede eliminarse cuando el usuario
  revise su migración a la spec 013.
- Aprobar o ajustar las specs inferidas 001–012 (`/specify --edit NNN`).
- Restos del esqueleto de Laravel: `App\Models\User`, tabla `users`, `DatabaseSeeder`,
  `welcome.blade.php`, nombre y README por defecto.
- Formatear el código existente con Pint en un cambio aislado (nunca se ha ejecutado; deriva generalizada).
- Unificar convenciones de rutas (`/crear` vs `/create`, `/edit` vs `/editar`, nombres con y sin `.index`).
- `Route::resource('servicios')` expone `show` sin método; `ServiciosController@index` ignora el buscador.
- Objetivo de accesibilidad sin definir (propuesto WCAG 2.1 AA).
- Deuda de la spec 025 (/review, aceptada sin tarea; liberada en v0.2.0):
  - 025/R8: `SET SESSION innodb_lock_wait_timeout` en `ReservaCitas` deja 5 s fijados en la conexión
    para el resto de la petición; restaurar el valor o usar `SET STATEMENT … FOR` si aparecen procesos
    persistentes.
  - 025/R9: `ReservaCitas::reservar()` convierte `DeadlockException` en no disponible; documentar o
    impedir que se llame dentro de otra transacción.
  - 025/R10: unir los dos tests de bloqueo real de `ReservaConcurrenteTest`. Resuelto en la propia 025
    (T024), sin acción pendiente.
  - 025/R11: extraer los helpers de fixtures de agenda de los 5 tests a un trait común.
  - 025/R12: la búsqueda de solapes empieza en el inicio del día; revisar en la spec 027 si se permiten
    franjas que crucen la medianoche.
  - 025/R13: el test de la espera de 5 s depende del texto del SQL; comprobar el efecto
    (`@@SESSION.innodb_lock_wait_timeout`) si se pasa a binding.
  - 025/R14: el test de orden debería exigir que el bloqueo sea la primera lectura de la transacción.
  - 025/R16: actualizar en el plan de la 025 (TM2 y Trazabilidad) el nombre del test unido en T024.
  - 025/R17: las migraciones de la spec 013 deben crear `idx_citas_dentista` con ese nombre, porque
    `ReservaCitas` lo fuerza (lo cubre `ReservaConcurrenteTest`).
- Enmienda MINOR de P2 (constitución): distinguir los cambios de comportamiento, que exigen un test
  que falle antes, de los tests de caracterización, que se comprueban rompiendo el código que
  protegen. Motivo: P2 necesitó excepción en la spec 024 (CA3–CA7) y en la 025 (CA2, CA3, CA6, CA7)
  (spec 025, D9; decisión del usuario, 2026-09-29).
- Auditoría del alta de citas con notas (dato de salud) → spec 022. Excepción P7 de la spec 025:
  la 025 no cambia qué notas se guardan ni quién (D9; decisión del usuario, 2026-09-30).
- Auditoría del 403 del alta de citas del personal (`autorizadoParaCita`) → spec 020, registro
  central de accesos denegados (OB1.c). Excepción P7 de la spec 025 (D9; decisión del usuario,
  2026-09-30).
- Gate o Policy del alta de citas del paciente (`POST /cliente/agendar`) y rechazo por rol en
  `/cliente/*` → spec 015. Excepción P3 de la spec 025. El alta del personal recibe su Gate en la
  014 (D9; decisión del usuario, 2026-09-30).
- Residuo de SQL con bindings en el log: cualquier `QueryException` de `ReservaCitas` (bloqueo,
  lecturas o insert) distinta de 1205/1213 sigue el reporte por defecto y registra el SQL con sus
  valores, notas incluidas → spec 023 (OB4.a). Excepción P8 de la spec 025 (D9, A38; decisión del
  usuario, 2026-09-30).
- Al retomar `/plan 014 --fix`: su plan añade el Gate `agendar-para-paciente` y el alcance del
  paciente sobre el `CitasController@store` que deja la 025, que ya delega la regla en
  `App\Agenda\ReservaCitas` (spec 025, D7; decisión del usuario, 2026-09-29).
- Límite de peticiones en las rutas de escritura, empezando por el alta de citas (TM6 de la spec
  025: reservas del mismo dentista esperan en serie hasta 5 s y `/cliente/agendar` no tiene
  límite) → spec propia (spec 025, D13; decisión del usuario, 2026-09-30).
- Spec 013 (prueba de arquitectura, CA10): debe permitir que `App\Agenda\ReservaCitas` bloquee la
  fila de `personal` (tabla de Identidad) o migrar ese acceso a una interfaz de Identidad para
  bloquear a un dentista (spec 025, D11, A27).
