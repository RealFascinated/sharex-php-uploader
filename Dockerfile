FROM nginx:1.29.3-alpine

# Install PHP-FPM and required dependencies
RUN apk update && apk upgrade && \
    apk add --no-cache php83 php83-fpm php83-gd

# Cleanup unnecessary files
RUN rm -rf /var/cache/apk/*

# Copy nginx configuration
COPY ./docker/nginx.conf /etc/nginx/nginx.conf

# Copy application files
COPY ./upload.php ./docker/index.html /tmp/
COPY ./docker/start.sh /start.sh

# Make start script executable
RUN chmod +x /start.sh

# Start server
CMD ["sh", "/start.sh"]