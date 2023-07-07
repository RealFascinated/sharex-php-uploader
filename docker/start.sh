#!/bin/sh

if [ -z "$MAX_UPLOAD_SIZE" ]; then
  MAX_UPLOAD_SIZE="8M"  # Default fallback value
fi

echo "Checking if upload script exists in /var/www/html"
if [ -f "/var/www/html/upload.php" ]; then
  echo "Upload script was found, ignoring copy."
else
  cp /tmp/upload.php /var/www/html
  echo "Upload script was not found, copying it."
fi

# Letting php know that we are running in docker
echo "env[DOCKER] = true" >> /etc/php8/php-fpm.d/www.conf
echo "clear_env = no" >> /etc/php8/php-fpm.d/www.conf

echo "Setting permissions for upload script"
chmod 777 /var/www/html/upload.php

echo "Setting max upload size to ${MAX_UPLOAD_SIZE}"

# Set max upload size for php
sed -i "s/^upload_max_filesize = .*/upload_max_filesize = ${MAX_UPLOAD_SIZE}/" /etc/php8/php.ini
sed -i "s/^post_max_size = .*/post_max_size = ${MAX_UPLOAD_SIZE}/" /etc/php8/php.ini

# Set max upload size for nginx
sed -i "s/client_max_body_size 500M;/client_max_body_size ${MAX_UPLOAD_SIZE};/" /etc/nginx/nginx.conf

function start() {
  echo "Starting PHP & Nginx"
  php-fpm8 &&
  nginx -g 'daemon off;'
}

# Start Nginx and retry if it fails
until start; do
  echo "Nginx failed to start, retrying in 5 seconds..."
  sleep 5
done