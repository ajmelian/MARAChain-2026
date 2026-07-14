#!/bin/bash
set -euo pipefail

echo "=== MARAChain Deploy Staging ==="
echo "Date: $(date '+%Y-%m-%d %H:%M:%S')"
echo "Commit: ${CI_COMMIT_SHORT_SHA:-local}"

DEPLOY_PATH="${DEPLOY_PATH:-/var/www/staging}"
TIMESTAMP=$(date '+%Y%m%d-%H%M%S')
RELEASE_PATH="${DEPLOY_PATH}/releases/${TIMESTAMP}"

echo "Creating release directory: ${RELEASE_PATH}"
ssh ${VPS_USER}@${VPS_HOST} "mkdir -p ${RELEASE_PATH}"

echo "Syncing files..."
rsync -avz --delete \
    --exclude '.git' \
    --exclude '.env' \
    --exclude 'writable/db/' \
    --exclude 'writable/cache/' \
    --exclude 'writable/logs/' \
    --exclude 'writable/session/' \
    --exclude 'writable/uploads/' \
    --exclude 'writable/debugbar/' \
    --exclude 'vendor/' \
    --exclude 'node_modules/' \
    --exclude '.opencode/' \
    --exclude 'resources/' \
    --exclude 'docs/' \
    wwwroot/ ${VPS_USER}@${VPS_HOST}:${RELEASE_PATH}/

echo "Installing dependencies..."
ssh ${VPS_USER}@${VPS_HOST} "cd ${RELEASE_PATH} && composer install --no-dev --optimize-autoloader --no-interaction"

echo "Running migrations..."
ssh ${VPS_USER}@${VPS_HOST} "cd ${RELEASE_PATH} && php spark migrate"

echo "Linking current release..."
ssh ${VPS_USER}@${VPS_HOST} "ln -sfn ${RELEASE_PATH} ${DEPLOY_PATH}/current"

echo "=== Deploy Staging Complete ==="
