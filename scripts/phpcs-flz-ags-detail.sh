#!/usr/bin/env bash
set -euo pipefail

PLUGIN_REPO="/home/filzmann/projects/tagore-plugins"
DDEV_PROJECT="/home/filzmann/projects/tagore-local"

cd "$DDEV_PROJECT"
ddev exec bash -lc "cd '$PLUGIN_REPO' && vendor/bin/phpcs --standard=phpcs.xml.dist -s"
