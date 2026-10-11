# Specs de Dentissa

Estado de todas las specs: `python .ai/bin/aidd.py status`.

**Specs inferidas** (`status: inferred`): documentan lo que el código **hace hoy**, no lo que
debería hacer. En sus criterios, `[x]` significa "cubierto por un test existente" y `[ ]`
"comportamiento observado en el código, sin test". Los casos de abuso que hoy no se cumplen llevan
la marca **HOY NO SE CUMPLE** y son el punto de partida de specs de corrección. Pueden citar rutas
y detalles técnicos. Pasan a `approved` solo con confirmación explícita del usuario
(`/specify --edit NNN`).

## Inventario
| # | Spec | Dominio | Estado | Confianza | Tests |
|---|---|---|---|---|---|
| 001 | [autenticacion](001-autenticacion/spec.md) | Identidad | inferred | alta | 2 de 11 criterios |
| 002 | [registro-pacientes](002-registro-pacientes/spec.md) | Identidad | inferred | alta | sin tests |
| 003 | [agenda-citas-personal](003-agenda-citas-personal/spec.md) | Agenda | inferred | alta | sin tests |
| 004 | [citas-cliente](004-citas-cliente/spec.md) | Agenda | inferred | alta | sin tests |
| 005 | [horarios-dentista](005-horarios-dentista/spec.md) | Agenda | inferred | alta | sin tests |
| 006 | [pacientes](006-pacientes/spec.md) | Pacientes | inferred | alta | sin tests |
| 007 | [expedientes-clinicos](007-expedientes-clinicos/spec.md) | Clínica | inferred | alta | sin tests |
| 008 | [personal](008-personal/spec.md) | Identidad | inferred | alta | sin tests |
| 009 | [servicios](009-servicios/spec.md) | Agenda | inferred | alta | 5 de 7 criterios |
| 010 | [usuarios-admin](010-usuarios-admin/spec.md) | Identidad | inferred | alta | 7 de 9 criterios |
| 011 | [perfil-cliente](011-perfil-cliente/spec.md) | Identidad | inferred | alta | sin tests |
| 012 | [paneles-por-rol](012-paneles-por-rol/spec.md) | Transversal | inferred | alta | sin tests |
| 013 | [monolito-modular](013-monolito-modular/spec.md) | Transversal | draft | — | — (migrada de AI-DLC) |
| 014 | [autorizacion-alcance-clinico](014-autorizacion-alcance-clinico/spec.md) | Pacientes / Clínica | approved | — | — (2026-09-28) |
| 020 | [auditoria-base](020-auditoria-base/spec.md) | Transversal | approved | — | — (2026-09-28) |
| 024 | [dependencias-seguras](024-dependencias-seguras/spec.md) | Transversal | released (v0.1.0) | — | — (2026-09-29) |
| 025 | [agenda-unica](025-agenda-unica/spec.md) | Agenda | released (v0.2.0) | — | — (2026-09-29) |
| 026 | [estados-cita](026-estados-cita/spec.md) | Agenda | released (v0.3.0) | — | — (2026-10-02) |
| 027 | [horarios-validos](027-horarios-validos/spec.md) | Agenda | released (v0.4.0) | — | — (2026-10-02) |
| 028 | [reprogramar-citas](028-reprogramar-citas/spec.md) | Agenda | implemented | — | — (2026-10-11) |
| 029 | [dependencias-al-dia](029-dependencias-al-dia/spec.md) | Transversal | released (v0.4.1) | — | — (2026-10-03) |
| 030 | [despliegue-servidor-propio](030-despliegue-servidor-propio/spec.md) | Transversal | released (v0.5.0) | — | — (2026-10-04) |

Specs previstas (números reservados; se crean con `/specify` una por una):
- Objetivo 2: 015 portal-paciente-aislado · 016 sesiones-seguras · 017 limite-intentos-acceso ·
  018 vinculacion-google-segura · 019 credenciales-generadas.
- Objetivo 3: 021 auditoria-accesos-cuentas · 022 auditoria-datos-clinicos · 023 logs-y-errores.
- Objetivo 4 (agenda): 027 horarios-reprogramar (tras la 025; decisión del
  usuario, 2026-09-29).

## Funcionalidades no documentadas como spec
Existen como placeholder o no están implementadas; se especifican con `/specify` cuando se decida
construirlas:
- **Reportes** (`/admin/reportes`): vista "en construcción".
- **Recuperar contraseña**: enlace `href="#"` en el login; la tabla `password_reset_tokens` existe sin rutas.
- **Recordatorios de cita**: `citas.recordatorio_enviado` se lee pero nunca se escribe.
- **Adjuntos del expediente**: `expedientes.archivos_url` siempre se guarda como `null`.
- `welcome.blade.php`: código muerto del esqueleto de Laravel.

## Specs previas en otros formatos (AI-DLC, en pausa)
| Origen | Estado | Destino |
|---|---|---|
| `aidlc/spaces/default/intents/260920-monolito-modular/` | en curso (3/26 etapas) | migrada a [013](013-monolito-modular/spec.md) (draft), originales en `referencias/` |
| `aidlc/spaces/default/intents/260903-fix-admin-crud/` | completa (commit `4a24b5e`) | ya implementada; su comportamiento está documentado en 009 y 010 |
| `aidlc/spaces/default/intents/260902-docs-sistema-dental/` | completa (solo ingeniería inversa) | insumo del codekb; no genera spec |
| `aidlc/spaces/default/codekb/dentissa/*.md` | desfasado (commit `9fcff4c`) | referencia histórica; esta documentación lo sustituye |
