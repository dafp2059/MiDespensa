#!/usr/bin/env bash
# Arranca la base de datos y la web cada vez que se abre el codespace.
cd "$(dirname "$0")/.."
sudo service mariadb start >/dev/null 2>&1 || true
pkill -f "php -S 0.0.0.0:8000" >/dev/null 2>&1 || true
nohup setsid php -S 0.0.0.0:8000 -t public_html tools/servidor-local.php > /tmp/ingespro-web.log 2>&1 &
echo "Web INGESPRO en el puerto 8000"
