# Stage 1: Build Nginx
FROM alpine:3.20.3 AS builder

# Variables
ARG NGINX_VERSION="1.27.1"

# Install build dependencies and required tools
RUN apk update && apk upgrade && \
    apk add --no-cache build-base pcre-dev openssl-dev zlib-dev linux-headers

# Download and build Nginx from source
WORKDIR /tmp
RUN wget https://nginx.org/download/nginx-${NGINX_VERSION}.tar.gz && \
    tar -xzvf nginx-${NGINX_VERSION}.tar.gz && \
    cd nginx-${NGINX_VERSION} && \
    ./configure --prefix=/usr/local/nginx --sbin-path=/usr/local/sbin/nginx --conf-path=/etc/nginx/nginx.conf && \
    make > /dev/null 2>&1 && \
    make install > /dev/null 2>&1 && \
    make_status=$? && \
    if [ $make_status -ne 0 ]; then echo "Nginx build failed"; exit $make_status; fi

# Cleanup unnecessary files
RUN rm -rf /tmp/*

# Set up nginx
COPY ./docker/nginx.conf /etc/nginx/nginx.conf

# Setup scripts
COPY ./upload.php /tmp/upload.php
COPY ./docker/start.sh /start.sh
COPY ./docker/index.html /tmp/index.html

# Copy public directory
COPY ./public /tmp/public

# Stage 2: Create a smaller production image
FROM alpine:3.20.3 

# Copy Nginx and PHP-FPM binaries and configurations from the builder stage
COPY --from=builder /usr/local/nginx /usr/local/nginx
COPY --from=builder /usr/local/sbin/nginx /usr/local/sbin/nginx
COPY --from=builder /etc/nginx /etc/nginx
COPY --from=builder /tmp/upload.php /tmp/upload.php
COPY --from=builder /tmp/index.html /tmp/index.html
COPY --from=builder /start.sh /start.sh
COPY --from=builder /tmp/public /tmp/public

# Install runtime dependencies
RUN apk update && apk upgrade && \
    apk add --no-cache php83 php83-fpm php83-gd pcre

# Cleanup unnecessary files
RUN rm -rf /var/cache/apk/*

# Start server
CMD ["sh", "/start.sh"]
