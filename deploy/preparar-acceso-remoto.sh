#!/usr/bin/env bash
#
# Spec 031 (CA8, CA9; D9, ADR 0006): red privada para administrar el servidor
# desde fuera de casa, sin abrir puertos en el router.
#
#     sudo bash deploy/preparar-acceso-remoto.sh
#
# Qué hace (se puede repetir sin cambiar el resultado):
#   - añade el repositorio oficial de Tailscale con su clave (signed-by) e
#     instala el cliente; no ejecuta ningún script descargado;
#   - añade su origen a las actualizaciones automáticas del sistema, con los
#     campos reales del repositorio (/analyze A7, A22);
#   - permite SSH por la interfaz de la red privada en ufw (defensa adicional: el
#     control es la ACL del tailnet, deploy/README.md §11);
#   - conecta el servidor sin el SSH propio de Tailscale (se sigue usando
#     OpenSSH con clave) y sin cambiar su DNS. El alta es interactiva: muestra
#     un enlace para iniciar sesión.

set -euo pipefail

LLAVERO=/usr/share/keyrings/tailscale-archive-keyring.gpg
LISTA=/etc/apt/sources.list.d/tailscale.list
ACTUALIZACIONES=/etc/apt/apt.conf.d/52dentissa-tailscale

error() {
    echo "ERROR: $*" >&2
    exit 1
}

paso() {
    echo
    echo "==> $*"
}

main() {
    [[ "${EUID}" -eq 0 ]] || error "ejecútalo con sudo: sudo bash deploy/preparar-acceso-remoto.sh"

    local codigo
    # shellcheck disable=SC1091
    codigo="$(. /etc/os-release && echo "${VERSION_CODENAME}")"
    [[ -n "${codigo}" ]] || error "no se pudo saber la versión de Ubuntu (/etc/os-release)"

    # ── Repositorio oficial firmado ─────────────────────────────────────────
    paso "Repositorio de Tailscale (${codigo})"
    install -m 0755 -d /usr/share/keyrings
    curl -fsSL "https://pkgs.tailscale.com/stable/ubuntu/${codigo}.noarmor.gpg" -o "${LLAVERO}"
    chmod a+r "${LLAVERO}"
    echo "deb [signed-by=${LLAVERO}] https://pkgs.tailscale.com/stable/ubuntu ${codigo} main" > "${LISTA}"
    apt-get update
    apt-get install -y tailscale

    # ── Actualizaciones automáticas ─────────────────────────────────────────
    paso "Actualizaciones automáticas de Tailscale"
    # Origen y archivo del repositorio tal como los publica: "apt-cache policy",
    # sin paquete, lista cada fuente con su línea "release o=…,a=…" (/analyze A22).
    local campos origen archivo
    campos="$(apt-cache policy | awk '/pkgs\.tailscale\.com/ { getline; if ($1 == "release") { print $2; exit } }')"
    origen="$(tr ',' '\n' <<< "${campos}" | sed -n 's/^o=//p')"
    archivo="$(tr ',' '\n' <<< "${campos}" | sed -n 's/^a=//p')"
    [[ -n "${origen}" && -n "${archivo}" ]] || error "no se pudieron leer los campos del repositorio de Tailscale (apt-cache policy)"
    cat > "${ACTUALIZACIONES}" <<CONF
// Spec 031 (ADR 0006): Tailscale se actualiza como los paquetes de Ubuntu.
// Campos del repositorio: ${campos}
Unattended-Upgrade::Allowed-Origins {
    "${origen}:${archivo}";
};
CONF
    echo "Origen permitido: ${origen}:${archivo}"

    # ── Servicio, cortafuegos y conexión ────────────────────────────────────
    paso "Servicio tailscaled"
    systemctl enable --now tailscaled

    paso "Cortafuegos: SSH por la red privada (defensa adicional; el control es la ACL)"
    ufw allow in on tailscale0 to any port 22 proto tcp

    paso "Conexión a la red privada (abre el enlace que aparece para iniciar sesión)"
    tailscale up --ssh=false --accept-dns=false

    paso "Listo"
    echo "Dirección del servidor en la red privada: $(tailscale ip -4)"
    echo "Versión instalada: $(tailscale version | head -n 1)"
    echo "En la consola de Tailscale (login.tailscale.com), según deploy/README.md §11:"
    echo "  1. Machines → este servidor → … → Disable key expiry."
    echo "  2. Access controls: aplica la ACL que solo deja llegar al servidor por tcp:22."
    echo "Después: bash deploy/verificar.sh --interna"
}

main "$@"
