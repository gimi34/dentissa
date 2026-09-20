# Registro de ejecución del despliegue — Corrección del CRUD de administración

**Intención**: `260903-fix-admin-crud`
**Etapa**: deployment-execution (Operation) · **Alcance**: bugfix
**Fecha de ejecución**: 2026-09-19
**Entorno**: local, Docker Compose (`dentissa_app`, `dentissa_nginx`, `dentissa_db`, `dentissa_phpmyadmin`)
**Ejecutado por**: el conductor, con verificación de navegador conducida en Chrome
**Resultado**: **Desplegado y verificado.** Sin vuelta atrás.

Entradas consumidas: `../deployment-pipeline/cd-config.md` (el procedimiento),
`../deployment-pipeline/deployment-strategy.md` (la estrategia y el humo) y
`../../construction/build-and-test/test-results.md` (la evidencia de que lo
desplegado estaba verificado).

La entrada `environment-inventory` no existe y su ausencia está prevista: el
alcance `bugfix` salta Environment Provisioning. El inventario de entornos se
tomó de la configuración real del repositorio, no se inventó.

---

## Comprobaciones previas

Ejecutadas, no supuestas.

| Comprobación | Resultado | Evidencia |
|---|---|---|
| Suite de pruebas en verde | **Sí** | `OK (22 tests, 108 assertions)` |
| Construcción de front-end | **Sí** | `npm run build` salida 0 |
| Migraciones pendientes | **Ninguna** | `php artisan migrate:status`: las 6 figuran como `Ran` |
| Servicios dependientes sanos | **Sí** | Los cuatro en `running` |
| Árbol de trabajo | 29 cambios sin confirmar, intencionadamente | Decisión humana Q1 = C |

**Nada se confirmó en git.** Por decisión explícita del humano, la corrección
sigue sin commit sobre `main` (`210d5bd`). Es la elección prudente: sin
confirmar, revertir es descartar cambios del árbol de trabajo en vez de deshacer
un commit.

## Pasos ejecutados

| # | Paso | Resultado |
|---|---|---|
| 1 | Verificar rama y estado del árbol | `main`, 29 cambios sin confirmar (esperado) |
| 2 | **Respaldo de la base** | `respaldo-20260919-195715.sql`, 39 kB, 18 tablas, 2 vistas — verificado |
| 3 | Anotar punto de retorno | `210d5bd96984210a2a78921072fe2ac46da2cdb6` |
| 4 | Traer el código | No aplica: el destino es este mismo árbol de trabajo |
| 5 | Actualizar dependencias | Omitido: esta corrección no añade ninguna |
| 6 | Limpiar cachés de Laravel | `view:clear`, `route:clear`, `config:clear` — sin errores |
| 7 | **Recompilar todas las vistas** | `view:cache` → 97 plantillas compiladas |
| 8 | Construir front-end | `built in 806ms`, CSS 56,02 kB |
| 9 | Verificar que la aplicación responde | `/login` 200, `/` 302, los cuatro servicios `running` |
| 10 | Comprobación de humo | 5 de 5 en verde — ver `smoke-test-results.md` |

## Dos defectos del propio procedimiento, encontrados al ejecutarlo

Ambos estaban en artefactos que yo mismo había escrito en la etapa anterior.
Ejecutar el procedimiento es lo que los sacó a la luz; documentarlo no habría
bastado.

### 1. La orden de respaldo no funcionaba

`rollback-runbook.md` prescribía:

```bash
docker compose exec -T db mysqldump -uroot --all-databases ...
```

Al ejecutarla:

```
mysqldump: Got error: 1045: "Access denied for user 'root'@'localhost' (using password: NO)"
```

Dos errores en una línea. Faltaba `-proot` —el `MYSQL_ALLOW_EMPTY_PASSWORD` del
`docker-compose.yml` no aplica a la conexión por socket dentro del contenedor—
y `--all-databases` arrastraba las 233 tablas de sistema de MariaDB, cuya
restauración sobreescribiría la tabla de usuarios del motor. Corregido a
`mariadb-dump -uroot -proot --databases dentissa`, y verificado: 39 kB, 18
tablas, 2 vistas.

**Un respaldo que no se ha probado no es un respaldo.** Si este fallo se
descubre durante una vuelta atrás real, ya es tarde.

### 2. El procedimiento construía el front-end en el orden equivocado

La versión original hacía `npm run build` (paso 6) y después limpiaba las
cachés (paso 7). Es exactamente al revés de lo que hace falta, porque
`resources/css/app.css` declara las vistas compiladas como fuente de Tailwind:

```
@source '../../storage/framework/views/*.php';
```

Tailwind mira las plantillas ya renderizadas para capturar clases que se
componen en tiempo de ejecución (`class="badge badge-{{ $estado }}"`). La
consecuencia medida:

| Vistas compiladas al construir | CSS resultante |
|---|---|
| 0, justo después de `view:clear` | **40,10 kB** |
| Las 97, tras `view:cache` | **56,02 kB** |

**Faltaba cerca del 28 % de la hoja de estilos**, sin ningún error visible: la
aplicación carga, y simplemente algunas clases dinámicas no tienen regla. El
procedimiento corregido intercala `php artisan view:cache` entre la limpieza y
la construcción, y `cd-config.md` lleva ahora diez pasos en vez de nueve, con la
medición como evidencia y un umbral de comprobación («si el CSS sale sobre 40 kB
te saltaste el paso 7»).

## Tiempo de inactividad

**Cero.** El código va montado desde el host, así que nunca se paró ningún
servicio. Los contenedores acumulan 50 minutos de subida continua durante todo
el despliegue.

## Vuelta atrás

**No fue necesaria.** El respaldo previo y el punto de retorno quedan
disponibles por si algo aparece después:

- Respaldo: `respaldo-20260919-195715.sql` (en el directorio temporal de la
  sesión; cópialo a un sitio duradero si quieres conservarlo)
- Punto de retorno: `210d5bd96984210a2a78921072fe2ac46da2cdb6`
- Procedimiento: `../deployment-pipeline/rollback-runbook.md`

## Acción de seguimiento abierta

Se detectó **daño preexistente en los datos** que esta corrección no sana. No es
una regresión del despliegue; es la huella que TD-8 dejó antes de corregirse.
Está documentado con el detalle y la orden de reparación en
`health-check-report.md` § «Daño preexistente en los datos». **Requiere una
decisión humana** y queda fuera del alcance de esta intención.
