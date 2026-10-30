# Producción en el servidor propio

Guía paso a paso para preparar el servidor (Ubuntu Server 24.04), desplegar
Dentissa y abrirla a internet con Cloudflare Tunnel (spec 030, ADR 0005).
Todos los comandos se ejecutan **en el servidor, por SSH, desde la cuenta de
despliegue** (la cuenta creada al instalar Ubuntu), salvo los marcados "en tu PC".

> **Nunca pegues en el chat** el token del túnel, contraseñas, `APP_KEY` ni el
> contenido de los archivos de entorno. Para pedir ayuda, pega solo la salida de
> los scripts, que no muestra secretos.

| Ruta en el servidor | Contenido |
|---|---|
| `/opt/dentissa/app` | clon del repositorio (solo lectura desde GitHub) |
| `/opt/dentissa/entorno` | `app.env`, `db.env`, `tunnel.env` y `copias.pub.asc` (carpeta 700, archivos 600, **fuera del clon**) |
| `/var/backups/dentissa` | copias cifradas de la base, `.sql.gz.gpg` (carpeta 700, copias 600, las 14 más recientes) |

## 1. Preparar el servidor (una sola vez)

1. **En tu PC**, copia tu clave pública al servidor y comprueba que entras sin
   contraseña:
   ```bash
   ssh-copy-id <cuenta>@<IP del servidor>
   ssh <cuenta>@<IP del servidor>
   ```
2. Todavía no hay clon en el servidor: **en tu PC**, desde tu copia del
   repositorio en la rama o tag que vas a desplegar, envía solo el script; luego,
   en el servidor, ejecútalo con la red local desde la que vas a administrar:
   ```bash
   scp deploy/preparar-servidor.sh <cuenta>@<IP del servidor>:   # en tu PC
   sudo bash preparar-servidor.sh 192.168.1.0/24                  # en el servidor
   ```
   Si la cuenta no tiene clave autorizada, el script se detiene sin cambiar nada.
3. **Antes de cerrar la sesión**, abre otra por SSH y comprueba que entras con tu
   clave. Después cierra sesión y vuelve a entrar para usar `docker` sin `sudo`.

## 2. Clave de despliegue de solo lectura y clon

1. Crea una clave solo para leer el repositorio:
   ```bash
   ssh-keygen -t ed25519 -f ~/.ssh/dentissa_deploy -N "" -C "dentissa-servidor"
   cat ~/.ssh/dentissa_deploy.pub
   ```
2. En GitHub: repositorio → **Settings → Deploy keys → Add deploy key**, pega la
   clave pública y deja **sin marcar** "Allow write access".
3. Dile a SSH que use esa clave para GitHub y clona en `/opt/dentissa/app`:
   ```bash
   printf 'Host github.com\n  IdentityFile ~/.ssh/dentissa_deploy\n  IdentitiesOnly yes\n' >> ~/.ssh/config
   chmod 600 ~/.ssh/config
   git clone git@github.com:gimi34/dentissa.git /opt/dentissa/app
   cd /opt/dentissa/app
   ```

### Red privada de administración (antes del primer despliegue)

Con el clon ya hecho, instala la red privada para administrar el servidor desde
fuera de casa siguiendo la **§11** (`sudo bash deploy/preparar-acceso-remoto.sh`,
caducidad de la clave y ACL). Desde la spec 031, `verificar.sh` la exige: sin
ella, la primera verificación daría un FALLO en «Administración remota».

## 3. Túnel de Cloudflare

1. En Cloudflare: **Zero Trust → Networks → Tunnels → Create a tunnel →
   Cloudflared**, nombre `dentissa`, entorno **Docker**. De la orden que muestra,
   copia **solo el token** (la cadena larga tras `--token`).
2. En **Public Hostname**, añade el subdominio (p. ej. `dentissa.tudominio.com`)
   con servicio **HTTP** y URL **`web:80`**.
3. Recomendado, como refuerzo de la redirección a https que ya hace nginx: en el
   dominio, **SSL/TLS → Edge Certificates → Always Use HTTPS** activado.
4. **No arranques el túnel todavía**: se abre en la liberación (sección 9).

## 4. Archivos de entorno

Crea los tres archivos en `/opt/dentissa/entorno` (nunca dentro del clon) con
`nano`, y ponles permisos 600. **Una variable por línea, con la forma
`NOMBRE=valor`**: sin comas, sin espacios alrededor del `=` y sin comillas. En
`nano`, guarda con `Ctrl+O` y `Enter`, y sal con `Ctrl+X`. Para las contraseñas,
`openssl rand -hex 24` da valores sin caracteres problemáticos.

**`app.env`** (pon tus valores donde van los `<…>`; `APP_KEY` se rellena después):
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<subdominio>
APP_KEY=
LOG_LEVEL=info
SESSION_DRIVER=file
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=
CACHE_STORE=database
DB_CONNECTION=mariadb
DB_HOST=db
DB_PORT=3306
DB_DATABASE=dentissa
DB_USERNAME=dentissa_app
DB_PASSWORD=<la misma que MARIADB_PASSWORD de db.env>
GOOGLE_CLIENT_ID=<client id>
GOOGLE_CLIENT_SECRET=<client secret>
GOOGLE_REDIRECT_URI=https://<subdominio>/auth/google/callback
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=<cuenta de envío>@gmail.com
MAIL_PASSWORD=<contraseña de aplicación, sin espacios>
MAIL_FROM_ADDRESS=<cuenta de envío>@gmail.com
MAIL_FROM_NAME=Dentissa
AVISOS_CORREO=<tu correo de siempre>
```

> **Correo de los avisos (spec 032):** crea una **cuenta de Gmail nueva, solo
> para enviar los avisos**, sin otros datos ni reenvíos, y no la uses para nada
> más. Una contraseña de aplicación da acceso a todo el buzón de su cuenta: si
> alguien comprometiera la aplicación, solo vería esa cuenta vacía, no tu correo.
> En esa cuenta: **Cuenta de Google → Seguridad → Verificación en dos pasos**
> (activada) y después **Contraseñas de aplicaciones** → crea una para «Dentissa»
> y cópiala **sin espacios** en `MAIL_PASSWORD`. Los avisos llegan a
> `AVISOS_CORREO`, tu correo de siempre. El puerto 465 es TLS desde el primer
> byte: la contraseña nunca viaja sin cifrar. `verificar.sh` da FALLO con otro
> puerto o con los `<…>` de ejemplo, sin mostrar los valores. Si cambias la
> contraseña de esa cuenta, Google revoca la de aplicación: crea otra y aplícala
> con `aplicar-entorno.sh app` (§10).

> **Sustituye todos los `<…>`**, también `GOOGLE_CLIENT_ID` y
> `GOOGLE_CLIENT_SECRET`: si se quedan con el texto de ejemplo, Google responde
> `invalid_client`. `verificar.sh` lo comprueba sin mostrar los valores
> («GOOGLE_CLIENT_ID con formato de id de Google» y «GOOGLE_CLIENT_SECRET
> definido…») y da FALLO mientras sigan los marcadores. El id real termina en
> `.apps.googleusercontent.com`.

**`db.env`** (dos contraseñas distintas; la base `dentissa` y el usuario
`dentissa_app` ya vienen en el Compose):
```
MARIADB_PASSWORD=<contraseña del usuario de la base>
MARIADB_ROOT_PASSWORD=<otra contraseña, la de root>
```

**`tunnel.env`** (solo la cadena larga que va tras `--token` en Cloudflare):
```
TUNNEL_TOKEN=<token>
```

```bash
cd /opt/dentissa/entorno
nano app.env
nano db.env
nano tunnel.env
chmod 600 app.env db.env tunnel.env
ls -l
grep -vnE '^[A-Z_]+=' app.env db.env tunnel.env   # no debe imprimir nada
cut -d= -f1 app.env                               # solo los nombres
```

**Generar `APP_KEY`** sin arrancar la base y guardarla en `app.env` sin que
aparezca en pantalla (la primera vez construye la imagen):
```bash
cd /opt/dentissa/app
CLAVE=$(docker compose -f docker-compose.prod.yml run --rm --no-deps -u www-data app php artisan key:generate --show | tail -n 1 | tr -d '\r')
echo "$CLAVE" | grep -c '^base64:'                 # debe dar 1; si da 0, no sigas
sed -i '/^APP_KEY=/d' /opt/dentissa/entorno/app.env
printf 'APP_KEY=%s\n' "$CLAVE" >> /opt/dentissa/entorno/app.env
unset CLAVE
grep -c '^APP_KEY=base64:' /opt/dentissa/entorno/app.env   # debe dar 1
```
Esta orden crea vacío el volumen de la base; `desplegar.sh` lo detecta y trata
igualmente el primer despliegue como tal.

## 5. Google

En Google Cloud Console → **APIs & Services → Credentials** → el cliente OAuth
de Dentissa → **Authorized redirect URIs**, añade
`https://<subdominio>/auth/google/callback`. Sin esto, el login con Google falla
en producción. El id y el secreto de ese mismo cliente son los que van en
`app.env` (§4), en lugar de los marcadores.

## 6. Ensayo y primer despliegue (sin exponer)

Mientras el túnel está parado, nadie de fuera llega a la aplicación. Antes de
cada ensayo (desplegar, volver atrás, restaurar la base o reiniciar el servidor)
**confirma en el chat tu aprobación**: queda anotada en `tasks.md`.

```bash
cd /opt/dentissa/app
bash deploy/desplegar.sh <tag o commit>      # el primero anuncia "sin copia previa"
docker compose -f docker-compose.prod.yml exec -u www-data app php artisan dentissa:rotar-credenciales --confirmar
bash deploy/verificar.sh --interna
```

`dentissa:rotar-credenciales` muestra **una sola vez** la contraseña nueva de cada
cuenta del volcado: guárdala en tu gestor de contraseñas, nunca en el chat ni en
el repositorio. `desplegar.sh` termina con `verificar.sh --interna`. **En el
primer despliegue esa verificación falla siempre en "contraseñas del README"**,
porque las credenciales del volcado aún no están rotadas: el script lo avisa y
no propone volver atrás (no hay versión anterior). Rota las credenciales y
repite `bash deploy/verificar.sh --interna`; entonces debe salir todo OK. En
los despliegues siguientes, si algo falla, te dice cómo volver atrás.

### Probar la aplicación sin exponerla

`web` no publica puertos. Para entrar desde tu PC, abre un túnel SSH a su IP en
la red de Docker:

```bash
# En el servidor: IP de web
docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' dentissa-prod-web-1
# En tu PC:
ssh -L 8081:<IP de web>:80 <cuenta>@<IP del servidor>
```

Abre **`http://localhost:8081`** (exactamente `localhost`). La cookie de sesión
es `Secure` y el navegador solo la envía por http cuando el sitio es
`localhost`; con la IP del servidor el login fallaría sin motivo aparente.

## 7. Copias de la base

Desde la spec 032 las copias van **cifradas** para tu clave
(`dentissa-AAAAMMDD-HHMMSS.sql.gz.gpg`). El servidor solo tiene la clave pública:
puede cifrarlas, pero no abrirlas. Se abren y se restauran **solo desde tu PC**,
donde está la clave secreta; ni la clave ni su frase llegan nunca al servidor.

### La clave de las copias (una sola vez, en tu PC)

Todo en **Git Bash** (viene con Git para Windows, con `gpg` y `ssh`).

1. Crea la clave:
   ```bash
   gpg --quick-generate-key "Dentissa copias" default default never
   ```
   Te pide una **frase de al menos 5 palabras al azar o 20 caracteres**, que no
   uses en ningún otro sitio: en tu PC están juntas las copias y la clave, así
   que una copia robada de tu PC solo la protege esa frase. La clave no caduca a
   propósito: caducar solo impediría cifrar copias nuevas.
2. Haz la **segunda copia de la clave secreta fuera del PC** (una memoria USB
   guardada en otro sitio, o tu gestor de contraseñas como archivo adjunto):
   ```bash
   gpg --armor --export-secret-keys "Dentissa copias" > dentissa-copias-secreta.asc
   ```
   Guárdala, y después borra el archivo del PC. Sigue protegida por la frase.
3. Lleva la clave pública al servidor:
   ```bash
   gpg --armor --export "Dentissa copias" > copias.pub.asc                       # en tu PC
   scp copias.pub.asc <cuenta>@<IP del servidor>:/opt/dentissa/entorno/          # en tu PC
   chmod 600 /opt/dentissa/entorno/copias.pub.asc                                # en el servidor
   gpg --show-keys /opt/dentissa/entorno/copias.pub.asc                          # en el servidor: «pub», nunca «sec»
   ```

> **Si pierdes la clave secreta y su segunda copia, las copias no se recuperan:**
> nadie, tampoco tú, puede abrirlas. Si pierdes el PC, importa la segunda copia
> en otro equipo con gpg y SSH (`gpg --import dentissa-copias-secreta.asc`) y
> sigue desde allí. El ensayo de restauración se hace con esa segunda copia.

### Hacer y llevarte una copia

`desplegar.sh` hace una antes de cada despliegue (salvo el primero). A mano:

```bash
bash deploy/respaldar.sh
```

Sin `gpg` o sin una clave pública válida en `/opt/dentissa/entorno/copias.pub.asc`,
la copia **no se hace en claro**: el script se detiene (y `desplegar.sh`, antes de
cambiar nada). Tras cada despliegue, llévate la copia más reciente (**en tu PC**):

```bash
scp <cuenta>@<IP del servidor>:/var/backups/dentissa/dentissa-AAAAMMDD-HHMMSS.sql.gz.gpg .
```

Va cifrada; aun así, guárdala en una carpeta solo tuya. **Las copias en claro de
antes de la 032 que tengas en el PC (`.sql.gz`)**: sustitúyelas por copias
cifradas y bórralas del todo (también de la papelera).

### Restaurar una copia (en tu PC)

Desde tu clon del repositorio, en Git Bash. Borra la base del servidor y la
recrea desde la copia:

```bash
bash deploy/restaurar.sh dentissa-AAAAMMDD-HHMMSS.sql.gz.gpg <cuenta>@<IP del servidor>
```

1. Comprueba la copia entera en tu PC: pide la frase y la descifra hacia ningún
   sitio. Si algo no cuadra, se detiene **sin tocar nada** y dice por qué: «la
   copia no es para tu clave», «no se pudo usar la clave (frase incorrecta o
   cancelada)», «la copia está alterada o cortada» o «el volcado está
   incompleto».
2. Te pide escribir el nombre de la base (`dentissa`) para confirmar.
3. Para `app` y `web`, descifra otra vez y envía el volcado por SSH; el servidor
   lo carga sin escribirlo en el disco. La frase se pide una sola vez si el
   agente de gpg aún la recuerda (10 minutos); si no, la vuelve a pedir, y la
   base no se toca hasta que llegan los datos.
4. Levanta `app` y `web`, rehace las cachés y ejecuta `verificar.sh --interna`.

Solo para comprobar una copia, sin servidor: `bash deploy/restaurar.sh <copia> --solo-comprobar`.
Si la carga se corta (por ejemplo, se cae la red), la base queda a medias: la
copia sigue intacta en tu PC, repite la misma orden antes de nada más. Si el clon
del servidor no está en `/opt/dentissa/app`, añade delante
`DENTISSA_APP=<ruta>`.

## 8. Volver a la versión anterior

Sin cambios en la base, **en el servidor**:

```bash
bash deploy/volver.sh <tag o commit anterior>
```

Si la versión nueva migró la base, se vuelve con la copia que dejó
`desplegar.sh`, **desde tu PC** (la copia está cifrada):

```bash
scp <cuenta>@<IP del servidor>:/var/backups/dentissa/<copia>.sql.gz.gpg .
bash deploy/restaurar.sh <copia>.sql.gz.gpg <cuenta>@<IP del servidor> --volver <tag o commit anterior>
```

`restaurar.sh` comprueba la copia en tu PC, llama a `volver.sh <anterior>
--sin-levantar` en el servidor (cambia de versión y deja `app` y `web` parados),
carga la copia y levanta la versión anterior. Sirve también para volver a una
versión anterior a la 032.

**El `scheduler` (spec 020)** hace la purga mensual del registro de auditoría y
escribe en la base, así que `volver.sh` lo para **antes** de cambiar de versión y
`restaurar.sh` lo deja parado mientras carga la copia; al terminar, los dos lo
levantan solo si la versión de destino lo tiene. Si la versión de destino no lo
tiene (anterior a la 020), el contenedor se quita (`--remove-orphans`) y no queda
ningún `scheduler` de otra versión en marcha. **Con la 020 la vuelta va sin la
copia:** su migración solo añade la tabla `auditoria`, que la versión anterior
ignora; con la copia se perderían los eventos escritos desde el despliegue. Cuando la verificación de un despliegue falla,
`desplegar.sh` imprime la orden exacta. La forma de la 030
(`volver.sh <anterior> <copia>`) ya no restaura: se detiene y da esta orden.

Para dejar la aplicación sin exposición al instante:
`docker compose -f docker-compose.prod.yml stop cloudflared`.

**Volver a una versión anterior a la 032:** esa versión no tiene el vigilante de
avisos, y `volver.sh` avisa de parar su temporizador:
`sudo systemctl disable --now dentissa-vigilar.timer`. Las copias siguen
cifradas y se restauran igual, desde tu PC.

**Volver a una versión anterior a la 031 (p. ej. `v0.5.0`) con la red privada
activa:** el `verificar.sh` de esa versión da **un único FALLO conocido**, en
«nada escucha fuera de localhost salvo SSH», porque `tailscaled` escucha en su
dirección de la red privada. Es lo esperado (spec 031 CA8). Cualquier otro FALLO
no lo es.

## 9. Abrir el túnel (liberación)

Solo en `/release`, con aprobación explícita:

```bash
bash deploy/desplegar.sh vX.Y.Z
docker compose -f docker-compose.prod.yml up -d cloudflared
bash deploy/verificar.sh           # ahora también por la dirección pública
```

Desde entonces `cloudflared` vuelve solo tras un reinicio. Los scripts de
despliegue nunca lo arrancan ni lo paran.

### Primera liberación con copias cifradas (spec 032, una sola vez)

`desplegar.sh` hace la copia previa con el `respaldar.sh` de la versión
**instalada**: el de una versión anterior a la 032 la haría en claro. Por eso,
en esta liberación el orden es otro:

```bash
cd /opt/dentissa/app
# 0. Antes de cambiar nada: hay con qué cifrar (si algo falla, no sigas).
command -v gpg && gpg --show-keys /opt/dentissa/entorno/copias.pub.asc   # «pub», nunca «sec»
# 1. El checkout del tag, a mano, para que la copia previa ya salga cifrada.
git fetch --tags origin && git -c advice.detachedHead=false checkout vX.Y.Z
# 2. El despliegue.
bash deploy/desplegar.sh vX.Y.Z
# 3. El temporizador de los avisos (§12).
sudo bash deploy/preparar-vigilancia.sh
# 4. Las copias en claro de antes: se cifran y se borran las originales.
bash deploy/cifrar-copias-antiguas.sh
# 5. La verificación completa: también «Avisos y copias».
bash deploy/verificar.sh
# 6. Ningún volcado en claro en todo el disco (también de los ensayos de la 030).
sudo find / -xdev \( -name '*.sql' -o -name '*.sql.gz' \) -path '*dentissa*' 2> /dev/null
```

Como el checkout ya está hecho, `desplegar.sh` informa como «versión anterior»
el propio tag: la vuelta, si hace falta, es a la versión anterior real (la que
estaba instalada antes del paso 1). Después: aviso de prueba y monitor de
UptimeRobot (§12), y en tu PC, sustituir y borrar las copias en claro (§7).

### Primera liberación de la 020 (una sola vez)

El `desplegar.sh` instalado (hasta la 0.11) no conoce el `scheduler`. En esta
liberación se hace el checkout a mano, para que corra el de la 020, que ya lo
levanta:

```bash
cd /opt/dentissa/app
# 1. Anota la versión instalada: es a la que se vuelve si algo falla.
git describe --tags
# 2. El checkout del tag, a mano.
git fetch --tags origin && git -c advice.detachedHead=false checkout vX.Y.Z
# 3. El despliegue: app, web y el scheduler, y la verificación interna.
bash deploy/desplegar.sh vX.Y.Z
# 4. La verificación completa: también «scheduler en marcha» y «auditoria:purgar».
bash deploy/verificar.sh
```

Como el checkout ya está hecho, `desplegar.sh` informa como «versión anterior»
el propio tag: la vuelta, si hace falta, es a la versión anotada en el paso 1.

## 10. Cambiar un archivo de entorno

Un `up -d` normal **no relee** `app.env` ni `tunnel.env`: el contenedor sigue con
los valores antiguos. Tras editar el archivo, recrea su servicio:

```bash
nano /opt/dentissa/entorno/app.env        # o tunnel.env
bash deploy/aplicar-entorno.sh app        # o cloudflared, o el servicio que lea ese archivo
```

`aplicar-entorno.sh` recrea el servicio, en `app` vuelve a generar las cachés de
configuración y rutas, y termina con `verificar.sh --interna`. Con `app` recrea
también el `scheduler` (spec 020), que lee el mismo `app.env`: si no, seguiría
con los valores antiguos (por ejemplo, la contraseña de la base sin rotar). Su sección
«Entorno aplicado» da FALLO si algún servicio en marcha usa una configuración
distinta de la guardada, y dice cuál (sin mostrar valores). También da FALLO si no
puede comparar algún servicio: nunca da OK sin haberlos revisado todos.

**`db.env` es distinto:** MariaDB solo lo lee al crear la base. Cambiar
`MARIADB_PASSWORD` ahí no cambia la contraseña real. Para cambiarla, entra en la
base con la contraseña actual y cámbiala con `ALTER USER`; después pon la nueva
en `db.env` y en `DB_PASSWORD` de `app.env` y ejecuta
`bash deploy/aplicar-entorno.sh app`. `aplicar-entorno.sh db` se niega a
propósito.

## 11. Administrar desde fuera de casa (red privada)

El servidor y tus dispositivos se unen a una red privada de Tailscale (ADR 0006).
No se abre ningún puerto en el router y se sigue entrando por SSH **con tu clave**.

1. **En el servidor** (por SSH desde casa):
   ```bash
   cd /opt/dentissa/app
   sudo bash deploy/preparar-acceso-remoto.sh
   ```
   Abre el enlace que muestra e inicia sesión con tu cuenta. Al final te da la
   dirección del servidor en la red privada (`100.x.y.z`).
2. **En la consola** de Tailscale (login.tailscale.com):
   - **Machines →** el servidor **→ … → Disable key expiry**. Si no, dentro de unos
     meses el servidor sale de la red y solo podrías volver a conectarlo desde casa.
   - **Access controls:** sustituye la política por esta (pon la dirección del
     servidor). Solo deja llegar al servidor por el puerto 22; las reglas de
     Tailscale van antes que `ufw`, así que **esta ACL es el control real**:
     ```json
     {
       "hosts": { "dentissa-server": "100.x.y.z" },
       "acls": [
         { "action": "accept", "src": ["autogroup:member"], "dst": ["dentissa-server:22"] }
       ]
     }
     ```
     Borra **todo** lo anterior: la política de por defecto trae un bloque `"grants"`
     con `{"src": ["*"], "dst": ["*"], "ip": ["*"]}` que permite cualquier conexión
     y anula esta regla aunque la añadas (ensayo de T041), y una sección `"ssh"`
     del SSH propio de Tailscale, que aquí no se usa.
3. **En cada dispositivo tuyo** (PC o móvil): instala Tailscale e inicia sesión con
   la misma cuenta. Para el móvil, además, un cliente SSH (por ejemplo Termius)
   con una clave propia de ese dispositivo, añadida a `~/.ssh/authorized_keys`
   del servidor desde casa.
4. **Entrar desde fuera:**
   ```bash
   ssh <cuenta>@100.x.y.z        # o ssh <cuenta>@dentissa-server si usas MagicDNS
   ```
5. **Comprobar:** `bash deploy/verificar.sh --interna` debe dar OK en
   «Administración remota» (red privada conectada y SSH sin contraseña).

**Retirar un dispositivo** (perdido o que ya no usas):
1. En la consola, **Machines →** ese dispositivo **→ … → Remove**. Deja de llegar
   al servidor por la red privada.
2. En el servidor, borra su clave de `~/.ssh/authorized_keys` (cada clave lleva al
   final un comentario con su nombre). Así tampoco entra desde la red local.
3. Comprueba desde otro dispositivo que sigues entrando.

## 12. Avisos de producción (spec 032)

Te llega un correo a `AVISOS_CORREO` cuando:

| Aviso | Cuándo |
|---|---|
| Errores del servidor | En 5 minutos, más del 1 % de las respuestas son errores del servidor (5xx). |
| Inicios de sesión fallidos | En 5 minutos, más de 10 fallos desde un mismo origen o contra una misma cuenta (contraseña o vuelta de Google). El límite de intentos sigue frenándolos. |
| El log de acceso tiene otro formato | El vigilante no reconoce las líneas del log de nginx; mientras dure, no puede contar los errores. |
| Caída vista desde fuera | `/up` no responde durante 5 minutos, también con el servidor apagado; y otro correo al volver (UptimeRobot). |

Como mucho llega **un correo por tipo cada 30 minutos**; lo que pase entre medias
se acumula y llega en el siguiente, con cada evento contado una sola vez. Los
correos solo llevan el tipo, recuentos, horas y cómo consultar el detalle: nunca
datos de pacientes, correos de cuentas, direcciones IP ni secretos. Con poco
tráfico, un solo error entre pocas peticiones ya pasa del 1 %: es un riesgo
aceptado; si molesta, el umbral se ajusta en otra spec.

**Instalar el vigilante** (una vez; lo lanza un temporizador de systemd cada
minuto, con la cuenta de despliegue):

```bash
sudo bash deploy/preparar-vigilancia.sh
systemctl list-timers dentissa-vigilar.timer      # la próxima ejecución, en menos de un minuto
```

**Aviso de prueba** (debe llegar en 5 minutos o menos; si no, revisa las
variables de correo de `app.env`, §4):

```bash
docker compose -f docker-compose.prod.yml exec -T -u www-data app php artisan dentissa:vigilar --prueba
```

**Consultar el detalle** (en `/opt/dentissa/app`):

```bash
journalctl -u dentissa-vigilar --since today        # cada ejecución; un envío fallido sale como error
docker compose -f docker-compose.prod.yml exec web tail -n 200 /var/log/nginx/dentissa-access.log
docker compose -f docker-compose.prod.yml exec app tail -n 50 storage/logs/seguridad-AAAA-MM-DD.log
```

El canal `seguridad` guarda cada fallo de acceso con huellas del origen y de la
cuenta, sin la IP ni el correo, durante 14 días.

**Pararlo:** `sudo systemctl disable --now dentissa-vigilar.timer` (por ejemplo,
al volver a una versión anterior a la 032). Para reanudarlo, repite
`preparar-vigilancia.sh`.

**Caída vista desde fuera, con UptimeRobot** (gratuito: no pide tarjeta y admite
uso comercial; plan comprobado el 2026-10-25):

1. Crea una cuenta en uptimerobot.com con tu correo de siempre.
2. **Add New Monitor** → tipo **HTTP(s)**, URL `https://<subdominio>/up`,
   intervalo **5 minutes**, y como contacto de alerta tu correo.
3. UptimeRobot avisa al caer y al volver. Con el servidor apagado o el túnel
   parado, Cloudflare responde con un error y cuenta como caída.
4. Comprueba en su panel que el monitor está en verde (**Up**).
