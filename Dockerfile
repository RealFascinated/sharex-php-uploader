FROM alpine:3.18.2

ADD https://raw.githubusercontent.com/mlocati/docker-php-extension-installer/master/install-php-extensions /usr/local/bin/

# Install dependencies
RUN apk update && \
    apk upgrade && \
    apk add --no-cache nginx php81 php81-fpm && \
    rm -rf /var/cache/apk/*

# Install Imagick
RUN chmod uga+x /usr/local/bin/install-php-extensions && sync && \
    install-php-extensions imagick

# Set up nginx
COPY ./docker/nginx.conf /etc/nginx/nginx.conf

# Setup scripts
COPY ./upload.php /tmp/upload.php
COPY ./docker/start.sh /start.sh

# Start server
CMD ["sh", "/start.sh"]