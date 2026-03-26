#!/bin/sh
set -e

php bin/console cache:warmup --no-optional-warmers 2>/dev/null || \
    php bin/console cache:clear --no-warmup 2>/dev/null || true

php-fpm -D

exec nginx -g 'daemon off;'
