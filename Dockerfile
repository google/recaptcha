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

# Enable Apache mod_rewrite for modern URL handling if needed
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set the working directory
WORKDIR /var/www/html

# Copy composer files first to leverage Docker cache
COPY composer.json composer.lock ./

# Install project dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy the library source and examples
COPY src/ ./src/
COPY examples/ ./examples/

# Update Apache configuration to serve from the examples directory
RUN sed -ri -e 's!/var/www/html!/var/www/html/examples!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!/var/www/html/examples!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Cloud Run requires Apache to listen on the port defined by the PORT environment variable
# We can use a script or sed to update the port at runtime, or just use 8080 as a standard
RUN sed -i 's/80/8080/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Set permissions for Apache
RUN chown -R www-data:www-data /var/www/html

# Use the default PORT environment variable or fallback to 8080
ENV PORT 8080
EXPOSE 8080

# The entrypoint is already set to apache2-foreground in the base image
