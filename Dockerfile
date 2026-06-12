FROM php:8.4-cli

ARG COMPOSER_DEV=true

RUN apt-get update \
    && apt-get install -y libpq-dev git unzip curl \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock ./

RUN if [ "$COMPOSER_DEV" = "false" ]; then \
        composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts; \
    else \
        composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts; \
    fi

COPY . .

RUN composer dump-autoload --optimize \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

EXPOSE 8000

HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD curl -fsS http://127.0.0.1:8000/api/health || exit 1

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
