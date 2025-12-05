# Dockerfile de développement pour le service Symfony notification-api
# Cette image attend que le code de l’application soit monté dans /var/www/html

FROM php:8.3-cli-alpine AS base

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    APP_ENV=dev \
    TZ=Europe/Paris

RUN set -eux; \
    apk add --no-cache \
        bash \
        git \
        tzdata \
        icu-dev \
        icu-libs \
        libzip-dev \
        zlib-dev \
        oniguruma-dev \
        $PHPIZE_DEPS; \
    docker-php-ext-configure zip; \
    docker-php-ext-install -j"$(nproc)" intl mbstring zip; \
    cp "/usr/share/zoneinfo/${TZ}" /etc/localtime; \
    echo "${TZ}" > /etc/timezone; \
    apk del --no-cache $PHPIZE_DEPS

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Port utilisé par le serveur PHP intégré
EXPOSE 8080

# Commande par défaut : démarrer le serveur PHP si public/ existe, sinon attendre
CMD if [ -d "public" ]; then \
      php -S 0.0.0.0:8080 -t public; \
    else \
      echo "Aucune application Symfony montée dans /var/www/html. Montez votre dossier (ex: ./notification-api) pour développer."; \
      sleep 3600; \
    fi
