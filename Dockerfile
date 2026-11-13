# Frontend assets
FROM node:22-bookworm-slim AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# PHP dependencies
FROM php:8.3-cli-bookworm AS php-build
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libicu-dev libzip-dev libonig-dev libxml2-dev \
    libfreetype6-dev libjpeg62-turbo-dev libpng-dev libcurl4-openssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring bcmath exif pcntl intl zip gd opcache \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-progress

# Runtime: Nginx + PHP-FPM in one container
FROM php:8.3-fpm-bookworm AS runtime
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx supervisor libicu72 libzip4 libonig5 libxml2 \
    libfreetype6 libjpeg62-turbo libpng16-16 libcurl4 \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring bcmath exif pcntl intl zip gd opcache \
    && rm -rf /var/lib/apt/lists/* \
    && mkdir -p /run/nginx /var/log/supervisor

WORKDIR /var/www/html
COPY --from=php-build --chown=www-data:www-data /var/www/html /var/www/html
COPY --from=frontend --chown=www-data:www-data /app/public/build /var/www/html/public/build
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/tryout-entrypoint
RUN chmod +x /usr/local/bin/tryout-entrypoint \
    && rm -f /etc/nginx/sites-enabled/default \
    && mkdir -p storage/framework/cache/data storage/framework/sessions \
       storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

ENV APP_ENV=production
EXPOSE 8080
ENTRYPOINT ["tryout-entrypoint"]
CMD ["supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
