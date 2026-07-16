#!/bin/bash
# ── systemd Worker Installation Script ─────────────────────────────────
# Installs and enables MARAChain systemd workers and timers.
# Run as root on the production VPS.
#
# Usage: sudo bash scripts/systemd/install.sh

set -euo pipefail

MARACHAIN_USER="${MARACHAIN_USER:-www-data}"
MARACHAIN_PATH="${MARACHAIN_PATH:-/var/www/prod/current}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "=== MARAChain Workers Installation ==="
echo "User: ${MARACHAIN_USER}"
echo "Path: ${MARACHAIN_PATH}"
echo ""

# ── Copy service files ────────────────────────────────────────────────
for service in marachain-notifications marachain-ledger-seal marachain-transfers-expire; do
    cp "${SCRIPT_DIR}/${service}.service" "/etc/systemd/system/"
    echo "  ${service}.service installed"
done

# ── Copy timer files ──────────────────────────────────────────────────
for timer in marachain-notifications marachain-ledger-seal marachain-transfers-expire; do
    cp "${SCRIPT_DIR}/${timer}.timer" "/etc/systemd/system/"
    echo "  ${timer}.timer installed"
done

# ── Reload systemd ────────────────────────────────────────────────────
systemctl daemon-reload

# ── Enable and start notifications (continuous worker) ────────────────
systemctl enable marachain-notifications.service
systemctl start marachain-notifications.service
echo "  marachain-notifications.service started (continuous)"

# ── Enable timers (oneshot workers) ───────────────────────────────────
for timer in marachain-ledger-seal marachain-transfers-expire marachain-notifications; do
    systemctl enable "${timer}.timer"
    systemctl start "${timer}.timer"
    echo "  ${timer}.timer started"
done

echo ""
echo "=== Status ==="
systemctl status marachain-notifications.service --no-pager --lines=5 2>/dev/null || true
echo ""
echo "=== Timers ==="
systemctl list-timers "marachain-*" --no-pager 2>/dev/null || true
echo ""
echo "Installation complete."
