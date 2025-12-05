DevOps: environnement Docker Desktop et Kubernetes (FR)

Ce dépôt fournit:
- Un environnement Docker Desktop (Compose) prêt à l’emploi pour le développement local: `notification-api`, `MongoDB`, `mongo-express`, `RabbitMQ`.
- Des manifests Kubernetes pour déployer la stack dans un cluster (namespace, ConfigMap/Secrets exemple, Services, Deployments/StatefulSet, Ingress optionnel).

Important — Normalisation Docker Desktop
- Le fichier Compose est à la racine: `compose.yaml` (recommandation Docker Desktop).
- Le dossier `docker/` est déprécié et n’est plus nécessaire (conservé uniquement s’il reste dans l’historique Git). Tous les chemins ont été adaptés à la racine.

Structure du dépôt
- compose.yaml — définition des services pour le local
- Dockerfile — image de dev pour `notification-api` (PHP 8.3, serveur intégré)
- .env.example — variables d’environnement par défaut (à copier en `.env`)
- k8s/ — manifests Kubernetes (namespace, ConfigMap, Secrets exemple, MongoDB, RabbitMQ, notification-api, Ingress)

Prérequis
- Docker Desktop (Windows/Mac) — sur Windows, activer WSL2
- kubectl (facultatif pour la partie Kubernetes)

Démarrage rapide (local avec Docker Desktop)
1) Copier le fichier d’environnement exemple
   PowerShell (Windows):
   ```powershell
   Copy-Item .env.example .env -Force
   ```
   Adaptez les valeurs dans `.env` si besoin.

2) Démarrer la stack
   ```powershell
   docker compose up -d --build
   ```

3) Services exposés
   - notification-api: http://localhost:${APP_PORT:-8009}
   - MongoDB: localhost:27017
   - mongo-express: http://localhost:8081
   - RabbitMQ (UI): http://localhost:15672 (guest/guest par défaut)

Application Symfony (montage du code)
- Placez votre projet Symfony dans `./notification-api` (mappé sur `/var/www/html` dans le conteneur).
- Le `Dockerfile` démarre le serveur PHP intégré sur le port 8009 si un dossier `public/` est présent.

Variables d’environnement principales
- `MONGODB_URI` et `MESSENGER_TRANSPORT_DSN` sont fournis au conteneur. Branchez-les dans la config Symfony (Doctrine ODM / Messenger).

Commandes utiles
- Voir la configuration résolue: `docker compose config`
- Voir les logs: `docker compose logs -f notification-api`
- Arrêter: `docker compose down`

Kubernetes (contexte docker-desktop ou autre cluster)
Fichiers sous `k8s/`:
- `namespace.yaml` — Namespace: `health-platform`
- `configmap.yaml` — Variables non sensibles
- `secrets.example.yaml` — Secrets exemple (copiez en `secrets.yaml`, à ne pas commiter)
- `mongodb.yaml` — StatefulSet + Service + PVC
- `rabbitmq.yaml` — Deployment + Service
- `notification-api-deployment.yaml` — Deployment (2 replicas, probes) + Service (ClusterIP, port 8009)
- `hpa.yaml` — HorizontalPodAutoscaler (min:2, max:10, CPU 60%)
- `pdb.yaml` — PodDisruptionBudget (minAvailable: 1)
- `ingress.example.yaml` — Ingress (optionnel)

1) Appliquer les ressources de base
   ```powershell
   # Contexte local (si Docker Desktop) : kubectl config use-context docker-desktop
   kubectl apply -f k8s/namespace.yaml
   kubectl apply -f k8s/configmap.yaml
   Copy-Item k8s/secrets.example.yaml k8s/secrets.yaml
   # Éditez k8s/secrets.yaml avec vos vraies valeurs, puis :
   kubectl apply -f k8s/secrets.yaml
   ```

2) Déployer MongoDB et RabbitMQ
   ```powershell
   kubectl apply -f k8s/mongodb.yaml
   kubectl apply -f k8s/rabbitmq.yaml
   ```

3) Construire et déployer l’image `notification-api`
   - Remplacez l’image dans `k8s/notification-api-deployment.yaml` (placeholder: `ghcr.io/example/notification-api:latest`).
   - Construire et pousser votre image (exemple) via le script:
     ```bash
     export REGISTRY=<registry>/<namespace>
     scripts/build-and-push.sh
     # export IMAGE_REF est affiché à la fin (gardez-le pour deploy.sh)
     ```
   - Appliquer le déploiement :
     ```bash
     IMAGE_REF=<registry>/<namespace>/notification-api:<tag>
     NAMESPACE=health-platform scripts/deploy.sh
     # ou
     IMAGE_REF="$IMAGE_REF" scripts/deploy.sh
     ```

4) Ingress (optionnel)
   - Éditez `k8s/ingress.example.yaml` (domaines/hôtes), puis :
   ```powershell
   kubectl apply -f k8s/ingress.example.yaml
   ```

Vérification Kubernetes
- Pods: `kubectl get pods -n health-platform -o wide`
- Services: `kubectl get svc -n health-platform`
- Logs: `kubectl logs -n health-platform deploy/notification-api`

Scripts de monitoring
- `scripts/monitor.sh` propose des alias utiles pour suivre les pods, ressources et logs en continu.

Docker Compose — workers Messenger
- Un service `notification-worker` est inclus et lance `messenger:consume` si votre app Symfony est montée dans `./notification-api`.

Sécurité
- Ne commitez pas de secrets réels. Utilisez `k8s/secrets.example.yaml` comme modèle et gardez `secrets.yaml` hors du VCS.

FAQ
- Pourquoi plus de dossier `docker/` ? Nous standardisons sur Docker Desktop avec `compose.yaml` à la racine pour simplifier les chemins et l’onboarding. La séparation n’apporte pas de valeur ici.

Dépan nage / Problèmes fréquents

- Page vide sur http://localhost:8009/ ou http://localhost:8009/public
  - Cause probable: le serveur PHP intégré n’utilisait pas de routeur Symfony, les URLs ne passaient pas par `public/index.php`.
  - Correctif: le Dockerfile a été mis à jour pour lancer `php -S 0.0.0.0:8009 -t public public/index.php`.
  - Que faire: reconstruisez et redémarrez le service.
    ```powershell
    docker compose up -d --build notification-api
    docker compose logs -f notification-api
    ```
  - Testez: `http://localhost:8009/health` (JSON OK) et `http://localhost:8009/`.

- Erreur Symfony: « The controller for URI "/" is not callable… controller … is private »
  - Cause: par défaut nos contrôleurs n’étaient pas publics/taggés.
  - Correctif appliqué dans `notification-api/config/services.yaml`:
    ```yaml
    services:
      NotificationApi\Presentation\Http\:
        resource: '../src/Presentation/Http/'
        public: true
        tags: ['controller.service_arguments']
    ```
  - Que faire: vider le cache/recharger (en dev, le changement est pris en compte automatiquement). Si besoin:
    ```powershell
    docker compose run --rm -w /var/www/html notification-api php bin/console cache:clear
    docker compose restart notification-api
    ```

- Healthcheck en échec au démarrage
  - Vérifiez les logs de `notification-api` et que votre code Symfony est bien monté dans `./notification-api` avec un dossier `public/`.
  - Vérifiez que le port n’est pas déjà utilisé et que `APP_PORT` dans `.env` correspond bien à `compose.yaml` (8009 par défaut).

- Composer / vendor manquant dans le conteneur
  - Installez les dépendances dans le conteneur (le volume persiste):
    ```powershell
    docker compose run --rm -w /var/www/html notification-api composer install -n --prefer-dist
    ```
  - Vérifiez ensuite la console Symfony:
    ```powershell
    docker compose run --rm -w /var/www/html notification-api php bin/console about
    ```

- Erreur Symfony: « Environment variable not found: APP_VERSION »
  - Cause: la variable `APP_VERSION` n’est pas fournie au conteneur et `.env` n’est pas chargé (ou absent). Nous avons ajouté un repli sécurisé et la variable dans l’infra.
  - Correctifs intégrés:
    - `notification-api/config/services.yaml` utilise maintenant un défaut: `app.version: "%env(default:0.1.0:APP_VERSION)%"`.
    - `compose.yaml` passe `APP_VERSION: ${APP_VERSION:-0.1.0}` aux services `notification-api` et `notification-worker`.
    - `k8s/configmap.yaml` expose `APP_VERSION: "0.1.0"` (à ajuster selon vos releases).
    - `.env.example` ajoute `APP_VERSION=0.1.0`.
  - Que faire: reconstruire et redémarrer l’app.
    ```powershell
    docker compose up -d --build notification-api
    curl -fsS http://localhost:8009/health
    ```
