#!/usr/bin/env bash
set -Eeuo pipefail

APP_ROOT="/var/www/kriptografi.rifrain.tech"
APP_USER="rifan"
CF_CONFIG="/home/rifan/.cloudflared/config.yml"
HOSTNAME="kriptografi.rifrain.tech"
SITE_NAME="kriptografi.rifrain.tech"
SITE_AVAILABLE="/etc/nginx/sites-available/${SITE_NAME}"
SITE_ENABLED="/etc/nginx/sites-enabled/${SITE_NAME}"
NGINX_TEMPLATE="${APP_ROOT}/deploy/nginx-cloudflared.conf"
CF_BACKUP="${CF_CONFIG}.bak-$(date +%Y%m%d%H%M%S)"

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Jalankan sekali dengan: sudo bash ${APP_ROOT}/bin/deploy-cloudflare-tunnel.sh" >&2
  exit 1
fi
if [[ ! -f "${APP_ROOT}/.env" || ! -f "${NGINX_TEMPLATE}" || ! -f "${CF_CONFIG}" ]]; then
  echo "Aplikasi, .env, atau konfigurasi Cloudflare Tunnel tidak ditemukan." >&2
  exit 1
fi
if [[ -e "${SITE_AVAILABLE}" ]]; then
  if ! cmp -s "${NGINX_TEMPLATE}" "${SITE_AVAILABLE}"; then
    echo "Konfigurasi Nginx ${SITE_AVAILABLE} sudah ada dan berbeda; tidak akan ditimpa." >&2
    exit 1
  fi
else
  install -o root -g root -m 0644 "${NGINX_TEMPLATE}" "${SITE_AVAILABLE}"
fi
ln -sfn "${SITE_AVAILABLE}" "${SITE_ENABLED}"
if ! nginx -t; then
  rm -f "${SITE_ENABLED}" "${SITE_AVAILABLE}"
  echo "Uji konfigurasi Nginx gagal; file vhost baru dibatalkan." >&2
  exit 1
fi
systemctl reload nginx

cp -p "${CF_CONFIG}" "${CF_BACKUP}"
python3 - "${CF_CONFIG}" "${HOSTNAME}" <<'PY'
from pathlib import Path
import sys

path = Path(sys.argv[1])
hostname = sys.argv[2]
lines = path.read_text().splitlines()
for i, line in enumerate(lines):
    if line.strip() == f"- hostname: {hostname}":
        if i + 1 < len(lines) and lines[i + 1].strip() == "service: http://localhost:80":
            print("Hostname sudah ada di ingress Cloudflare Tunnel.")
            break
        raise SystemExit("Hostname sudah ada dengan origin berbeda; tidak diubah.")
else:
    catch_all = next((i for i in range(len(lines) - 1, -1, -1) if lines[i].strip() == "- service: http_status:404"), None)
    if catch_all is None:
        raise SystemExit("Catch-all ingress tidak ditemukan; config tidak diubah.")
    lines[catch_all:catch_all] = [f"  - hostname: {hostname}", "    service: http://localhost:80"]
    path.write_text("\n".join(lines) + "\n")
    print("Hostname ditambahkan ke ingress Cloudflare Tunnel.")
PY
chown "${APP_USER}:${APP_USER}" "${CF_CONFIG}"

if ! runuser -u "${APP_USER}" -- env HOME="/home/${APP_USER}" XDG_RUNTIME_DIR="/run/user/1000" DBUS_SESSION_BUS_ADDRESS="unix:path=/run/user/1000/bus" systemctl --user restart cloudflared.service; then
  cp -p "${CF_BACKUP}" "${CF_CONFIG}"
  runuser -u "${APP_USER}" -- env HOME="/home/${APP_USER}" XDG_RUNTIME_DIR="/run/user/1000" DBUS_SESSION_BUS_ADDRESS="unix:path=/run/user/1000/bus" systemctl --user restart cloudflared.service || true
  echo "Restart tunnel gagal; konfigurasi sebelumnya dipulihkan. Vhost Nginx tetap tersedia." >&2
  exit 1
fi

echo "Vhost Nginx dan ingress tunnel siap untuk ${HOSTNAME}."
echo "Tambahkan CNAME di zone rifrain.tech secara manual; cert.pem lokal hanya berwenang pada zone rifrain.my.id."
echo "Tunnel direstart; situs lain yang memakai tunnel mungkin sempat terputus beberapa detik."
