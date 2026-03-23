# Use the official PHP image with Apache
FROM php:8.5-apache

# Install system dependencies for PHP extensions
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions required for the library and examples
RUN docker-php-ext-install zip

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set the working directory
WORKDIR /var/www/html

# Copy composer files first
COPY composer.json composer.lock ./

# Install project dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy the library source and examples
COPY src/ ./src/
COPY examples/ ./examples/

# Update Apache configuration to serve from the examples directory correctly
RUN sed -ri -e 's!/var/www/html!/var/www/html/examples!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!/var/www/html/examples!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set ServerName to localhost to avoid startup warnings
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Cloud Run requires Apache to listen on the port defined by the PORT environment variable
RUN sed -i 's/80/8080/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Set permissions for Apache
RUN chown -R www-data:www-data /var/www/html

# Use the default PORT environment variable or fallback to 8080
ENV PORT 8080
EXPOSE 8080
