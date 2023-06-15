#!/bin/sh
set -e

echo "Deploying application ..."

# Update codebase
git fetch origin main
git reset --hard origin/main

# Install dependencies based on lock file
composer install -q --no-ansi --no-interaction --no-scripts --no-progress --prefer-dist

# Migrate database
# php artisan migrate --force

# Clear cache
php artisan optimize

# Permission
sudo chmod -R 777 storage bootstrap/cache

# Reload PHP to update opcache
echo "" | sudo -S service php8.1-fpm reload

echo "Application deployed!"
