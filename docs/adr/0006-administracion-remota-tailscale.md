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
- `ufw` permite el 22 por `tailscale0`, además de la red local.
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
- Las reglas de `tailscaled` aceptan lo que llega por `tailscale0`: el control real es que el tailnet
  solo tiene dispositivos del responsable. Se comprueba en el ensayo (solo el 22 abierto).
- El cliente de Tailscale se actualiza con las actualizaciones automáticas del sistema.
- Cambia la vía administrativa de la spec 030 (CA3): se suma la red privada a la red local.
