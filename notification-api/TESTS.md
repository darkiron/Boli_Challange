# 🧪 Tests et Couverture — notification-api

Ce document explique comment exécuter les tests (unitaires et fonctionnels) et comment obtenir la couverture de code. L’objectif minimal est ≥ 80% de lignes couvertes.

## Prérequis
- Docker Desktop (Windows/macOS) — WSL2 activé sur Windows.
- L’image de dev contient déjà PCOV (driver de couverture) activé et limité au dossier `src/`.

## Lancer les tests
Depuis la racine du dépôt:

```powershell
# Démarrer les dépendances si nécessaire (Mongo/Rabbit sont optionnels pour ces tests)
docker compose up -d mongodb rabbitmq

# Exécuter PHPUnit dans le conteneur de l’app Symfony
docker compose run --rm -w /var/www/html notification-api ./vendor/bin/phpunit --testdox
```

Sortie attendue: les tests passent (Unit + Functional).

## Couverture de code
Afficher la couverture en console:

```powershell
docker compose run --rm -w /var/www/html notification-api ./vendor/bin/phpunit --coverage-text
```

- Le driver PCOV est activé dans l’image (fichier `pcov.ini`).
- Le périmètre de couverture est limité à `notification-api/src/`.

Couverture actuelle (à la date de rédaction): 100% classes, 100% méthodes, 100% lignes.

## Structure des tests
- `tests/Unit/` — tests unitaires (contrôleur Health, service Notification, commande CQRS)
- `tests/Functional/` — tests fonctionnels de boot Kernel et wiring (container, routes)
- `phpunit.xml.dist` — configuration PHPUnit (deux suites Unit/Functional, source incluse)
- `tests/bootstrap.php` — bootstrap de tests (variables d’env par défaut + purge cache test)

## Dépannage
- « No code coverage driver available »
  - PCOV est déjà installé/activé dans l’image fournie. Si vous utilisez une autre image, installez PCOV ou Xdebug (mode coverage) avant d’exécuter PHPUnit.
- Erreur « Environment variable not found: APP_VERSION »
  - Le bootstrap définit `APP_VERSION=0.1.0` par défaut pour les tests. Assurez-vous d’utiliser `phpunit.xml.dist` (ne pas surcharger `bootstrap`).
- Cache Symfony incohérent en test
  - Le bootstrap supprime `var/cache/test` avant exécution pour éviter d’anciens artefacts.

## Conseils pour ≥ 80%
- Cibler les classes « non testées » dans le rapport `--coverage-text`.
- Tester le chemin heureux et un cas d’erreur par service.
- Mock des dépendances externes (HTTP FCM, Logger, Cache) pour isoler le domaine.

## Intégration CI (idée)
- Dans une CI (GitHub Actions), exécuter la même commande Docker.
- Pour imposer un seuil, générez un rapport Clover et vérifiez le pourcentage:

```bash
docker compose run --rm -w /var/www/html notification-api \
  ./vendor/bin/phpunit --coverage-clover build/coverage.xml
# Puis parsez build/coverage.xml dans un job CI pour échouer si < 80%
```
