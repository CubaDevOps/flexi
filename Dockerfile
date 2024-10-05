# Base image with PHP and Apache
FROM php:7.4-apache

# Install required dependencies for Composer
RUN apt-get update && apt-get install -y \
    nano \
    git \
    unzip \
    libzip-dev \
    libjson-c-dev

# Install PHP extensions
RUN docker-php-ext-install zip json pcntl

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY . /var/www/html

VOLUME /var/www/html

# Set working directory
WORKDIR /var/www/html

# Install project dependencies using Composer
RUN composer install --no-interaction --optimize-autoloader

RUN pecl install xdebug-3.1.0 && docker-php-ext-enable xdebug

RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini

# Configure Apache server
RUN a2enmod rewrite

# Expose port 80 or wathever port you want
EXPOSE 80

# Run Apache server in the foreground
CMD ["apache2-foreground"]
