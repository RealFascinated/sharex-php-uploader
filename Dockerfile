FROM ubuntu:22.04

# Install dependencies
RUN apt update
RUN DEBIAN_FRONTEND=noninteractive \
apt install nginx php-fpm php-gd -y

# Set up nginx
COPY ./conf/nginx.conf /etc/nginx/nginx.conf
RUN mkdir -p /var/www/html
COPY ./upload.php /var/www/html/index.php

# Start NGINX
CMD ["nginx", "-g", "daemon off;"]