---
id: 0001
status: accepted
date: 2026-09-28
---

# ADR 0001 · Registrar decisiones arquitectónicas mediante ADRs

## Contexto
Dentissa lo desarrolla un equipo académico de varias personas, con decisiones repartidas entre
commits, conversaciones y los artefactos de AI-DLC (ahora en pausa). No hay un lugar único donde
consultar por qué el sistema es como es.

## Decisión
Toda decisión arquitectónica relevante (framework, base de datos, dependencia nueva, patrón que
contradiga `docs/architecture.md`) se registra como ADR en `docs/adr/NNNN-<slug>.md` usando
`docs/templates/adr.md`. Los ADR aceptados no se editan: se sustituyen por otro que los reemplace.

## Alternativas consideradas
- Documentar solo en `docs/architecture.md` — pierde el contexto y las alternativas descartadas.
- Seguir con los artefactos de AI-DLC — el flujo está pausado (ver `AIDLC.md`).

## Consecuencias
- Positivas: historial de decisiones consultable por el equipo y por los agentes.
- Negativas: coste pequeño de escribir cada ADR.
