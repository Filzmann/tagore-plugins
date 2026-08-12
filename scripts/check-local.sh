#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DDEV_PROJECT="$(cd "$ROOT_DIR/../tagore-local" && pwd)"

cd "$ROOT_DIR"

echo "=== Fast source checks ==="
./scripts/check-fast

echo
echo "=== Runtime symlinks ==="
./scripts/link-local-components

echo
echo "=== DDEV status ==="
cd "$DDEV_PROJECT"
ddev describe

echo
echo "=== Registered custom plugins ==="
plugin_pattern="$(awk -F '\t' '$2 == "plugin" { print $3 }' "$ROOT_DIR/config/workspace-components.tsv" | paste -sd '|' -)"
ddev wp plugin list --fields=name,status,version --format=table \
	| grep -E "name|${plugin_pattern}"

theme_pattern="$(awk -F '\t' '$2 == "theme" { print $3 }' "$ROOT_DIR/config/workspace-components.tsv" | paste -sd '|' -)"
if [[ -n "$theme_pattern" ]]; then
	echo
	echo "=== Registered custom themes ==="
	ddev wp theme list --fields=name,status,version --format=table \
		| grep -E "name|${theme_pattern}"
fi

echo
echo "=== HTTP status ==="
curl -kI https://tagore-local.ddev.site | sed -n '1,10p'

echo
echo "OK: local WordPress checks completed."
