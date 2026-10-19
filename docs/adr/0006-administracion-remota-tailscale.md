---
id: 0006
status: accepted
date: 2026-10-06
---

# ADR 0006 · Administración remota del servidor por una red privada (Tailscale)

## Contexto
Desde la spec 030 (ADR 0005), el servidor de producción solo se administra por SSH con clave desde
la red local de la casa del responsable (`ufw`). La aplicación ya está en internet, y el
responsable necesita atender incidencias fuera de casa. La spec 031 (CA8–CA10) pide administrar
desde fuera sin abrir ninguna vía nueva desde internet, siempre con clave, y poder retirar un
dispositivo perdido. No hay presupuesto.

## Decisión
- El servidor y los dispositivos del responsable se unen a una red privada de Tailscale (un solo
  usuario, plan Personal gratuito). Tailscale solo abre conexiones salientes: no hay puertos en el
  router ni en el servidor.
- Se instala con `deploy/preparar-acceso-remoto.sh`, desde el repositorio oficial firmado
  (`pkgs.tailscale.com`, `signed-by`), nunca con `curl | sh`. El alta es interactiva y no hay clave
  de autorización en el repositorio. La caducidad de la clave del servidor se desactiva en la consola.
- El acceso sigue siendo OpenSSH con clave y sin contraseña. El SSH propio de Tailscale queda
  desactivado (`--ssh=false`) para que no salte esa política.
- Una ACL del tailnet solo deja llegar al servidor por tcp:22 (copiada en `deploy/README.md` y
  aplicada en la consola). Es el control real: las reglas de `tailscaled` aceptan lo que entra por
  `tailscale0` antes que `ufw`, y la regla de `ufw` para `tailscale0` queda solo como defensa
  adicional (spec 031, /analyze A6).
- El servidor no acepta el DNS de la red privada (`--accept-dns=false`), para no cambiar la
  resolución de los contenedores.
- Para retirar un dispositivo se quita del tailnet y su clave de `authorized_keys` (`deploy/README.md`).
- `deploy/verificar.sh` comprueba que `tailscaled` está en marcha y que SSH no ofrece contraseña.

## Alternativas consideradas
- Abrir el puerto 22 (u otro) en el router: expone el SSH a bots de todo internet. Se descarta (CA9).
- SSH por el túnel de Cloudflare con Cloudflare Access: reutiliza el túnel, pero exige una
  política de Access correcta (sin ella el SSH queda en internet) y `cloudflared` en cada equipo.
  Se descarta por complejidad.
- WireGuard propio: sin terceros, pero necesita un puerto abierto en el router y gestionar las
  claves a mano. Se descarta.

## Consecuencias
- Depende de un servicio de terceros para coordinar la red privada. Si cae, sigue el acceso desde
  la red local.
- El acceso por la red privada depende de la ACL del tailnet y de que solo haya dispositivos del
  responsable. Se comprueba en el ensayo con un puerto de prueba no alcanzable.
- El script añade el origen de Tailscale a las actualizaciones automáticas del sistema, así que el
  cliente se actualiza como los paquetes de Ubuntu (spec 031, /analyze A7).
- Volver a una versión anterior a la 031 con Tailscale activo da un FALLO conocido en su
  `verificar.sh` (spec 031 CA8).
- Cambia la vía administrativa de la spec 030 (CA3): se suma la red privada a la red local.
- Refinado antes de liberar la 031 (/analyze 031 A6 y A7: ACL del tailnet solo con el 22 y el origen de
  Tailscale en las actualizaciones automáticas), como se hizo con el ADR 0005 en la 030 (/analyze 031 A28).
