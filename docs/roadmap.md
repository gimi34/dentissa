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
| 4 | Monolito modular con regla de agenda única | La regla de agenda vive en un solo sitio con pruebas propias, la doble reserva es imposible y una prueba de arquitectura vigila los módulos Agenda, Pacientes, Clínica e Identidad. Riesgos: RS12.a, RS12.b, RS12.c, RS15.a; fuera: RS15.b → objetivo 5 | 013 (partes 2 y 3) | pendiente |
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
  sin objetivo propio (decisión del usuario, 2026-09-29).
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
- `aidlc/spaces/default/intents/260920-monolito-modular/` puede eliminarse cuando el usuario
  revise su migración a la spec 013.
- Aprobar o ajustar las specs inferidas 001–012 (`/specify --edit NNN`).
- Restos del esqueleto de Laravel: `App\Models\User`, tabla `users`, `DatabaseSeeder`,
  `welcome.blade.php`, nombre y README por defecto.
- Formatear el código existente con Pint en un cambio aislado (nunca se ha ejecutado; deriva generalizada).
- Unificar convenciones de rutas (`/crear` vs `/create`, `/edit` vs `/editar`, nombres con y sin `.index`).
- `Route::resource('servicios')` expone `show` sin método; `ServiciosController@index` ignora el buscador.
- Objetivo de accesibilidad sin definir (propuesto WCAG 2.1 AA).
