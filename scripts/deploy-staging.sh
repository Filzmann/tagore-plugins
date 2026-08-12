#!/usr/bin/env bash
set -euo pipefail

REPO_DIR="/home/filzmann/projects/tagore-plugins"
LOCAL_DDEV_DIR="/home/filzmann/projects/tagore-local"

SSH_TARGET="filzmann@staging.tagore-gymnasium.de"
SSH_OPTS="-o AddressFamily=inet"

REMOTE_WP_PATH="/var/www/vhosts/tagore-gymnasium.de/staging.tagore-gymnasium.de"
REMOTE_PLUGIN_PATH="${REMOTE_WP_PATH}/wp-content/plugins"
REMOTE_WP_BIN="/usr/local/bin/wp"
REMOTE_PHP=""
REMOTE_WP=""

APPLY=0
REACTIVATE=0
SKIP_CHECKS=0

mapfile -t PLUGIN_ROWS < <(
  awk -F '\t' '$2 == "plugin" { print $1 "\t" $3 }' \
    "$REPO_DIR/config/workspace-components.tsv"
)
PLUGINS=()
declare -A PLUGIN_PATHS=()
for row in "${PLUGIN_ROWS[@]}"; do
  IFS=$'\t' read -r plugin_path plugin_slug <<< "$row"
  PLUGINS+=("$plugin_slug")
  PLUGIN_PATHS["$plugin_slug"]="$plugin_path"
done
ACTIVATE_ORDER=("${PLUGINS[@]}")
DEACTIVATE_ORDER=()
for ((index = ${#PLUGINS[@]} - 1; index >= 0; index--)); do
  DEACTIVATE_ORDER+=("${PLUGINS[index]}")
done

usage() {
  cat <<USAGE
Usage:
  ./scripts/deploy-staging.sh [--apply] [--reactivate] [--skip-checks]

Default:
  Dry-run only. No remote files are changed.

Options:
  --apply        Actually rsync files to staging.
  --reactivate   Deactivate and activate custom plugins on staging. Requires --apply.
  --skip-checks  Skip local checks.
USAGE
}

find_remote_php() {
  ssh $SSH_OPTS "$SSH_TARGET" 'set -eu
for php_bin in \
  /opt/plesk/php/8.4/bin/php \
  /opt/plesk/php/8.3/bin/php \
  /opt/plesk/php/8.2/bin/php \
  /opt/plesk/php/8.1/bin/php \
  /opt/plesk/php/8.0/bin/php \
  /opt/plesk/php/7.4/bin/php \
  /usr/bin/php \
  /usr/local/bin/php
do
  if [ -x "$php_bin" ]; then
    echo "$php_bin"
    exit 0
  fi
done
command -v php
'
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --apply)
      APPLY=1
      shift
      ;;
    --reactivate)
      REACTIVATE=1
      shift
      ;;
    --skip-checks)
      SKIP_CHECKS=1
      shift
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      echo "Unknown argument: $1" >&2
      usage
      exit 2
      ;;
  esac
done

if [[ "$REACTIVATE" -eq 1 && "$APPLY" -ne 1 ]]; then
  echo "ERROR: --reactivate requires --apply." >&2
  exit 2
fi

cd "$REPO_DIR"

echo "=== Deploy target ==="
echo "SSH: ${SSH_TARGET}"
echo "Remote WP path: ${REMOTE_WP_PATH}"
echo "Mode: $([[ "$APPLY" -eq 1 ]] && echo "APPLY" || echo "DRY-RUN")"
echo "Reactivate: $([[ "$REACTIVATE" -eq 1 ]] && echo "yes" || echo "no")"
echo

echo "=== Git status ==="
git status --short
echo

if [[ -n "$(git status --porcelain)" ]]; then
  echo "ERROR: Working tree is not clean. Commit or stash changes before staging deploy." >&2
  exit 1
fi

for plugin in "${PLUGINS[@]}"; do
  source_dir="$REPO_DIR/${PLUGIN_PATHS[$plugin]}"
  if [[ -n "$(git -C "$source_dir" status --porcelain)" ]]; then
    echo "ERROR: Plugin repository is not clean: $plugin" >&2
    exit 1
  fi
done

echo "=== Remote PHP/WP-CLI ==="
if ! REMOTE_PHP="$(find_remote_php)"; then
  echo "ERROR: Could not find a usable PHP binary on staging." >&2
  echo "Check manually with:" >&2
  echo "  ssh $SSH_OPTS $SSH_TARGET 'command -v php; ls -1 /opt/plesk/php/*/bin/php /usr/bin/php 2>/dev/null'" >&2
  exit 1
fi

REMOTE_WP="${REMOTE_PHP} ${REMOTE_WP_BIN} --path=${REMOTE_WP_PATH}"
echo "Remote PHP: ${REMOTE_PHP}"
echo "Remote WP: ${REMOTE_WP}"
echo

if [[ "$SKIP_CHECKS" -ne 1 ]]; then
  echo "=== Local checks ==="
  ./scripts/check-local.sh
  git diff --check
  echo
fi

echo "=== Remote connectivity ==="
if ! ssh $SSH_OPTS "$SSH_TARGET" "test -d '$REMOTE_PLUGIN_PATH'"; then
  echo "ERROR: Remote plugin path does not exist: ${REMOTE_PLUGIN_PATH}" >&2
  exit 1
fi

if ! remote_plugins="$(ssh $SSH_OPTS "$SSH_TARGET" "$REMOTE_WP plugin list --field=name")"; then
  echo "ERROR: Remote WP-CLI plugin list failed on staging." >&2
  exit 1
fi

printf "%s\n" "$remote_plugins" | grep -E "^flz_" || true
echo

run_remote_wp() {
  local cmd="$1"

  if [[ "$APPLY" -eq 1 ]]; then
    ssh $SSH_OPTS "$SSH_TARGET" "$REMOTE_WP $cmd"
  else
    echo "DRY-RUN remote wp: $REMOTE_WP $cmd"
  fi
}

if [[ "$REACTIVATE" -eq 1 ]]; then
  echo "=== Deactivate custom plugins on staging ==="
  for plugin in "${DEACTIVATE_ORDER[@]}"; do
    echo "Deactivate: $plugin"
    run_remote_wp "plugin deactivate '$plugin' || true"
  done
  echo
fi

echo "=== Rsync custom plugins ==="

RSYNC_FLAGS=(-az --delete)
if [[ "$APPLY" -ne 1 ]]; then
  RSYNC_FLAGS+=(--dry-run)
fi

RSYNC_EXCLUDES=(
  --exclude ".git/"
  --exclude "vendor/"
  --exclude "node_modules/"
  --exclude ".vscode/"
  --exclude "*.log"
  --exclude "*.tmp"
  --exclude "reports/"
  --exclude ".DS_Store"
)

for plugin in "${PLUGINS[@]}"; do
  source_dir="$REPO_DIR/${PLUGIN_PATHS[$plugin]}"
  if [[ ! -d "$source_dir" ]]; then
    echo "ERROR: Local plugin directory missing: $plugin" >&2
    exit 1
  fi

  echo "Deploy plugin: $plugin"

  if [[ "$APPLY" -eq 1 ]]; then
    ssh $SSH_OPTS "$SSH_TARGET" "mkdir -p '$REMOTE_PLUGIN_PATH/$plugin'"
  else
    echo "DRY-RUN remote mkdir: $REMOTE_PLUGIN_PATH/$plugin"
  fi

  rsync "${RSYNC_FLAGS[@]}" "${RSYNC_EXCLUDES[@]}" \
    -e "ssh $SSH_OPTS" \
    "$source_dir/" \
    "$SSH_TARGET:$REMOTE_PLUGIN_PATH/$plugin/"
done

echo

if [[ "$REACTIVATE" -eq 1 ]]; then
  echo "=== Activate custom plugins on staging ==="
  for plugin in "${ACTIVATE_ORDER[@]}"; do
    echo "Activate: $plugin"
    run_remote_wp "plugin activate '$plugin'"
  done
  echo
fi

echo "=== Staging plugin status ==="
run_remote_wp "plugin list --status=active --field=name | grep -E '^flz_' || true"

echo
echo "OK: staging deploy script completed."
