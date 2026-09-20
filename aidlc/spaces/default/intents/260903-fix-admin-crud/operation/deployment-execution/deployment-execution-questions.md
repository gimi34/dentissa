# Preguntas — Ejecución del Despliegue

**Intención**: `260903-fix-admin-crud`
**Etapa**: deployment-execution (Operation) · **Alcance**: bugfix · **Profundidad**: Minimal
**Fecha**: 2026-09-19

---

## Comprobaciones previas: ya ejecutadas, no preguntadas

El cuerpo de la etapa pide preguntar por las comprobaciones previas al
despliegue. Las he ejecutado en vez de preguntarlas, porque son verificables:

| Comprobación | Resultado | Cómo se verificó |
|---|---|---|
| ¿Pasan todas las comprobaciones previas? | **Sí.** `OK (22 tests, 108 assertions)`, `npm run build` salida 0 | Etapa Build and Test, ejecutada por el conductor |
| ¿Hacen falta migraciones? | **No. Ninguna pendiente** | `php artisan migrate:status`: las 6 migraciones figuran como `Ran` |
| ¿Los servicios dependientes están sanos? | **Sí.** Los cuatro en `running` | `docker compose ps` |
| ¿Cuál es la ventana de despliegue? | **No aplica.** Destino local, sin usuarios concurrentes | Respuestas Q1 = A y Q3 = A del Pipeline de Despliegue; supuesto A1 de los requisitos |

**Un hallazgo que corrigió el runbook.** Al probar de verdad la orden de
respaldo que yo mismo había escrito, falló:
`Access denied for user 'root'@'localhost' (using password: NO)`. Le faltaba
`-proot`, y además usaba `--all-databases`, que arrastra las 233 tablas de
sistema de MariaDB y cuya restauración sobreescribiría la tabla de usuarios del
motor. `rollback-runbook.md` ya está corregido y la orden nueva está verificada:
39 kB, 18 tablas, las 2 vistas SQL.

---

## Q1. El árbol de trabajo tiene 29 cambios sin confirmar. ¿Qué hago con ellos?

Este es el punto donde «desplegar» y «confirmar el trabajo» se tocan. La
práctica de la organización es desarrollo sobre tronco con ramas cortas
fusionadas a `main`, pero ahora mismo la corrección vive sin confirmar
directamente sobre `main` (commit actual `210d5bd`).

No voy a confirmar nada sin que lo pidas.

- A. **Confirmar sobre `main`** con un mensaje que describa la corrección. Es lo más simple y coherente con el destino local.
- B. **Rama corta y fusión con aplastado** a `main`, que es la práctica escrita en `org.md`.
- C. **No confirmar nada.** Tú decides cuándo y cómo; esta etapa solo despliega y verifica.
- X. Other (please specify)

[Answer]: C

## Q2. ¿Ejecuto el despliegue ahora contra esta máquina?

El destino es esta misma máquina y el código ya está montado en el contenedor,
así que «desplegar» aquí significa: limpiar las cachés de Laravel y verificar
que la aplicación responde correctamente.

- A. **Sí, ejecútalo ahora** y registra lo que salga.
- B. **No lo ejecutes.** Deja el procedimiento documentado y lo corro yo.
- X. Other (please specify)

[Answer]: A

## Q3. La comprobación de humo tiene 4 puntos de navegador. ¿Cómo los verifico?

De los cinco puntos de humo definidos en `deployment-strategy.md`, el quinto
—ejecutar la suite— lo puedo correr sin más. Los cuatro primeros son pantallas
que hay que abrir en un navegador con sesión de administrador.

- A. **Ejecuta el punto 5 y deja los otros cuatro para mí**, documentados como pendientes de verificación humana.
- B. **Condúcelos tú en el navegador** y registra lo que veas. Necesito darte acceso al navegador y credenciales de administrador.
- C. **Sustitúyelos por comprobaciones HTTP automáticas** que se aproximen a lo mismo sin navegador.
- X. Other (please specify)

[Answer]: B

---

## Consolidated Summary Confirmation

**¿Está todo correcto antes de que genere el artefacto?**

- Looks correct
- Request changes

[Answer]: Looks correct
