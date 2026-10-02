# Changelog

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y
[SemVer](https://semver.org/lang/es/). Lo mantiene `/release`.

## [Unreleased]

## [0.1.0] - 2026-09-29

### Added
- Prueba automática que impide volver a versiones vulnerables de las dependencias corregidas
  (spec 024).
- Flujo de desarrollo AI-DD inicializado con `/init`: `AGENTS.md`, constitución, arquitectura,
  despliegue, seguridad, observabilidad, roadmap, specs inferidas 001–012 y validador `.ai/bin/aidd.py`.
- Workflow de CI `.github/workflows/ai-dd.yml` (validador, tests, Pint y escáneres de seguridad).

### Changed
- AI-DLC en pausa: `.claude/CLAUDE.md` movido literalmente a `AIDLC.md`; `.claude/rules/aidlc.md`
  y los hooks/statusLine de `.claude/settings.json` movidos a `aidlc/paused/`
  (decisión del usuario, 2026-09-28).
- La intención AI-DLC `260920-monolito-modular` se migró como spec draft 013.
- Las comprobaciones de seguridad del CI auditan el lockfile de Composer y solo bloquean por
  vulnerabilidades críticas o altas, como ya hacía la de npm (spec 024).

### Fixed
- El análisis estático del CI dejaba de ejecutarse por un conjunto de reglas inexistente
  (spec 024).

### Security
- Dependencias del servidor y del frontend actualizadas a versiones sin vulnerabilidades
  críticas ni altas conocidas, entre ellas el cliente HTTP que usa el inicio de sesión con Google
  (spec 024).
- Dos avisos de severidad baja publicados el mismo día quedan registrados como excepción temporal,
  con vencimiento el 2026-10-31 (spec 024).
