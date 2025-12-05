#!/usr/bin/env bash
set -euo pipefail

# Déploiement Kubernetes — Exercice 4
# Prérequis: kubectl configuré (contexte valide)

NAMESPACE="${NAMESPACE:-health-platform}"
MANIFEST_DIR="${MANIFEST_DIR:-k8s}"
IMAGE_REF="${IMAGE_REF:-}"   # ex: ghcr.io/org/notification-api:abcd123
APPLY_INGRESS="${APPLY_INGRESS:-0}" # 1 pour appliquer l'ingress.example.yaml

echo "[INFO] Namespace: ${NAMESPACE}"
echo "[INFO] Dossier manifests: ${MANIFEST_DIR}"

set -x
kubectl apply -f "${MANIFEST_DIR}/namespace.yaml"
kubectl apply -f "${MANIFEST_DIR}/configmap.yaml"

# Appliquer secrets si présents localement (ne pas commiter vos secrets réels)
if [[ -f "${MANIFEST_DIR}/secrets.yaml" ]]; then
  kubectl apply -f "${MANIFEST_DIR}/secrets.yaml"
else
  echo "[WARN] ${MANIFEST_DIR}/secrets.yaml introuvable. Copiez depuis ${MANIFEST_DIR}/secrets.example.yaml et éditez vos valeurs."
fi

kubectl apply -f "${MANIFEST_DIR}/mongodb.yaml"
kubectl apply -f "${MANIFEST_DIR}/rabbitmq.yaml"
kubectl apply -f "${MANIFEST_DIR}/notification-api-deployment.yaml"

# HPA & PDB
kubectl apply -f "${MANIFEST_DIR}/hpa.yaml"
kubectl apply -f "${MANIFEST_DIR}/pdb.yaml"

# Optionnel: Ingress d'exemple
if [[ "${APPLY_INGRESS}" == "1" ]]; then
  kubectl apply -f "${MANIFEST_DIR}/ingress.example.yaml"
fi
set +x

# Option: fixer l'image si fournie
if [[ -n "${IMAGE_REF}" ]]; then
  echo "[INFO] Mise à jour de l'image du déploiement: ${IMAGE_REF}"
  kubectl set image -n "${NAMESPACE}" deploy/notification-api notification-api="${IMAGE_REF}" --record
fi

echo "[INFO] Attente du déploiement des pods..."
if ! kubectl rollout status -n "${NAMESPACE}" deploy/notification-api --timeout=180s; then
  echo "[ERREUR] Rollout en échec, tentative de rollback"
  kubectl rollout undo -n "${NAMESPACE}" deploy/notification-api || true
  exit 1
fi

echo "[OK] Déploiement terminé"
kubectl get deploy,svc,hpa,pdb -n "${NAMESPACE}"
