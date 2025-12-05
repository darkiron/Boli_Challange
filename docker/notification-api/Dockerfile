# Dev Dockerfile for the Symfony notification-api service
# This image expects the application code to be mounted at /var/www/html

FROM php:8.3-cli-alpine AS base

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    APP_ENV=dev \
    TZ=Europe/Paris

RUN apk add --no-cache git bash tzdata icu-data-full icu-libs libzip-dev oniguruma-dev \
    && docker-php-ext-install intl mbstring zip \
    && cp /usr/share/zoneinfo/$TZ /etc/localtime \
    && echo $TZ > /etc/timezone

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Expose port used by PHP built-in server
EXPOSE 8080

# Default command: start PHP dev server if public/ exists, otherwise sleep
CMD if [ -d "public" ]; then \
      php -S 0.0.0.0:8080 -t public; \
    else \
      echo "No Symfony app mounted at /var/www/html. Mount your app directory (e.g., ../notification-api) to develop."; \
      sleep 3600; \
    fi
