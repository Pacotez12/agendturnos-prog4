FROM php:8.4-fpm-alpine

# Argumentos de UID y GID para alinear permisos con el host
ARG UID=1000
ARG GID=1000

# Utilidades de sistema necesarias
RUN apk add --no-cache shadow bash curl git zip unzip \
    && (usermod -u ${UID} www-data || true) \
    && (groupmod -g ${GID} www-data || true)

# Instalador oficial de extensiones PHP
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Extensiones requeridas: pdo_pgsql, pgsql, redis (phpredis), zip, intl, bcmath, opcache
RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    redis \
    zip \
    intl \
    bcmath \
    opcache

# Binario oficial de Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Configuración personalizada de PHP
COPY docker/php-fpm/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php-fpm/zz-docker.conf /usr/local/etc/php-fpm.d/zz-docker.conf

WORKDIR /var/www/html

CMD ["php-fpm"]
