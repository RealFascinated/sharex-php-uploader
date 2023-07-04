echo "Checking if upload script exists in /var/www/html"
if [ -f "/var/www/html/upload.php" ]; then
    echo "Upload script was found, ignoring copy."
else
    cp /tmp/upload.php /var/www/html
    echo "Upload script was not found, copying it."
fi

# Start php dep
echo "Starting PHP"
service php8.1-fpm start
chmod 777 /run/php/php8.1-fpm.sock # I don't know how to fix this properly, but it works.

# Start Nginx
echo "Starting Nginx"
nginx -g "daemon off;"