#!/bin/sh

# TODO: add all the other fallback values for the other variables
if [ -z "$MAX_UPLOAD_SIZE" ]; then
  MAX_UPLOAD_SIZE="8M"  # Default fallback value
  echo "MAX_UPLOAD_SIZE was not set, using default value of ${MAX_UPLOAD_SIZE}"
fi

for file in upload.php index.html; do
  echo "Checking if ${file} exists in /var/www/html"
  if [ ! -f "/var/www/html/${file}" ]; then
    cp "/tmp/${file}" /var/www/html
    echo "${file} was not found, copying it."
  fi
done

# Letting php know that we are running in docker
echo "env[DOCKER] = true" >> /etc/php83/php-fpm.d/www.conf
echo "clear_env = no" >> /etc/php83/php-fpm.d/www.conf

# Create the directory for PHP socket
mkdir -p /run/php

# Set php-fpm to listen on socket
touch /run/php/php.sock
sed -i 's/^listen = .*/listen = \/run\/php\/php.sock/' /etc/php83/php-fpm.d/www.conf

echo "Setting permissions for upload script"
chmod 777 /var/www/html/upload.php

echo "Setting max upload size to ${MAX_UPLOAD_SIZE}"

# Set max upload size for php
sed -i "s/^upload_max_filesize = .*/upload_max_filesize = ${MAX_UPLOAD_SIZE}/" /etc/php83/php.ini
sed -i "s/^post_max_size = .*/post_max_size = ${MAX_UPLOAD_SIZE}/" /etc/php83/php.ini

# Set max upload size for nginx
sed -i "s/\${MAX_UPLOAD_SIZE}/${MAX_UPLOAD_SIZE}/" /etc/nginx/nginx.conf

function start() {
  echo "Starting PHP & Nginx"
  php-fpm83 &&
  chmod 777 /run/php/php.sock &&
  nginx -g 'daemon off;'
}

# Start Nginx and retry if it fails
until start; do
  echo "Nginx failed to start, retrying in 5 seconds..."
  sleep 5
done