FROM alpine:3.18.4

# Install build dependencies and required tools
RUN apk update && apk upgrade && \
    apk add --no-cache php81 php81-fpm php81-gd build-base pcre-dev openssl-dev zlib-dev linux-headers

# Download and build the latest version of Nginx from source
WORKDIR /tmp
RUN wget https://nginx.org/download/nginx-1.25.2.tar.gz
RUN tar -xzvf nginx-1.25.2.tar.gz
WORKDIR /tmp/nginx-1.25.2
RUN ./configure --prefix=/usr/local/nginx --sbin-path=/usr/local/sbin/nginx --conf-path=/etc/nginx/nginx.conf
RUN make
RUN make install

# Cleanup unnecessary files
RUN rm -rf /tmp/*
RUN rm -rf /var/cache/apk/*

# Set up nginx
COPY ./docker/nginx.conf /etc/nginx/nginx.conf

# Setup scripts
COPY ./upload.php /tmp/upload.php
COPY ./docker/start.sh /start.sh

# Start server
CMD ["sh", "/start.sh"]