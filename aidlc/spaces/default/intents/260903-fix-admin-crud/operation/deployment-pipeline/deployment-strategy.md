# Estrategia de despliegue — Corrección del CRUD de administración

**Intención**: `260903-fix-admin-crud`
**Etapa**: deployment-pipeline (Operation) · **Alcance**: bugfix · **Profundidad**: Minimal
**Fecha**: 2026-09-19

---

## Estrategia elegida: recreate

**Parar los servicios, actualizar el código, volver a levantar.** Sin
solapamiento de versiones, sin desvío de tráfico, sin infraestructura duplicada.

En la práctica ni siquiera hace falta parar nada para los cambios de PHP: el
código va montado desde el host (`docker-compose.yml` monta `.:/var/www`), así
que un `git pull` lo hace visible al instante. El reinicio solo es necesario si
cambiara el `Dockerfile` o `docker-compose.yml`, que no es el caso aquí.

## Por qué recreate, y no otra cosa

Las alternativas del manual presuponen infraestructura que este proyecto no
tiene. Decirlo explícitamente es más útil que fingir que se evaluaron en serio:

| Estrategia | Por qué no aquí |
|---|---|
| **Azul/verde** | Exige dos entornos idénticos y un balanceador que conmute el tráfico. Hay un `nginx` y un puerto. Duplicar la pila para una corrección que no toca el esquema sería coste sin cobertura |
| **Canario** | Exige repartir tráfico por porcentaje y métricas automáticas que decidan si seguir o abortar. No hay observabilidad instrumentada: `observability-setup` está fuera de este alcance. Sin métricas, un canario es una estrategia azul/verde con más pasos |
| **Actualización progresiva** | Exige varias réplicas del servicio de aplicación. Hay una, `dentissa_app` |
| **Banderas de funcionalidad** | Exigen un servicio de configuración y un ciclo de limpieza de banderas obsoletas. Para una corrección de cuatro defectos que se quiere activa de inmediato, una bandera sería deuda desde el primer día |

**El criterio que decide.** Recreate es aceptable cuando una interrupción breve
no tiene coste, y aquí no lo tiene: el destino es local (respuesta Q1 = A), lo
opera una sola persona (Q3 = A), y no hay usuarios concurrentes que puedan ver
una pantalla a medias. El supuesto **A1** de los requisitos lo dice en los
mismos términos: no se contempla concurrencia de dos administradores sobre el
mismo registro.

## Tiempo de inactividad esperado

**Cero, en el caso normal.** El código montado se actualiza sin reiniciar nada.
La limpieza de cachés (`view:clear`, `route:clear`, `config:clear`) tarda unos
segundos y no detiene el servidor; lo que hace es invalidar archivos compilados
que Laravel regenera en la siguiente petición.

Si hubiera que reconstruir la imagen —solo si cambia el `Dockerfile` o
`composer.json`— la interrupción sería la del `docker compose up -d --build`,
del orden de minutos.

## Migración de base de datos

**Ninguna.** Esta corrección no toca el esquema, por la restricción **C1**: las
tablas del dominio viven en `database/dentissa.sql` y no en migraciones de
Laravel. `source-manifest.json` no incluye ningún archivo de migración.

El patrón expandir-contraer que se usaría para un cambio de esquema sin
interrupción **no aplica aquí**, y no se documenta como si aplicara.

Un matiz que sí importa, y que motiva el respaldo previo del runbook: esta
corrección hace que el alta y la edición de usuarios **funcionen** por primera
vez. Eso significa que después de desplegarla se pueden crear registros que
antes era imposible crear. El respaldo no protege contra la corrección; protege
contra lo que se haga con ella.

## Promoción entre entornos

**No hay promoción, porque no hay entornos que promover.** Un único entorno
local (Q1 = A). La regla de la organización —«desplegamos al fusionar a
staging; producción se aprueba a mano»— describe una topología que este proyecto
no tiene todavía. No se contradice: simplemente no hay staging ni producción
que separar.

El equivalente honesto de la puerta de aprobación manual aquí es la fila de la
tabla de puertas de `cd-config.md` que dice «Suite de pruebas en verde antes del
`git pull`». Esa es la puerta real.

## Comprobación de humo

Se ejecuta después de desplegar (paso 9 de `cd-config.md`) y es lo que decide si
el despliegue se da por bueno. **Un despliegue no está hecho hasta que el humo
pasa.**

Cinco comprobaciones, elegidas porque cada una cubre uno de los cuatro defectos
corregidos más el modo de fallo propio del despliegue:

| # | Qué hacer | Qué debe pasar | Qué cubre |
|---|---|---|---|
| 1 | Abrir `/admin/usuarios` con sesión de administrador | Sale el listado real de usuarios, no la pantalla de marcador de posición ni un error de ruta | TD-12, y el fallo de caché de vistas |
| 2 | Pulsar «Nuevo usuario» | Sale el formulario de alta, no un 404 | TD-11 |
| 3 | Abrir la edición de «Colocación de brackets», cambiar solo la duración y guardar | Al reabrirlo, el precio sigue siendo 18.000,00 | TD-8 |
| 4 | Editar un usuario y abrir el selector de rol | Ofrece las cuatro opciones, incluida Asistente | TD-16, elevación de privilegios |
| 5 | En destino, ejecutar la suite completa | `OK (22 tests, 108 assertions)` | Todo lo anterior, de forma automática |

La quinta es la más barata y la más informativa: requiere haber ejecutado antes
`bash database/testing/bootstrap-test-db.sh`, y con eso una sola orden verifica
los cuatro defectos.

**Criterio de abortar:** si falla cualquiera de las cinco, ir a
`rollback-runbook.md`. Si falla solo la 1 y el error menciona
`admin.usuarios.destroy`, el problema es la caché de vistas, no el código:
repetir el paso 7 de `cd-config.md` antes de revertir nada.

## Vuelta atrás

El procedimiento completo está en `rollback-runbook.md`. En resumen: revertir
al commit anotado en el paso 3, limpiar cachés, reconstruir el front-end y, si
hubo escrituras que no se quieren conservar, restaurar el respaldo de base de
datos tomado en el paso 2.

**Disparadores de vuelta atrás**, en orden de probabilidad:

1. Cualquier punto de la comprobación de humo falla y no es el caso de la caché.
2. La aplicación devuelve 500 en cualquier pantalla de administración.
3. Aparecen precios a 0 en `/admin/servicios` o en las vistas SQL — significaría
   que la corrección de TD-8 no llegó.
4. Alguien detecta que un usuario cambió de rol sin que nadie lo cambiara.

## Qué haría falta para graduarse

Si este proyecto pasa de local a una máquina de verdad, el orden de inversión
que más seguridad compra por unidad de esfuerzo:

1. **Automatizar la suite en cada empuje.** Un `.github/workflows/tests.yml` que
   levante MariaDB, ejecute `bootstrap-test-db.sh` y corra `phpunit`. Es la
   puerta que hoy depende de que alguien se acuerde. Esfuerzo: horas.
2. **Sacar los secretos del `docker-compose.yml`.** `MYSQL_ROOT_PASSWORD: root`
   y `MYSQL_ALLOW_EMPTY_PASSWORD: "yes"` a variables de entorno fuera del
   control de versiones. Esfuerzo: menos de una hora.
3. **Dejar de publicar 3306 y 8080** en cuanto la máquina sea accesible desde
   fuera. Esfuerzo: minutos, y es el cambio de mayor impacto en seguridad.
4. **Una métrica de salud y una de errores**, que es lo mínimo que haría posible
   un canario más adelante. Es el trabajo de `observability-setup`, fuera de
   este alcance.

## Riesgos que este despliegue no resuelve

Se listan porque callarlos sería peor que no poder arreglarlos aquí. Ninguno lo
introduce esta corrección; todos preexisten.

- **Credenciales en claro en `docker-compose.yml`.** Contraseña de root fija y
  contraseña vacía permitida. Aceptable en local; inaceptable en cuanto la base
  escuche en una red compartida.
- **Puertos de base de datos y phpMyAdmin publicados al host** (3306 y 8080).
  Dos superficies de ataque si la máquina deja de ser local.
- **El esquema del dominio no está en migraciones.** Reproducir el entorno en
  otra máquina depende de `database/dentissa.sql`, un artefacto que se
  desincroniza si alguien cambia el esquema sin regenerarlo.
- **Sin observabilidad.** No hay métrica de salud ni de errores, así que un
  fallo en ejecución se detecta cuando alguien lo ve, no cuando ocurre.
- **Sin copias de seguridad automáticas.** El respaldo del runbook es manual y
  previo al despliegue; no hay una política periódica.
