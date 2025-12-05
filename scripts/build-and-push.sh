#!/usr/bin/env bash
set -euo pipefail

# Variables
REGISTRY="${REGISTRY:-}"            # ex: ghcr.io/darkiron
IMAGE_NAME="${IMAGE_NAME:-notification-api}"
IMAGE_TAG="${IMAGE_TAG:-}"
DOCKERFILE="${DOCKERFILE:-Dockerfile}"

if [[ -z "${REGISTRY}" ]]; then
  echo "[ERREUR] REGISTRY n'est pas défini. Exemple: export REGISTRY=ghcr.io/<org>"
  exit 1
fi

# Déterminer un TAG si absent: git sha court ou timestamp
if [[ -z "${IMAGE_TAG}" ]]; then
  if git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    IMAGE_TAG="$(git rev-parse --short HEAD 2>/dev/null || date +%Y%m%d%H%M%S)"
  else
    IMAGE_TAG="$(date +%Y%m%d%H%M%S)"
  fi
fi

IMAGE_REF="${REGISTRY}/${IMAGE_NAME}:${IMAGE_TAG}"

echo "[INFO] Construction de l'image: ${IMAGE_REF}"
docker build -t "${IMAGE_REF}" -f "${DOCKERFILE}" .

echo "[INFO] Push de l'image: ${IMAGE_REF}"
docker push "${IMAGE_REF}"

echo "[OK] Image poussée: ${IMAGE_REF}"
echo "Astuce: export IMAGE_REF=${IMAGE_REF} et utilisez-le dans deploy.sh"
