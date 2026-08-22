#!/usr/bin/env bash
set -euo pipefail

workspace="$(cd "$(dirname "$0")/.." && pwd)"
inventory="$workspace/config/workspace-components.tsv"
quality_config="$workspace/config/quality-gates.tsv"

fail() {
	echo "FEHLER: $*" >&2
	exit 1
}

require_text() {
	local file="$1"
	local text="$2"
	grep -Fq -- "$text" "$file" || fail "${file#"$workspace/"} enthält nicht: $text"
}

mapfile -t slugs < <(awk -F '\t' 'NR > 1 && ($2 == "plugin" || $2 == "theme") { print $3 }' "$inventory")

for slug in "${slugs[@]}"; do
	repo="$workspace/repositories/$slug"
	workflow="$repo/.github/workflows/tests.yml"

	for required in "$workflow" "$repo/composer.lock" "$repo/LICENSE" "$repo/CHANGELOG.md"; do
		[[ -f "$required" ]] || fail "Phase-1-Pflichtdatei fehlt: ${required#"$workspace/"}"
	done

	for text in \
		'branches: [main]' \
		'pull_request:' \
		'contents: read' \
		'cancel-in-progress: true' \
		'runs-on: ubuntu-24.04' \
		'timeout-minutes: 10' \
		"php-version: ['8.1', '8.5']" \
		'uses: actions/checkout@v7' \
		'uses: shivammathur/setup-php@v2' \
		'coverage: none' \
		'tools: composer:v2' \
		'composer install' \
		'--no-interaction --no-progress --prefer-dist' \
		'./scripts/check-fast'; do
		require_text "$workflow" "$text"
	done
	if grep -Eq '^[[:space:]]+[a-zA-Z0-9_-]+:[[:space:]]+write([[:space:]]|$)' "$workflow"; then
		fail "${workflow#"$workspace/"} fordert eine nicht begründete Schreibberechtigung an."
	fi

	require_text "$repo/LICENSE" 'SPDX-License-Identifier: GPL-2.0-or-later'
	require_text "$repo/CHANGELOG.md" '## Unreleased'
	require_text "$repo/AGENTS.md" 'Übernahmestand ist Phase 1'
	require_text "$repo/docs/manual-acceptance.md" '| PR-/`main`-CI / PHP 8.1 und 8.5 |'

	read -r phase ci_gate < <(
		awk -F '\t' -v slug="$slug" '$1 == slug { print $2, $4; exit }' "$quality_config"
	)
	[[ "$phase" == '1' ]] || fail "Quality-Phase für $slug ist nicht 1: $phase"
	[[ "$ci_gate" == 'configured' || "$ci_gate" == 'enforced' ]] \
		|| fail "CI-Gate für $slug ist weder configured noch enforced: $ci_gate"
done

for slug in flz_elternsprechtag flz_probeunterricht flz_ags; do
	workflow="$workspace/repositories/$slug/.github/workflows/tests.yml"
	for provider in flz_wpdb_objects flz_ui_components; do
		require_text "$workflow" "repository: Filzmann/$provider"
	done
	for contract in \
		'CANDIDATE_REF: ${{ github.head_ref || github.ref_name }}' \
		'git ls-remote --exit-code --heads' \
		'echo "ref=main" >> "$GITHUB_OUTPUT"'; do
		require_text "$workflow" "$contract"
	done
done

for slug in flz_wpdb_objects flz_ui_components; do
	workflow="$workspace/repositories/$slug/.github/workflows/tests.yml"
	for consumer in flz_elternsprechtag flz_probeunterricht flz_ags; do
		require_text "$workflow" "repository: Filzmann/$consumer"
	done
	for contract in \
		'Verbrauchertests' \
		'CANDIDATE_REF: ${{ github.head_ref || github.ref_name }}' \
		'git ls-remote --exit-code --heads' \
		'=main" >> "$GITHUB_OUTPUT"'; do
		require_text "$workflow" "$contract"
	done
done

root_workflow="$workspace/.github/workflows/tests.yml"
[[ -f "$root_workflow" ]] || fail 'Workspace-CI fehlt: .github/workflows/tests.yml'
for text in \
	'branches: [main]' \
	'pull_request:' \
	'contents: read' \
	'cancel-in-progress: true' \
	'timeout-minutes: 15' \
	'CANDIDATE_REF: ${{ github.head_ref || github.ref_name }}' \
	'git ls-remote --exit-code --heads' \
	'./scripts/check-fast'; do
	require_text "$root_workflow" "$text"
done
for slug in "${slugs[@]}"; do
	require_text "$root_workflow" "repository: Filzmann/$slug"
done

echo 'CI-Vertrag: OK'
