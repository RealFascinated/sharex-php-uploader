#!/bin/sh

# TODO: add all the other fallback values for the other variables
if [ -z "$MAX_UPLOAD_SIZE" ]; then
  MAX_UPLOAD_SIZE="8M"  # Default fallback value
  echo "MAX_UPLOAD_SIZE was not set, using default value of ${MAX_UPLOAD_SIZE}"
fi

echo "Checking if upload script exists in /var/www/html"
if [ -f "/var/www/html/upload.php" ]; then
  echo "Upload script was found, ignoring copy."
else
  cp /tmp/upload.php /var/www/html
  echo "Upload script was not found, copying it."
fi

echo "Checking if default index.html exists in /var/www/html"
if [ -f "/var/www/html/index.html" ]; then
  echo "Upload script was found, ignoring copy."
else
  cp /tmp/index.html /var/www/html
  echo "Default index.html was not found, copying it."
fi

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
sed -i "s/client_max_body_size 500M;/client_max_body_size ${MAX_UPLOAD_SIZE};/" /etc/nginx/nginx.conf

# Function to handle signal forwarding and service startup
function start_services() {
  echo "Starting PHP-FPM..."
  php-fpm83 --nodaemonize && chmod 777 /run/php/php.sock &
  PHP_FPM_PID=$!

  echo "Starting Nginx..."
  nginx -g 'daemon off;' &
  NGINX_PID=$!

  # Wait for both processes to finish
  wait $PHP_FPM_PID $NGINX_PID
}

# Trap SIGTERM and SIGINT and forward to PHP-FPM and Nginx
trap "echo 'Stopping services...'; kill -TERM $PHP_FPM_PID $NGINX_PID" SIGTERM SIGINT

# Start the services and retry if Nginx fails
until start_services; do
  echo "Nginx or PHP-FPM failed to start, retrying in 5 seconds..."
  sleep 5
done