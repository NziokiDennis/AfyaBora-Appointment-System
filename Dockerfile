# Use the official PHP image with Apache
FROM php:8.2-apache

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install dependencies and mysqli extension
RUN apt-get update && \
    apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev libzip-dev zip unzip && \
    docker-php-ext-install mysqli && \
    docker-php-ext-enable mysqli && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy project files into the container
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80 (default HTTP)
EXPOSE 80
