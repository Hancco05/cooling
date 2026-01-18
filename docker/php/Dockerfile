# Dockerfile para Cooling - PHP 8.1 con extensiones necesarias
FROM php:8.1-fpm-alpine

# Etiquetas informativas
LABEL maintainer="Cooling Team"
LABEL version="1.0"
LABEL description="PHP environment for Cooling project"

# Instalar dependencias del sistema
RUN apk update && apk upgrade && \
    apk add --no-cache \
    bash \
    curl \
    git \
    wget \
    nano \
    vim \
    zip \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libxml2-dev \
    oniguruma-dev \
    openssl-dev \
    pkgconfig \
    mysql-client \
    redis \
    supervisor

# Instalar extensiones PHP para Cooling
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    mysqli \
    gd \
    zip \
    mbstring \
    xml \
    json \
    bcmath \
    opcache \
    sockets

# Instalar extensiones PECL
RUN pecl install redis && docker-php-ext-enable redis

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

# Configurar PHP para Cooling
COPY php.ini /usr/local/etc/php/conf.d/custom.ini

# Crear usuario para Cooling
RUN addgroup -g 1000 cooling && \
    adduser -u 1000 -G cooling -s /bin/sh -D cooling

# Configurar directorio de trabajo
WORKDIR /var/www/html

# Cambiar permisos
RUN chown -R cooling:cooling /var/www/html

# Cambiar usuario
USER cooling

# Puerto expuesto
EXPOSE 9000

# Comando por defecto
CMD ["php-fpm"]