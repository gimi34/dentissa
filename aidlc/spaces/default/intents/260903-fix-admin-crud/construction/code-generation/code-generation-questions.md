# Preguntas — Generación de código

**Etapa:** code-generation
**Intención:** 260903-fix-admin-crud
**Profundidad:** Minimal
**Fecha:** 2026-09-03 · **Revisado:** 2026-09-14 (bucle de corrección 1 de 3)

---

## Plan Approval

> **Reabierto por segunda vez — reinicio del intento de la etapa.** Aprobaste
> este mismo plan a las 02:2x y el código de los pasos 13 a 17 YA ESTÁ ESCRITO y
> verificado: suite completa `OK (15 tests, 65 assertions)`, comando acotado
> `OK (13 tests, 62 assertions)`, y las nueve pruebas de dominio nuevas fallan
> contra el código con el defecto. Nada de eso se ha perdido ni se va a
> regenerar.
>
> El motivo del reinicio es de mecánica, no de contenido: la revisión de
> arquitectura terminó con veredicto READY, pero al ejecutar la suite para
> fundamentar su juicio, Laravel reescribió archivos de runtime
> (`.phpunit.result.cache`, el log, los archivos de sesión) que entran en la
> huella del código fuente a la que esa revisión quedó fijada. El recibo no se
> pudo cerrar y el presupuesto de revisión advisory es de una sola pasada.
> Reiniciar el intento devuelve esa pasada. La revisión se repetirá sin ejecutar
> nada que escriba en el árbol; la evidencia de las pruebas ya está verificada de
> forma independiente.
>
> El plan y las instrucciones de prueba son **byte a byte los mismos** que
> aprobaste, salvo la sección `## Review` de la pasada anterior, que se ha
> retirado porque el salto la invalidó.

Esta pregunta cubre los dos documentos que autorizan la generación:

- `code-generation-plan.md` — los 12 pasos originales sin cambios, más los pasos
  13 a 17 que añade este bucle, el `## Testing Contract` incrustado sin
  modificar (su `contract_sha256` no ha cambiado), la sección «Cobertura de
  pruebas — qué cambia en este bucle de corrección» y la tabla de archivos
  ampliada.
- `unit-test-instructions.md` — la configuración de pruebas nueva (base MariaDB
  `dentissa_test` cargada desde el volcado, trait `DatabaseTransactions`), el
  comando acotado ampliado a los tres archivos de prueba, las doce pruebas
  automáticas y la lista de comprobación manual de 19 puntos, que se conserva.

Lo que cambia respecto a la aprobación anterior:

- **Se levanta una base MariaDB de pruebas** (`dentissa_test`) en el contenedor
  `db` que ya existe, cargada desde `database/dentissa.sql`. `phpunit.xml` deja
  de apuntar a SQLite en memoria. Esto es el arreglo **A** que elegiste.
- **Se escriben ocho pruebas de dominio nuevas** para TD-8 (el precio sobrevive
  a una edición) y TD-16 (el rol `asistente` se conserva, el correo duplicado se
  rechaza, la baja lógica no borra el expediente). Eran las regresiones que el
  plan anterior declaraba imposibles de automatizar.
- **Se corrige `tests/Feature/ExampleTest`** para que afirme la redirección real
  a `/login` en vez de un 200 que esta aplicación nunca devolvió. Es el arreglo
  **C**.

Dos puntos que conviene mirar antes de aprobar:

1. **Esto reabre la decisión Q4 = D.** La restricción **C2** de `requirements.md`
   y su § 6 «Fuera de alcance» dicen literalmente que esta corrección no añade
   pruebas automáticas. Tu elección de `Retry with fix` con el arreglo A + C las
   contradice de forma deliberada. Si prefieres actualizar `requirements.md`
   antes de generar código, elige **Request Changes**.
2. **La restricción C1 sigue intacta.** No se escriben migraciones del dominio,
   no se toca `database/dentissa.sql` ni `database/migrations/`, y no hay ningún
   cambio de esquema. La base de pruebas es una copia cargada del esquema que ya
   existe. La opción B (migraciones) se descartó precisamente por chocar con C1.

[Approval Fingerprint]: sha256:0f29a8cbbb484a50c699d1d450316749d617e967e34ef047d98ba5c94b84a564

- Approve Plan
- Request Changes

[Answer]: Approve Plan
