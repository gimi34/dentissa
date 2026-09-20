# Puerta de cobertura final — Corrección del CRUD de administración

**Intención**: `260903-fix-admin-crud`
**Etapa**: build-and-test (Construction), paso 10 · **Alcance**: bugfix
**Fecha**: 2026-09-19 (reentrada tras el bucle de corrección 1)

Esta es una puerta de ámbito de etapa, no la frontera de fase de Construction.

## Veredicto

**PASA.** Los 31 requisitos enumerados en
`../../inception/requirements-analysis/requirements.md` están cubiertos con
estado `OK` en `../code-generation/traceability.json`, y el archivo destino de
cada uno existe en disco. **Ningún elemento queda sin cubrir.**

## Qué se enumeró

- **Requisitos funcionales y no funcionales**: los 26 subrequisitos `FR` y los 5
  `NFR` de `requirements.md`. Las cinco cabeceras de grupo (`FR1`…`FR5`) no son
  requisitos verificables por sí mismas: quedan cubiertas por composición, ya
  que todos sus subrequisitos lo están.
- **Criterios de aceptación de tres segmentos** (`AC<n>.<m>.<seq>`): ninguno que
  enumerar. El alcance `bugfix` salta `user-stories`, así que ese artefacto no
  existe y no hay criterios que rastrear. No es una laguna, es diseño del
  alcance.

## Qué se leyó

Sin unidades de trabajo: el alcance saltó `units-generation`, así que no hay
archivos de trazabilidad por unidad. La única fuente es el archivo de ámbito de
etapa `../code-generation/traceability.json`, con 31 filas de cobertura y 0
entradas inversas.

## Cobertura por elemento

| ID | Estado | Archivo destino | Existe |
|---|---|---|---|
| FR1.1 | OK | `routes/web.php` | Sí |
| FR1.2 | OK | `routes/web.php` | Sí |
| FR1.3 | OK | `routes/web.php` | Sí |
| FR1.4 | OK | `routes/web.php` | Sí |
| FR2.1 | OK | `resources/views/servicios/create.blade.php` | Sí |
| FR2.2 | OK | `resources/views/servicios/edit.blade.php` | Sí |
| FR2.3 | OK | `resources/views/servicios/create.blade.php` | Sí |
| FR2.4 | OK | `app/Http/Controllers/ServiciosController.php` | Sí |
| FR2.5 | OK | `app/Http/Controllers/ServiciosController.php` | Sí |
| FR2.6 | OK | `app/Http/Controllers/ServiciosController.php` | Sí |
| FR2.7 | OK | `app/Http/Controllers/ServiciosController.php` | Sí |
| FR3.1 | OK | `app/Http/Controllers/UsuarioController.php` | Sí |
| FR3.2 | OK | `app/Http/Controllers/UsuarioController.php` | Sí |
| FR3.3 | OK | `app/Http/Controllers/UsuarioController.php` | Sí |
| FR3.4 | OK | `resources/views/usuarios/create.blade.php` | Sí |
| FR3.5 | OK | `resources/views/usuarios/create.blade.php` | Sí |
| FR3.6 | OK | `app/Http/Controllers/UsuarioController.php` | Sí |
| FR3.7 | OK | `resources/views/usuarios/create.blade.php` | Sí |
| FR4.1 | OK | `app/Http/Controllers/UsuarioController.php` | Sí |
| FR4.2 | OK | `routes/web.php` | Sí |
| FR4.3 | OK | `resources/views/usuarios/index.blade.php` | Sí |
| FR4.4 | OK | `resources/views/usuarios/index.blade.php` | Sí |
| FR4.5 | OK | `routes/web.php` | Sí |
| FR4.6 | OK | `app/Http/Controllers/UsuarioController.php` | Sí |
| FR5.1 | OK | `../code-generation/unit-test-instructions.md` | Sí |
| FR5.2 | OK | `phpunit.xml` | Sí |
| NFR1 | OK | `app/Http/Controllers/UsuarioController.php` | Sí |
| NFR2 | OK | `app/Http/Controllers/UsuarioController.php` | Sí |
| NFR3 | OK | `app/Http/Controllers/UsuarioController.php` | Sí |
| NFR4 | OK | `app/Http/Controllers/UsuarioController.php` | Sí |
| NFR5 | OK | `app/Http/Controllers/ServiciosController.php` | Sí |

**31 de 31.** Comprobado por script en esta etapa: ningún ID sin cubrir, ningún
estado distinto de `OK`, ningún destino inexistente, y ninguna fila de
trazabilidad que apunte a un ID que no esté en `requirements.md`.

## Etapa y unidad responsables

Todas las filas pertenecen a `code-generation`, de ámbito de etapa (sin
unidades). Esta etapa aporta la verificación en ejecución que cierra cada
destino: 20 pruebas propias de la corrección, todas en verde y todas
verificadas como no complacientes contra el código con el defecto.

## Elementos sin cubrir

**Ninguno.** No hay hallazgos de cobertura que llevar a la puerta de aprobación.

## Una nota honesta sobre lo que «cubierto» significa aquí

Una fila `OK` afirma que el requisito tiene un archivo destino que lo
implementa, y que ese archivo existe. No afirma que cada requisito tenga una
prueba automática dedicada, y la estrategia Minimal no lo pide: pide una prueba
verificable por requisito al nivel más estrecho, más un piso de camino feliz.

La relación real entre requisitos y pruebas queda así:

- **Cubiertos por prueba automática directa**: FR1.1-FR1.4, FR2.4-FR2.7,
  FR3.2-FR3.6, FR4.1, FR4.2, FR4.5, FR4.6, FR5.2 y los cinco NFR.
- **Cubiertos por prueba automática indirecta** (la prueba ejercita la vista que
  los implementa, sin afirmar sobre su apariencia): FR2.1-FR2.3, FR3.1, FR3.7,
  FR4.3, FR4.4.
- **Cubierto por entrega, no por ejecución**: FR5.1, la lista de verificación
  manual. La etapa la entrega; recorrerla contra el navegador le corresponde al
  humano.
