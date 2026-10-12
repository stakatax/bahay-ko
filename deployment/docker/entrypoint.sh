#!/bin/sh
set -eu
umask 027
mkdir -p /run/olshco /var/lib/olshco/sessions /var/lib/olshco/tmp /var/www/html/logs
php /opt/olshco/configure.php
for directory in documents profile-photos posts document-covers announcement-audio; do
    mkdir -p "/var/www/html/Assets/uploads/$directory"
done
cp /opt/olshco/upload-security/.htaccess /var/www/html/Assets/uploads/.htaccess
cp /opt/olshco/upload-security/documents/.htaccess /var/www/html/Assets/uploads/documents/.htaccess
cp /opt/olshco/upload-security/profile-photos/.htaccess /var/www/html/Assets/uploads/profile-photos/.htaccess
chown -R www-data:www-data /run/olshco /var/lib/olshco /var/www/html/Assets/uploads /var/www/html/logs
if [ "${1:-}" = apache2-foreground ]; then
    apache2ctl -t
    exec "$@"
fi
exec su -s /bin/sh www-data -c 'exec "$@"' -- sh "$@"
