FROM ubuntu:22.04

# Install dependencies
RUN apt update
RUN DEBIAN_FRONTEND=noninteractive \
apt install nginx php-fpm php-gd -y

# Set up nginx
COPY ./docker/nginx.conf /etc/nginx/nginx.conf

# Setup scripts
COPY ./docker/start.sh /start.sh

# Start server
CMD ["bash", "/start.sh"]