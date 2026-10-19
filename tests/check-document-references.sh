#!/usr/bin/env bash
set -euo pipefail
workspace="$(cd "$(dirname "$0")/.." && pwd)"
temporary="$(mktemp -d)"
trap 'rm -rf "$temporary"' EXIT
mkdir -p "$temporary/docs"
printf '# A\n[ok](present.md)\n' > "$temporary/docs/valid.md"
touch "$temporary/docs/present.md"
"$workspace/scripts/check-document-references" >/dev/null
echo 'Dokumentreferenz-Vertrag: OK'
