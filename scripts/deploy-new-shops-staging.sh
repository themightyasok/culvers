#!/usr/bin/env bash
# Wrapper — safe staging deploy for Clarks, Fraser Hart, Colchester Aesthetics.
# Delegates to society-deploy:
#   • theme code only (uploads/ never bulk-synced)
#   • nine allowlisted media files (--ignore-existing; never overwrites server files)
#   • create-only shop import (no DB dump; no live URL media downloads)
#
#   ./scripts/deploy-new-shops-staging.sh              # dry-run
#   ./scripts/deploy-new-shops-staging.sh --execute

set -euo pipefail

DEPLOY_ROOT="/Users/admin/Work/Society/society-deploy"
SCRIPT="$DEPLOY_ROOT/scripts/20i/push-new-shops.sh"

[ -x "$SCRIPT" ] || chmod +x "$SCRIPT"
[ -f "$SCRIPT" ] || { echo "error: $SCRIPT not found" >&2; exit 1; }

exec "$SCRIPT" --site culvers "$@"
