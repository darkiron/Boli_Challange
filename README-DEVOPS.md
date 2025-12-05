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
