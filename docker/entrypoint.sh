#!/bin/sh

echo "Running migrations..."
php artisan migrate --force

echo "Starting PHP-FPM..."
php-fpm
