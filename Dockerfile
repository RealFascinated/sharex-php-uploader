FROM ubuntu:23.04

# Install dependencies
RUN apt update
RUN DEBIAN_FRONTEND=noninteractive \
apt install nginx php8.1 php8.1-fpm php8.1-gd php8.1-imagick -y

# Clean up
RUN apt clean

# Set up nginx
COPY ./docker/nginx.conf /etc/nginx/nginx.conf

# Setup scripts
COPY ./upload.php /tmp/upload.php
COPY ./docker/start.sh /start.sh

# Start server
CMD ["bash", "/start.sh"]