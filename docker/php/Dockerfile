FROM php:8.1-fpm-alpine

# Instalar extensiones necesarias para CodeIgniter
RUN apk add --no-cache \
    curl \
    git \
    zip \
    unzip \
    libpng-dev \
    libzip-dev \
    mariadb-client

RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mysqli \
    gd \
    zip

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

# Permisos
RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000
CMD ["php-fpm"]