# notification-api — Test Technique (Symfony, MongoDB, RabbitMQ)

Ce dépôt contient le service `notification-api` réalisé pour le test technique (exercices 1 à 5). Il inclut l’application Symfony, l’infrastructure Docker/Kubernetes, les tests et les outils de qualité.

## Sommaire
- [Aperçu rapide](#aperçu-rapide)
- [Structure du dépôt](#structure-du-dépôt)
- [Démarrage rapide (Docker Desktop)](#démarrage-rapide-docker-desktop)
- [Application Symfony](#application-symfony)
- [Tests et couverture](#tests-et-couverture)
- [Qualité de code](#qualité-de-code)
- [DevOps (Docker / Kubernetes)](#devops-docker--kubernetes)
- [Branches et exercices](#branches-et-exercices)
- [Dépannage & limitations connues](#dépannage--limitations-connues)

## Aperçu rapide
- **Stack** : Symfony 7.3, Doctrine MongoDB ODM, Symfony Messenger (RabbitMQ), PHP 8.2+.
- **Fonctionnalités clés** :
  - Document `Notification` avec index (TTL 90 jours), repository avec agrégations complexes.
  - Messages/Handlers Messenger (envoi, bulk, mise à jour de statut, DLQ) avec middleware de métriques.
  - Commande de migration `app:notification:migrate` et commande de test `app:notification:test`.
  - Healthcheck `/health`.
- **Qualité** : PHPStan niveau 8, PHP-CS-Fixer (@Symfony).

## Structure du dépôt
```
.
├── compose.yaml                 # Stack Docker locale (notification-api, MongoDB, RabbitMQ, mongo-express)
├── Dockerfile                   # Image de dev/runtime pour notification-api
├── README-DEVOPS.md             # Guide DevOps détaillé (Docker/K8s)
├── k8s/                         # Manifests Kubernetes (ConfigMap, Secrets exemple, Deployments, HPA, PDB, Ingress)
├── docker/                      # Legacy (déprécié)
├── scripts/                     # build-and-push.sh, deploy.sh, monitor.sh
└── notification-api/            # Application Symfony
    ├── src/                     # Code (Domain, Application, Infrastructure, Presentation)
    ├── config/                  # Configuration Symfony (ODM, Messenger, services, routes)
    ├── tests/                   # Tests unitaires / fonctionnels
    ├── vendor/                  # Dépendances Composer
    ├── README.md                # README spécifique à l’application
    └── ...
```

## Démarrage rapide (Docker Desktop)
Prérequis : Docker Desktop (WSL2 sous Windows). Les commandes suivantes se lancent depuis la racine du dépôt.

```powershell
# 1) Copier l'environnement exemple
Copy-Item .env.example .env -Force

# 2) Démarrer la stack (build + up)
docker compose up -d --build

# 3) Vérifier les logs / healthcheck
docker compose logs -f notification-api
curl -fsS http://localhost:8009/health
```

Services exposés par défaut :
- API Symfony : http://localhost:8009
- MongoDB : localhost:27017
- Mongo Express : http://localhost:8081
- RabbitMQ UI : http://localhost:15672 (guest/guest)

## Application Symfony
- **Doctrine ODM** : multi-bases (default, wellness, maternity) avec document `Notification` et repository dédié.
- **Messenger / RabbitMQ** :
  - Transports `notifications` (topic exchange `notification-api`), `services` (fanout), `failed` (direct).
  - Messages : `SendNotificationMessage`, `BulkNotificationMessage`, `NotificationStatusUpdateMessage`.
  - Handlers : envoi, bulk dispatch, mise à jour de statut multi-managers, dead-letter (retry final + archive), middleware de métriques.
- **Commandes** :
  - `app:notification:migrate [--rollback]` : migration des notifications (versionnage dans `data`).
  - `app:notification:test <userId> <type> [--dry-run]` : envoi de notification de test (FCM simulé).
- **Healthcheck** : `/health` retourne `{ status, timestamp, version }`.

## Tests et couverture
Exécution recommandée depuis la racine avec le conteneur :
```powershell
docker compose run --rm -w /var/www/html notification-api php -d memory_limit=-1 vendor/bin/phpunit --coverage-text
```

Notes :
- Les tests unitaires des handlers et de l’entité passent.
- Les tests fonctionnels dépendant de MongoDB peuvent échouer dans certains environnements Docker à cause du driver MongoDB (`SCRAM_SHA_1` requiert libmongoc compilé avec SSL). Voir la section [Dépannage](#dépannage--limitations-connues).
- Couverture observée (dernière exécution) : ~60% lignes global, 100% sur l’entité et les handlers. L’objectif cible reste ≥ 80%.

## Qualité de code
- **PHPStan** (niveau 8) :
  ```powershell
  docker compose run --rm -w /var/www/html notification-api php -d memory_limit=-1 vendor/bin/phpstan analyse
  ```
- **PHP-CS-Fixer** (@Symfony) :
  ```powershell
  docker compose run --rm -w /var/www/html notification-api vendor/bin/php-cs-fixer fix
  ```

## DevOps (Docker / Kubernetes)
- **Docker Compose** : voir `compose.yaml` (services `notification-api`, `notification-worker`, `mongodb`, `mongo-express`, `rabbitmq`). Le volume `./notification-api` est monté dans `/var/www/html`.
- **Dockerfile** : multi-usage dev/runtime, serveur PHP intégré sur 8009 (router Symfony `public/index.php`).
- **Kubernetes** : manifests dans `k8s/` (Deployment 2 réplicas, HPA, PDB, probes, ConfigMap/Secrets, MongoDB StatefulSet, RabbitMQ). Scripts d’aide dans `scripts/` pour builder/pusher et déployer.

## Branches et exercices
- `feature/exercise-2` : Document `Notification`, repository avec agrégations, commande de migration + tests unitaires.
- `feature/exercise-3` : Messages/Handlers Messenger (RabbitMQ), middleware de métriques, tests unitaires.
- `feature/exercise-5` : Outils de qualité (PHPStan lvl 8, PHP-CS-Fixer) et corrections.
- `feature/readme-root` : README racine (présent document).

## Dépannage & limitations connues
- **MongoDB SCRAM_SHA_1 / SSL** : certains environnements Docker renvoient `AuthenticationException: The SCRAM_SHA_1 authentication mechanism requires libmongoc built with ENABLE_SSL`. Dans ce cas, les tests fonctionnels MongoDB échouent. Contournements possibles :
  - Utiliser une image MongoDB/driver construite avec SSL activé.
  - Forcer un mécanisme sans SSL ou utiliser un serveur Mongo local avec SSL.
- **Chemins Windows/WSL** : lancer les commandes depuis la racine du dépôt (`C:\Users\vincent\Challange_Boli`) pour éviter les erreurs de chemin.
- **Cache Symfony** : en cas d’erreur de cache, nettoyer via `rm -rf notification-api/var/cache/*` ou dans le conteneur `php bin/console cache:clear`.

## Références utiles
- README applicatif : `notification-api/README.md`
- Guide DevOps détaillé : `README-DEVOPS.md`
- Résultats de tests : `notification-api/TEST_RESULTS.md` (le cas échéant)