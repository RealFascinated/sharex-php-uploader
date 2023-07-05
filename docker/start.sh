if [[ -z "${MAX_UPLOAD_SIZE}" ]]; then
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
echo "env[DOCKER] = true" >> /etc/php/8.1/fpm/pool.d/www.conf
echo "clear_env = no" >> /etc/php/8.1/fpm/pool.d/www.conf

# echo "env[UPLOAD_SECRETS] = ${UPLOAD_SECRETS}" >> /etc/php/8.1/fpm/pool.d/www.conf

# echo "env[UPLOAD_DIR] = ${UPLOAD_DIR}" >> /etc/php/8.1/fpm/pool.d/www.conf
# echo "env[USE_RANDOM_FILE_NAMES] = ${USE_RANDOM_FILE_NAMES}" >> /etc/php/8.1/fpm/pool.d/www.conf
# echo "env[FILE_NAME_LENGTH] = ${FILE_NAME_LENGTH}" >> /etc/php/8.1/fpm/pool.d/www.conf
# echo "env[SHOULD_CONVERT_TO_WEBP] = ${SHOULD_CONVERT_TO_WEBP}" >> /etc/php/8.1/fpm/pool.d/www.conf
# echo "env[WEBP_QUALITY] = ${WEBP_QUALITY}" >> /etc/php/8.1/fpm/pool.d/www.conf
# echo "env[WEBP_THREADHOLD] = ${WEBP_THREADHOLD}" >> /etc/php/8.1/fpm/pool.d/www.conf

echo "Setting permissions for upload script"
chmod 777 /var/www/html/upload.php

echo "Setting max upload size to ${MAX_UPLOAD_SIZE}"

# Set max upload size for php
sed -i "s/^upload_max_filesize = .*/upload_max_filesize = ${MAX_UPLOAD_SIZE}/" /etc/php/8.1/fpm/php.ini
sed -i "s/^post_max_size = .*/post_max_size = ${MAX_UPLOAD_SIZE}/" /etc/php/8.1/fpm/php.ini

# Set max upload size for nginx
sed -i "s/client_max_body_size 500M;/client_max_body_size ${MAX_UPLOAD_SIZE};/" /etc/nginx/nginx.conf

# Start Nginx
echo "Starting PHP & Nginx"
/etc/init.d/php8.1-fpm start \
chmod 777 /run/php/php8.1-fpm.sock \
nginx -g 'daemon off;'