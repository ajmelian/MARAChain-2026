#!/bin/bash
set -euo pipefail

echo "=== MARAChain Deploy Production ==="
echo "Date: $(date '+%Y-%m-%d %H:%M:%S')"
echo "Commit: ${CI_COMMIT_SHORT_SHA:-local}"

DEPLOY_PATH="${DEPLOY_PATH:-/var/www/prod}"
TIMESTAMP=$(date '+%Y%m%d-%H%M%S')
RELEASE_PATH="${DEPLOY_PATH}/releases/${TIMESTAMP}"
BACKUP_PATH="${DEPLOY_PATH}/backups"

echo "Backing up database..."
ssh ${VPS_USER}@${VPS_HOST} "mkdir -p ${BACKUP_PATH} && mysqldump -u ${DB_USER:-root} -p'${DB_PASS}' ${DB_NAME:-marachain} > ${BACKUP_PATH}/db-${TIMESTAMP}.sql"

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

echo "Smoke test..."
SMOKE=$(ssh ${VPS_USER}@${VPS_HOST} "curl -s -o /dev/null -w '%{http_code}' https://marachain.com/health" 2>/dev/null || echo "000")
if [ "$SMOKE" != "200" ]; then
    echo "ERROR: Smoke test failed (HTTP ${SMOKE}). Rolling back."
    ssh ${VPS_USER}@${VPS_HOST} "ln -sfn ${DEPLOY_PATH}/releases/previous ${DEPLOY_PATH}/current"
    ssh ${VPS_USER}@${VPS_HOST} "mysql -u ${DB_USER:-root} -p'${DB_PASS}' ${DB_NAME:-marachain} < ${BACKUP_PATH}/db-${TIMESTAMP}.sql"
    exit 1
fi

echo "=== Deploy Production Complete ==="
