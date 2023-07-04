UPLOAD_SCRIPT="upload.php"

echo "Checking if $UPLOAD_SCRIPT exists in /var/www/html"
if [ -f "$UPLOAD_SCRIPT" ]; then
    echo "$FILE exists, not copying"
else
    cp /var/www/html/$UPLOAD_SCRIPT $UPLOAD_SCRIPT
    echo "$UPLOAD_SCRIPT copied to /var/www/html/$UPLOAD_SCRIPT"
fi

# Start Nginx
echo "Starting Nginx"
nginx -g "daemon off;"