#!/bin/sh
set -eu

if [ "${1:-}" = 'apache2-foreground' ]; then
    mkdir -p /var/www/data
    if [ "$(id -u)" = '0' ]; then
        # Only the persistent data needs to be writable by Apache.
        # Do not change ownership of the mounted application code.
        chown -R --no-dereference www-data:www-data /var/www/data
        chmod -R u+rwX,g+rX,o-rwx /var/www/data
    fi
fi

exec docker-php-entrypoint "$@"
