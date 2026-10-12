#!/bin/sh
set -eu
if [ "${OLSHCO_WORKER_ENABLED:-0}" != 1 ]; then
    echo 'Notification worker disabled until deployment data and delivery settings are approved.'
    exec sleep infinity
fi
trap 'exit 0' TERM INT
while :; do
    php /var/www/html/scripts/dispatch_browser_push.php || echo 'Worker batch failed; inspect private application logs.' >&2
    sleep 60 &
    wait $!
done
