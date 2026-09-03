# Preguntas — Generación de código

**Etapa:** code-generation
**Intención:** 260903-fix-admin-crud
**Profundidad:** Minimal
**Fecha:** 2026-09-03

---

## Plan Approval

Esta pregunta cubre los dos documentos que autorizan la generación:

- `code-generation-plan.md` — 12 pasos de implementación, la tabla de archivos a
  tocar, el `## Testing Contract` incrustado sin modificar, y la sección
  «Brecha de cobertura de pruebas».
- `unit-test-instructions.md` — el marco de pruebas, el comando acotado a esta
  corrección, el alcance de las cuatro pruebas automáticas y la lista de
  comprobación manual de 19 puntos.

Punto que conviene mirar antes de aprobar: el contrato de pruebas del alcance
`bugfix` pide una regresión dirigida al fallo. Solo puedo automatizar las de
TD-11 y TD-12 (son de tabla de rutas). Las de TD-8 y TD-16 necesitan las tablas
del dominio, que no existen fuera de `database/dentissa.sql` — la restricción C1
y la decisión Q4 = D. Quedan cubiertas por la lista manual. El plan lo declara
abiertamente en lugar de rebajar el objetivo.

[Approval Fingerprint]: sha256:99c18aba1b3ff9ed80b3b2b871e3f972f7c5b2a11ec420abc579df60497391c9

- Approve Plan
- Request Changes

[Answer]: Approve Plan
