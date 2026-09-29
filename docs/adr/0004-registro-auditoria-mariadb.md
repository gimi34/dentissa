---
id: 0004
status: accepted
date: 2026-09-28
---

# ADR 0004 · Registro de auditoría en MariaDB con triggers de inmutabilidad

## Contexto
La constitución (P7) y la spec 020 exigen un registro de auditoría solo-anexado, con retención de
5 años, que ni siquiera un admin pueda alterar, y un identificador de petición que enlace eventos y
logs. El proyecto no tiene presupuesto para servicios externos y usa una única MariaDB.

## Decisión
- Tabla `auditoria` en la misma base, creada por migración, con `id` autoincremental.
- Inmutabilidad en dos capas: la aplicación no ofrece rutas de modificación, y dos triggers
  rechazan `UPDATE` siempre y `DELETE` salvo cuando la sesión activa la variable
  `@auditoria_purga` y el evento tiene más de 5 años.
- Purga mensual con el comando `auditoria:purgar` y un servicio `scheduler` en Docker Compose.
- Un único servicio `App\Auditoria\Auditoria` para registrar, con modo crítico (fallo cerrado)
  para acciones sobre datos clínicos.
- Correlación con `X-Request-Id` y el `Context` de Laravel.

## Alternativas consideradas
- Usuario de BD con solo `INSERT`/`SELECT` para la auditoría: más robusto frente a quien tenga la
  conexión de la app, pero exige una segunda conexión y gestionar permisos por entorno; se
  descarta por ahora (decisión del usuario, 2026-09-28).
- Servicio externo o archivo de log: sin presupuesto; un archivo no permite la consulta filtrada
  que pide la spec.
- Solo la aplicación, sin triggers: no cumple la inmutabilidad en la base decidida por el usuario.

## Consecuencias
- Positivas: sin dependencias nuevas; consultable desde la app; inmutable frente a errores y
  frente a la propia aplicación.
- Negativas: alguien con privilegios de administrador de la BD puede eliminar los triggers o usar
  `TRUNCATE`; crear triggers puede requerir privilegios especiales en staging/prod; la purga
  depende de que cada entorno ejecute el programador de tareas.
