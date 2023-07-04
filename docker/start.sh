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

echo "Setting permissions for upload script"
chmod 777 /var/www/html/upload.php

# Start php dep
echo "Starting PHP"
service php8.1-fpm start

echo "Setting max upload size to ${MAX_UPLOAD_SIZE}"
sed -i "s/^upload_max_filesize = .*/upload_max_filesize = ${MAX_UPLOAD_SIZE}/" /etc/php/8.1/fpm/php.ini
sed -i "s/^post_max_size = .*/post_max_size = ${MAX_UPLOAD_SIZE}/" /etc/php/8.1/fpm/php.ini

# Restart php to apply changes
echo "Restarting PHP to apply changes for max upload size"
service php8.1-fpm restart

# I don't know how to fix this properly, but it works.
chmod 777 /run/php/php8.1-fpm.sock

# Start Nginx
echo "Starting Nginx"
nginx -g "daemon off;"