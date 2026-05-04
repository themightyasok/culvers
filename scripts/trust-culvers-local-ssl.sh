#!/usr/bin/env bash
#
# Trust Local's router certificate for culvers.local (System keychain).
# Needed when Local's "Trust" button fails but other Local sites already work.
# Safe for dev-only self-signed Local certs; requires your Mac login password once.
#
set -euo pipefail
CERT="${HOME}/Library/Application Support/Local/run/router/nginx/certs/culvers.local.crt"
if [[ ! -f "$CERT" ]]; then
	echo "Missing certificate: $CERT (start Culvers in Local first)." >&2
	exit 1
fi
echo "Installing trust for: $CERT"
echo "You may be prompted for your macOS administrator password."
sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain-db "$CERT"
echo "Done. Quit and reopen your browser, then open https://culvers.local"
security verify-cert -p ssl -c "$CERT" -n culvers.local || true
