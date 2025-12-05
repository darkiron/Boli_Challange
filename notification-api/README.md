# 🧩 Exercice 1 — notification-api (Symfony) — DDD + CQRS

Ce document décrit l’architecture et l’usage du service `notification-api` pour l’Exercice 1.

## 🎯 Objectifs
- Service Symfony 7.x structuré en DDD + CQRS.
- Doctrine MongoDB ODM (multi‑bases).
- Symfony Messenger (RabbitMQ).
- Service d’envoi de notifications (FCM) avec rate limiting et logs.
- Endpoint `/health` et commande CLI de test.

## 🏗️ Architecture (DDD + CQRS)

Arborescence (simplifiée):
```
notification-api/
├─ src/
│  ├─ Domain/
│  │  └─ Notification/
│  │     └─ NotificationServiceInterface.php
│  ├─ Application/
│  │  └─ Command/SendNotificationCommand.php
│  ├─ Infrastructure/
│  │  └─ Notification/NotificationService.php
│  └─ Presentation/
│     └─ Http/HealthController.php
├─ config/
│  ├─ packages/
│  │  ├─ doctrine_mongodb.yaml
│  │  ├─ messenger.yaml
│  │  └─ monolog.yaml
│  ├─ routes.yaml
│  └─ services.yaml
├─ public/index.php
├─ bin/console
├─ .env
└─ composer.json
```

- Domain: contrat métier pur (`NotificationServiceInterface`, types/VO à venir).
- Application: commandes CQRS (ex: `SendNotificationCommand`).
- Infrastructure: adapters (implémentation concrète du service d’envoi, persistance Mongo, client FCM, rate limit, logs).
- Presentation: HTTP (controllers) et CLI (commandes) exposant les cas d’usage.

## ⚙️ Configuration

Fichiers clés:
- `config/packages/doctrine_mongodb.yaml` — connexion `MONGODB_URI`, `default_database`, auto_mapping.
- `config/packages/messenger.yaml` — transports AMQP (`notifications`, `services`, `failed`) et routing.
- `config/packages/monolog.yaml` — canal `notification`.
- `config/services.yaml` — autowire/autoconfigure; binding `app.version` depuis `APP_VERSION` (fallback `0.1.0`).
- `config/routes.yaml` — `/health` et `/` → `HealthController::index`.

Variables d’environnement utilisées:
- `APP_ENV`, `APP_DEBUG`, `APP_VERSION` (fallback 0.1.0 si absente)
- `MONGODB_URI` (connexion primaire)
- `MESSENGER_TRANSPORT_DSN` (AMQP vers RabbitMQ)
- `FCM_API_KEY` (clé FCM — à fournir pour l’implémentation réelle)
- `RATE_LIMIT_PER_MINUTE` (par défaut 10)

## 🔌 Lancement en local (Docker Desktop)

Depuis la racine du dépôt:
```
Copy-Item .env.example .env -Force
docker compose up -d --build
```
Endpoints:
- Health: http://localhost:8009/health
- Accueil: http://localhost:8009/

Logs & outils:
```
docker compose logs -f notification-api
docker compose run --rm -w /var/www/html notification-api php bin/console about
```

## 📨 Service d’envoi (FCM) + Rate limiting (à compléter)
- Contrat: `Domain/Notification/NotificationServiceInterface`.
- Implémentation: `Infrastructure/Notification/NotificationService` (actuellement stub qui log et retourne `true`).
- À faire (prochaines itérations):
  - Client HTTP FCM (Authorization Bearer `FCM_API_KEY`).
  - Enum/type `NotificationType` (`alert|reminder|info`).
  - Rate limit via `CacheInterface` (clé par `userId`, TTL 60s), configurable avec `RATE_LIMIT_PER_MINUTE`.
  - Logs dédiés (canal `notification`).

## 🐇 Messenger (RabbitMQ)
Transports configurés dans `messenger.yaml`:
- `notifications`: AMQP (exchange `notification-api` topic, routing `notification.*`) — à détailler dans itération suivante.
- `services`: AMQP (exchange `services` fanout)
- `failed`: `in-memory://` (défaut dev)
Routage: `SendNotificationCommand` → `notifications`.

Worker local (service docker): `notification-worker` lance `messenger:consume` automatiquement si `bin/console` est présent.

## 🧪 Tests
- Guide détaillé: voir `TESTS.md` (exécution, couverture, dépannage, CI).
- Commande rapide: `docker compose run --rm -w /var/www/html notification-api ./vendor/bin/phpunit --coverage-text`
- Couverture actuelle: 100% (classes/méthodes/lignes) — objectif minimal requis: ≥ 80%.

## 👩‍💻 Commande CLI de test (à ajouter)
Spécification: `app:notification:test <userId> <type> [--dry-run]`
- Envoie une notification de test.
- Couleurs et messages clairs via `SymfonyStyle`.
- Option `--dry-run` pour simuler l’envoi sans requête FCM.

## 🩺 Healthcheck
`/health` renvoie:
```json
{ "status": "ok", "timestamp": "<ISO>", "version": "<APP_VERSION>" }
```
La version provient d’`APP_VERSION` (via `app.version`, fallback `0.1.0`).

## 🚧 Roadmap courte
1) Implémentation réelle FCM + rate limiting.
2) ODM: documents + repo Mongo + multi‑bases (test, diabetes, wellness, maternity).
3) Commande CLI `app:notification:test`.
4) Tests unitaires (≥ 80%).
