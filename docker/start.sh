UPLOAD_SCRIPT="upload.php"

echo "Checking if $UPLOAD_SCRIPT exists in /var/www/html"
if [ -f "$UPLOAD_SCRIPT" ]; then
    echo "Upload script was found, ignoring copy."
else
    cp /var/www/html/$UPLOAD_SCRIPT $UPLOAD_SCRIPT
    echo "Upload script was not found, copying it."
fi

# Start Nginx
echo "Starting Nginx"
nginx -g "daemon off;"