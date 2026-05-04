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
# Use the actual system keychain path (differs by OS: System.keychain vs System.keychain-db).
SYSTEM_KC=""
while read -r line; do
	line="${line//\"/}"
	line="${line// /}"
	[[ -n "$line" ]] && [[ -e "$line" ]] && SYSTEM_KC="$line" && break
done < <(security list-keychains -d system 2>/dev/null || true)
if [[ -z "$SYSTEM_KC" ]]; then
	for cand in /Library/Keychains/System.keychain-db /Library/Keychains/System.keychain; do
		[[ -e "$cand" ]] && SYSTEM_KC="$cand" && break
	done
fi
if [[ -z "$SYSTEM_KC" ]]; then
	echo "Could not find system keychain under /Library/Keychains/." >&2
	exit 1
fi
sudo security add-trusted-cert -d -r trustRoot -k "$SYSTEM_KC" "$CERT"
echo "Done. Quit and reopen your browser, then open https://culvers.local"
echo ""
echo "Sanity check (quiet): SSL policy for culvers.local against this cert file."
echo "Note: Extended Validation and Certificate Transparency / SCT messages from"
echo "'security verify-cert' without -q are normal for Local's self-signed certs —"
echo "they are not used on the public web and have no CT logs."
if security verify-cert -q -p ssl -c "$CERT" -n culvers.local 2>/dev/null; then
	echo "verify-cert: OK"
else
	echo "verify-cert: not OK yet (try quitting the browser, or reboot once). Real test:"
	echo "  curl -fsSI https://culvers.local"
fi
