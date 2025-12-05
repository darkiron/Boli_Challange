# Dockerfile optimisé (Exercice 4) pour le service Symfony notification-api
# Multi-stage, PHP 8.4 (alpine), utilisateur non-root, healthcheck, port 8009

FROM php:8.4-cli-alpine AS base

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
        curl \
        $PHPIZE_DEPS; \
    docker-php-ext-configure zip; \
    docker-php-ext-install -j"$(nproc)" intl mbstring zip; \
    pecl install mongodb; \
    docker-php-ext-enable mongodb; \
    pecl install pcov; \
    docker-php-ext-enable pcov; \
    printf "pcov.enabled=1\npcov.directory=/var/www/html/src\n" > /usr/local/etc/php/conf.d/pcov.ini; \
    cp "/usr/share/zoneinfo/${TZ}" /etc/localtime; \
    echo "${TZ}" > /etc/timezone; \
    apk del --no-cache $PHPIZE_DEPS

# Étape utilitaire: Composer (pour build potentiels)
FROM composer:2 AS composer_bin

FROM base AS runtime
# Installer Composer dans l'image runtime (utile en dev)
COPY --from=composer_bin /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Créer un utilisateur non-root
RUN set -eux; \
    addgroup -g 1000 app; \
    adduser -D -u 1000 -G app app; \
    chown -R app:app /var/www/html

USER app

# Port utilisé par le serveur PHP intégré
EXPOSE 8009

# Healthcheck HTTP sur /health (si exposé par l'app)
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
  CMD sh -lc 'curl -fsS http://127.0.0.1:8009/health || exit 1'

# Commande par défaut : démarrer le serveur PHP avec un routeur pour Symfony, sinon attendre
CMD if [ -d "public" ]; then \
      php -S 0.0.0.0:8009 -t public public/index.php; \
    else \
      echo "Aucune application Symfony montée dans /var/www/html. Montez votre dossier (ex: ./notification-api) pour développer."; \
      sleep 3600; \
    fi
