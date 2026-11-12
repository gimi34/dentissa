---
lane: fix
status: released             # open → done → released · escalated (→ escalated_to)
released: v0.17.0 (2026-11-12)
base: 2810575                # commit de partida; lo registra la skill al empezar (lo usa lane-check)
relates: [041]               # specs cuyo comportamiento toca ([] si ninguna)
covers: []                   # fix: criterios de esas specs que el arreglo vuelve a cumplir
escalated_to: null           # NNN de la spec si el cambio se escaló al flujo completo
---

# Fix: tests de la recuperación de contraseña que fallan a veces en la suite completa

## Problema
Dos tests de la spec 041 fallan de vez en cuando en la suite completa y pasan en aislado:
- `RecuperarContrasenaTest::test_una_cuenta_activa_recibe_el_enlace`, con «The expected
  [App\Mail\EnlaceRestablecimiento] mailable was not sent» (`tests/Feature/RecuperarContrasenaTest.php:167`);
- `RecuperarContrasenaVistasTest::test_el_foco_va_al_primer_campo_con_error`.

Se vieron en la `/review` de la 039: en la ronda 2, y en la ronda 3 en dos de tres pasadas, con 2 y con
1 fallo. En aislado no fallan: 0 de 25 repeticiones. Ponen la CI en rojo al azar.

Pista: a la hora del fallo de la ronda 3 (2026-11-10 04:49:47 UTC), `laravel.log` registra
`restablecimiento.correo_fallido` con `{"error":"QueryException"}`. El trabajo que envía el enlace,
que corre después de la respuesta, falló en una consulta; el controlador lo captura y solo registra la
clase (TM6), así que el correo no sale.

## Reproducción
El fallo no depende del orden de los tests sino de **otra suite contra la misma base** `dentissa_test`.
A la hora del fallo de la ronda 3 (04:49:47 UTC), el clon interior `dentissa/` estaba corriendo su
propia suite: su `laravel.log` tiene entradas `testing.*` de 04:47 a 04:52, solapadas con la suite de
`dentissa-041` (04:46 a 04:51). Las dos usan el mismo servidor MariaDB y la misma base.

Reproducción manual, determinista:
1. Una segunda conexión abre una transacción, borra e inserta una fila en `password_reset_tokens` y la
   deja abierta 70 s, como un test de la otra suite antes de su rollback.
2. Mientras, `php artisan test --filter=test_una_cuenta_activa_recibe_el_enlace`.
3. Falla igual que en la review: «The expected [App\Mail\EnlaceRestablecimiento] mailable was not sent»
   (50,67 s). Con una traza temporal (retirada) en el `catch` del trabajo, el mensaje es
   `SQLSTATE[HY000]: General error: 1205 Lock wait timeout exceeded … insert into password_reset_tokens`.

Test que prueba el arreglo: `tests/Feature/SuiteExclusivaTest.php`, que desde otra conexión intenta
reservar la base de tests mientras la suite corre. Antes del arreglo: «Otra suite no consigue el candado
de la base de tests mientras esta corre. Failed asserting that 1 is identical to 0.»

## Causa raíz
Cada test con `DatabaseTransactions` guarda sus candados de InnoDB hasta el rollback del final. Con la
tabla de tokens casi vacía, el `DELETE` + `INSERT` del broker (`DatabaseTokenRepository::create`) toma un
candado de hueco que abarca toda la tabla, así que dos suites que piden enlaces a la vez chocan siempre:
la segunda espera hasta el «Lock wait timeout» (1205) o cae en un interbloqueo (1213). El trabajo que
envía el enlace corre tras la respuesta y captura la excepción (TM6), así que el test solo ve «el correo
no salió». En aislado o en la CI (una sola suite) no pasa nunca.

Nada impedía dos suites a la vez: las sesiones en paralelo de este proyecto corren la suite en clones
distintos (o con `docker run` sobre la red de Compose) contra el mismo servidor y la misma base. El código
de la aplicación es correcto: en producción no hay suites.

## Cambio
`tests/TestCase.php`: al preparar el primer test, la suite abre una conexión propia y pide
`GET_LOCK('dentissa:suite:<base>')`. Si otra suite la tiene, avisa por stderr («Otra suite está usando
dentissa_test; esta espera a que termine.») y espera hasta 20 minutos; pasado ese tiempo, el test falla con
un mensaje claro. El servidor suelta el candado al cerrarse la conexión, también si la suite se
interrumpe. Sin conexión `mariadb` o sin base alcanzable no hace nada. Ningún cambio en `app/`.

## Escalado
`lane-check` al empezar (controlador y test previstos) y sobre el diff real desde `2810575`
(`tests/TestCase.php`, `tests/Feature/SuiteExclusivaTest.php` y esta ficha): «Escalar: no. El cambio cabe
en el carril.» 0 archivos de código de la aplicación.

## Verificación
- Línea base (`2810575`, con la traza temporal puesta): 909 pasan y 2 fallan, `CorreosRestablecimientoTest`
  y `RecuperarContrasenaAbusoTest`, ambos por la traza, que escribía el correo en el log (TM6). Sin la
  traza, la línea base es 911.
- `SuiteExclusivaTest`: falla sin el arreglo («Failed asserting that 1 is identical to 0») y pasa con él.
- Dos suites a la vez (`RecuperarContrasenaTest` y `RecuperarContrasenaVistasTest`): la segunda avisa
  «Otra suite está usando dentissa_test; esta espera a que termine» y pasan las dos (18 y 19).
- Suite completa con el arreglo: 912 pasan (13 999 aserciones), los 911 de la línea base más el nuevo; de
  nuevo 912 tras corregir el menor (c) de la review.
- Pint sobre `tests/TestCase.php` y `tests/Feature/SuiteExclusivaTest.php`: PASS.
- Review ligera (subagente `standard`): sin bloqueantes. Revertido el arreglo, el test falla; restaurado,
  pasa. Lo comprobó también en una base vacía temporal, porque una suite ajena tomó el candado entre dos
  de sus pasadas. La causa explica el síntoma y el arreglo ataca la causa. Menores:
  - (c) Si la espera de 20 min vencía, cada test siguiente volvía a esperar: **corregido**, un indicador
    estático hace fallar al resto en seguida.
  - (a), (b) y `wait_timeout`: aceptados, ver «Decisiones».

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-10 | implícita | No lo define ninguna spec: un test que falla al azar es un defecto evidente | Se arregla el entorno de tests, no la app: el comportamiento de la 041 ya se cumplía | el síntoma y la reproducción |
| 2026-11-10 | supuesto | ¿Separar una base por clone o serializar las suites? | Serializar con un candado del servidor: no cambia `phpunit.xml`, el arnés ni la CI, y vale también para `docker run` sobre la red de Compose | — |
| 2026-11-10 | supuesto | La otra suite, ¿espera o falla en seguida? | Espera hasta 20 min con aviso por stderr: las sesiones en paralelo no tienen que repetir nada | — |
| 2026-11-10 | supuesto | `ContrasenaDistintaTest::test_tras_el_rechazo_el_enlace_sigue_sirviendo` (044/R23, spec 043) también falló una vez en la suite completa | Muy probablemente la misma causa (fija la contraseña con `DELETE`/`INSERT` en la misma tabla); queda cubierto por el mismo candado y R23 se cierra con esta ficha | — |
| 2026-11-10 | supuesto | En los tests, `Application::terminate()` no vacía los callbacks: cada petición vuelve a ejecutar los trabajos `afterResponse` anteriores del mismo test (los `TransportException` salen dos veces en el log) | No es la causa y no afecta a producción (cada petición es su proceso); no se toca | lectura de `vendor/laravel/framework` |
| 2026-11-10 | supuesto | Review (a): `database/testing/bootstrap-test-db.sh` recarga `dentissa_test` sin pedir el candado | Aceptado: es un paso manual y poco frecuente (preparar la base), no una suite; no se corre con otra suite en marcha | — |
| 2026-11-10 | supuesto | Review (b): el candado solo protege si todas las suites llevan este código | Aceptado: los clones y ramas lo reciben al fusionar `main` | — |
| 2026-11-10 | supuesto | Review: si una suite superase `wait_timeout` (8 h), el servidor soltaría el candado | Aceptado: la suite tarda unos 7 min | — |
