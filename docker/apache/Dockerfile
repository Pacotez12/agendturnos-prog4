FROM php:8.4-apache

# Argumentos de UID y GID para alinear permisos con el host
ARG UID=1000
ARG GID=1000

RUN (usermod -u ${UID} www-data || true) \
    && (groupmod -g ${GID} www-data || true)

# Mismas extensiones que la arquitectura A (docker/php-fpm)
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    redis \
    zip \
    intl \
    bcmath \
    opcache

# Misma configuracion de PHP que la arquitectura A
COPY docker/php-fpm/php.ini /usr/local/etc/php/conf.d/custom.ini

# mod_php ya viene en la imagen; rewrite para public/.htaccess y headers para las cabeceras de seguridad
RUN a2enmod rewrite headers
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
