# Changelog

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y
[SemVer](https://semver.org/lang/es/). Lo mantiene `/release`.

## [Unreleased]

### Added
- Flujo de desarrollo AI-DD inicializado con `/init`: `AGENTS.md`, constitución, arquitectura,
  despliegue, seguridad, observabilidad, roadmap, specs inferidas 001–012 y validador `.ai/bin/aidd.py`.
- Workflow de CI `.github/workflows/ai-dd.yml` (validador, tests, Pint y escáneres de seguridad).

### Changed
- AI-DLC en pausa: `.claude/CLAUDE.md` movido literalmente a `AIDLC.md`; `.claude/rules/aidlc.md`
  y los hooks/statusLine de `.claude/settings.json` movidos a `aidlc/paused/`
  (decisión del usuario, 2026-09-28).
- La intención AI-DLC `260920-monolito-modular` se migró como spec draft 013.
