# Stage 1: Build Nginx
FROM alpine:3.18.4 as builder

# Install build dependencies and required tools
RUN apk update && apk upgrade && \
    apk add --no-cache build-base pcre-dev openssl-dev zlib-dev linux-headers

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

# Set up nginx
COPY ./docker/nginx.conf /etc/nginx/nginx.conf

# Setup scripts
COPY ./upload.php /tmp/upload.php
COPY ./docker/start.sh /start.sh

# Stage 2: Create a smaller production image
FROM alpine:3.18.4

# Copy Nginx and PHP-FPM binaries and configurations from the builder stage
COPY --from=builder /usr/local/nginx /usr/local/nginx
COPY --from=builder /usr/local/sbin/nginx /usr/local/sbin/nginx
COPY --from=builder /etc/nginx /etc/nginx
COPY --from=builder /etc/php81 /etc/php81
COPY --from=builder /tmp/upload.php /tmp/upload.php
COPY --from=builder /start.sh /start.sh

# Install runtime dependencies
RUN apk update && apk upgrade && \
    apk add --no-cache php81 php81-fpm php81-gd pcre

# Cleanup unnecessary files
RUN rm -rf /var/cache/apk/*

# Start server
CMD ["sh", "/start.sh"]