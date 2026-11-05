#!/bin/sh
set -e

# Apache must run exactly one worker module, and PHP needs this one.
a2dismod -f mpm_event mpm_worker >/dev/null 2>&1 || true
a2enmod mpm_prefork >/dev/null

# Listen on the port Railway gives the service.
PORT="${PORT:-80}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# A Railway volume mounted on uploads/ starts empty and hides what's in the image,
# including the .htaccess files that keep private photos private. Restore anything
# missing, without touching files customers have already uploaded.
cp -Rn /usr/src/casely-uploads/. /var/www/html/uploads/ || true
chown -R www-data:www-data /var/www/html/uploads

exec apache2-foreground
