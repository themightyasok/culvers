#!/usr/bin/env bash
#
# Run WP-CLI or mysql against the Local (WP Engine) site that contains this WordPress tree.
# Uses Local's MySQL socket + bundled PHP/WP-CLI. Does not modify wp-config.php.
#
#   ./scripts/with-local-env.sh wp theme list --status=active
#   ./scripts/with-local-env.sh mysql local -e "SELECT option_value FROM wp_options WHERE option_name='stylesheet'\G"
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# Theme layout: <WP_ROOT>/wp-content/themes/<slug>/scripts/
WP_ROOT="$(cd "$SCRIPT_DIR/../../../.." && pwd)"
if [[ ! -f "$WP_ROOT/wp-load.php" ]]; then
	echo "with-local-env: could not find wp-load.php above ${SCRIPT_DIR}" >&2
	exit 1
fi

SITES_JSON="${HOME}/Library/Application Support/Local/sites.json"
LOCAL_SERVICES="${HOME}/Library/Application Support/Local/lightning-services"
LOCAL_APP_WP_CLI="/Applications/Local.app/Contents/Resources/extraResources/bin/wp-cli"

if [[ ! -f "$SITES_JSON" ]]; then
	echo "with-local-env: Local sites.json missing (${SITES_JSON})." >&2
	exit 1
fi

if [[ ! -d "${LOCAL_APP_WP_CLI}/posix" ]]; then
	echo "with-local-env: Local.app WP-CLI not found (${LOCAL_APP_WP_CLI})." >&2
	exit 1
fi

ARCH="$(uname -m)"
case "$ARCH" in
arm64) PLATFORM="darwin-arm64" ;;
*) PLATFORM="darwin-x64" ;;
esac

eval "$(
	WP_ROOT="$WP_ROOT" \
	SITES_JSON="$SITES_JSON" \
	LOCAL_SERVICES="$LOCAL_SERVICES" \
	PLATFORM="$PLATFORM" \
	python3 <<'PY'
import glob
import json
import os
import pathlib
import sys


def sh_export(name: str, value):
    s = str(value)
    out = "'" + s.replace("'", "'\"'\"'") + "'"
    print(f"{name}={out}")


wp_root = pathlib.Path(os.environ["WP_ROOT"]).resolve()
sites_path = pathlib.Path(os.environ["SITES_JSON"]).expanduser()
services_root = pathlib.Path(os.environ["LOCAL_SERVICES"]).expanduser()
platform = os.environ["PLATFORM"]

with sites_path.open(encoding="utf-8") as handle:
    sites = json.load(handle)

best_sid = None
best_len = -1
best_site = None

for sid, site in sites.items():
    raw = site.get("path") or ""
    base = pathlib.Path(raw).expanduser().resolve()
    try:
        wp_root.relative_to(base)
    except ValueError:
        continue
    ln = len(str(base))
    if ln > best_len:
        best_len = ln
        best_sid = sid
        best_site = site

if not best_sid or not best_site:
    print("with-local-env: no Local site path contains", wp_root, file=sys.stderr)
    sys.exit(1)

mysql_ver = (best_site.get("services") or {}).get("mysql", {}).get("version", "8.0.35")
php_ver = (best_site.get("services") or {}).get("php", {}).get("version", "8.2")

mysql_dirs = sorted(glob.glob(str(services_root / f"mysql-{mysql_ver}*")))
php_dirs = sorted(glob.glob(str(services_root / f"php-{php_ver}*")))
if not mysql_dirs:
    print("with-local-env: no mysql-* package for version", mysql_ver, file=sys.stderr)
    sys.exit(1)
if not php_dirs:
    print("with-local-env: no php-* package for version", php_ver, file=sys.stderr)
    sys.exit(1)

mysql_home = pathlib.Path(mysql_dirs[-1]) / "bin" / platform / "bin"
php_home = pathlib.Path(php_dirs[-1]) / "bin" / platform / "bin"

run_dir = pathlib.Path.home() / "Library/Application Support/Local/run" / best_sid
my_cnf = run_dir / "conf/mysql/my.cnf"
if not my_cnf.is_file():
    print(
        "with-local-env: start this site in Local once (missing",
        my_cnf,
        ")",
        file=sys.stderr,
    )
    sys.exit(1)

sh_export("SITE_ID", best_sid)
sh_export("SITE_RUN", run_dir)
sh_export("MYSQL_HOME", run_dir / "conf/mysql")
sh_export("PHPRC", run_dir / "conf/php")
sh_export("MYSQL_BIN", mysql_home)
sh_export("PHP_BIN", php_home)
PY
)"

export MYSQL_HOME PHPRC SITE_RUN SITE_ID

export WP_CLI_CONFIG_PATH="${LOCAL_APP_WP_CLI}/config.yaml"
export WP_CLI_DISABLE_AUTO_CHECK_UPDATE=1
export PATH="${MYSQL_BIN}:${PHP_BIN}:${LOCAL_APP_WP_CLI}/posix:${PATH}"

cd "$WP_ROOT"
exec "$@"
