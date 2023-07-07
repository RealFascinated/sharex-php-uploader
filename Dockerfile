FROM alpine:3.14

# Install dependencies
RUN apk update && \
    apk upgrade && \
    apk add --no-cache nginx php8.1 php8.1-fpm php8.1-gd php8.1-imagick && \
    rm -rf /var/cache/apk/*

# Set up nginx
COPY ./docker/nginx.conf /etc/nginx/nginx.conf

# Setup scripts
COPY ./upload.php /tmp/upload.php
COPY ./docker/start.sh /start.sh

# Start server
CMD ["bash", "/start.sh"]