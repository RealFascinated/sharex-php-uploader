FROM alpine:3.18.2

# Install dependencies
RUN apk update && \
    apk upgrade && \
    apk add --no-cache nginx php81 php81-fpm php81-pear && \
    rm -rf /var/cache/apk/*

# Install Imagick
RUN apk add php82-pecl-imagick --repository=https://dl-cdn.alpinelinux.org/alpine/edge/community
RUN apk --update add imagemagick
RUN pecl install imagick
RUN docker-php-ext-enable imagick

# Set up nginx
COPY ./docker/nginx.conf /etc/nginx/nginx.conf

# Setup scripts
COPY ./upload.php /tmp/upload.php
COPY ./docker/start.sh /start.sh

# Start server
CMD ["sh", "/start.sh"]