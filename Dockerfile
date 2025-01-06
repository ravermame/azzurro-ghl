# Composer dependencies
FROM composer AS composer_build

WORKDIR /app
COPY composer.* ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-scripts \
    --no-plugins \
    --no-progress \
    --ignore-platform-reqs

# Install all node_modules, including dev dependencies
FROM node:20-alpine AS npm-build

RUN mkdir -p /app/public
COPY package.json package-lock.json* /app/
COPY resources /app/resources

WORKDIR /app

RUN yarn install --production --ignore-scripts --prefer-offline --frozen-lockfile

# Use the official PHP image as a base
FROM php:8.2.20-fpm-alpine
 
# Set working directory
WORKDIR /app
 
# Install system dependencies
RUN apk update  && apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    musl-locales \
    zip \
    unzip \
    git \
    curl \
    oniguruma-dev \
    libzip-dev \
    libpq-dev \
    && docker-php-ext-configure gd \
    --with-freetype=/usr/include/ \
    --with-jpeg=/usr/include/ \
    && docker-php-ext-install pdo pdo_pgsql opcache mbstring exif pcntl bcmath gd zip

# OPCache
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/opcache.ini $PHP_INI_DIR/conf.d/

# PHP-FPM configs
RUN sed -i 's/pm.max_children = .*/pm.max_children = 128/' /usr/local/etc/php-fpm.d/www.conf
RUN sed -i 's/pm.start_servers = .*/pm.start_servers = 4/' /usr/local/etc/php-fpm.d/www.conf
RUN sed -i 's/pm.min_spare_servers = .*/pm.min_spare_servers = 2/' /usr/local/etc/php-fpm.d/www.conf
RUN sed -i 's/pm.max_spare_servers = .*/pm.max_spare_servers = 12/' /usr/local/etc/php-fpm.d/www.conf
 
# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer


COPY --from=composer_build --chown=www-data /app/vendor/ /app/vendor/
COPY --from=npm-build --chown=www-data /app/public /app/public
COPY  --chown=www-data . /app/

RUN composer dump -o \
    && composer check-platform-reqs \
    && rm -f /usr/bin/composer

# Copy custom entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Default entrypoint
ENTRYPOINT ["entrypoint.sh"]

CMD ["php-fpm"]
