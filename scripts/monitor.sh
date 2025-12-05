#!/usr/bin/env bash
set -euo pipefail

NAMESPACE="${NAMESPACE:-health-platform}"

echo "[INFO] Contexte: $(kubectl config current-context 2>/dev/null || echo 'inconnu')"
echo "[INFO] Namespace: ${NAMESPACE}"

echo "\n== Pods =="
kubectl get pods -n "${NAMESPACE}" -o wide

echo "\n== Deployments =="
kubectl get deploy -n "${NAMESPACE}"

echo "\n== HPA =="
kubectl get hpa -n "${NAMESPACE}" || true

echo "\n== PDB =="
kubectl get pdb -n "${NAMESPACE}" || true

echo "\n== Ressources (CPU/Mémoire) =="
kubectl top pods -n "${NAMESPACE}" || echo "kubectl-metrics-server requis pour 'kubectl top'"

echo "\n== Logs notification-api (10 dernières lignes par pod) =="
for pod in $(kubectl get pods -n "${NAMESPACE}" -l app=notification-api -o jsonpath='{.items[*].metadata.name}'); do
  echo "--- $pod ---"
  kubectl logs -n "${NAMESPACE}" "$pod" --tail=10 || true
done

echo "\nAstuce: suivre en continu -> kubectl logs -n ${NAMESPACE} deploy/notification-api -f"
