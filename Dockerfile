# Etapa base
FROM php:8.2-apache AS base
RUN apt-get update && apt-get install -y --no-install-recommends \
    ffmpeg git unzip curl libzip-dev libpng-dev libonig-dev libxml2-dev cron \
 && docker-php-ext-install pdo pdo_mysql mbstring zip gd \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN a2enmod rewrite

FROM base AS build

WORKDIR /var/www/html

# Copiamos solo los archivos y directorios esenciales para composer install:
COPY composer.json composer.lock artisan ./
COPY bootstrap/ ./bootstrap/
COPY config/ ./config/
COPY app/ ./app/

COPY routes/ ./routes/ 

# Ejecutamos composer install.
RUN if [ -f composer.json ]; then composer install --no-dev --prefer-dist --no-interaction --ignore-platform-reqs; fi

# Etapa final
FROM base AS production
COPY --from=build /var/www/html/vendor ./vendor
COPY docker/conf/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY . .
RUN chown -R www-data:www-data storage bootstrap/cache
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

CMD ["apache2-foreground"]
