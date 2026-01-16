#!/bin/sh

set -e

# Set default values for environment variables
MAX_UPLOAD_SIZE="${MAX_UPLOAD_SIZE:-8M}"
MAX_EXECUTION_TIME="${MAX_EXECUTION_TIME:-300}"
MEMORY_LIMIT="${MEMORY_LIMIT:-512M}"

echo "Configuration: MAX_UPLOAD_SIZE=${MAX_UPLOAD_SIZE}, MAX_EXECUTION_TIME=${MAX_EXECUTION_TIME}, MEMORY_LIMIT=${MEMORY_LIMIT}"

# Copy files if they don't exist
for file in upload.php index.html; do
  if [ ! -f "/var/www/html/${file}" ]; then
    cp "/tmp/${file}" "/var/www/html/${file}"
    echo "Copied ${file} to /var/www/html"
  fi
done

# Configure PHP-FPM
echo "Configuring PHP-FPM..."
echo "env[DOCKER] = true" >> /etc/php83/php-fpm.d/www.conf
echo "clear_env = no" >> /etc/php83/php-fpm.d/www.conf
sed -i 's/^listen = .*/listen = \/run\/php\/php.sock/' /etc/php83/php-fpm.d/www.conf

# Create PHP socket directory
mkdir -p /run/php

# Configure PHP settings
echo "Configuring PHP settings..."
sed -i "s/^upload_max_filesize = .*/upload_max_filesize = ${MAX_UPLOAD_SIZE}/" /etc/php83/php.ini
sed -i "s/^post_max_size = .*/post_max_size = ${MAX_UPLOAD_SIZE}/" /etc/php83/php.ini
sed -i "s/^max_execution_time = .*/max_execution_time = ${MAX_EXECUTION_TIME}/" /etc/php83/php.ini
sed -i "s/^memory_limit = .*/memory_limit = ${MEMORY_LIMIT}/" /etc/php83/php.ini

# Configure Nginx
echo "Configuring Nginx..."
sed -i "s/\${MAX_UPLOAD_SIZE}/${MAX_UPLOAD_SIZE}/" /etc/nginx/nginx.conf

# Set permissions
chmod 777 /var/www/html/upload.php

# Start services
start_services() {
  echo "Starting PHP-FPM..."
  php-fpm83
  
  # Wait for socket to be created, then set permissions
  echo "Waiting for PHP socket..."
  while [ ! -S /run/php/php.sock ]; do
    sleep 0.1
  done
  
  echo "Setting socket permissions..."
  chmod 777 /run/php/php.sock
  
  echo "Starting Nginx..."
  nginx -g 'daemon off;'
}

# Retry on failure
until start_services; do
  echo "Services failed to start, retrying in 5 seconds..."
  sleep 5
done
