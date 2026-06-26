#!/usr/bin/env bash
set -euo pipefail

PLUGIN_REPO="/home/filzmann/projects/tagore-plugins"
DDEV_PROJECT="/home/filzmann/projects/tagore-local"

cd "$PLUGIN_REPO"

echo "=== Git status ==="
git status --short

echo
echo "=== PHP syntax check ==="
find \
  flz_elternsprechtag \
  flz_probeunterricht \
  flz_wpdb_objects \
  flz_shortcode_redirect \
  flz_ags \
  flz_ui_components \
  -type f -name '*.php' \
  -print0 \
  | xargs -0 -n1 php -l

echo
echo "=== DDEV status ==="
cd "$DDEV_PROJECT"
ddev describe

echo
echo "=== WordPress own plugin status ==="
ddev wp plugin list --fields=name,status,version --format=table \
  | grep -E 'name|flz_|flz_ags'

echo
echo "=== HTTP status ==="
curl -kI https://tagore-local.ddev.site | sed -n '1,10p'

echo
echo "OK: local checks completed."
