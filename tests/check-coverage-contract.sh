#!/usr/bin/env bash
set -euo pipefail

workspace="$(cd "$(dirname "$0")/.." && pwd)"
inventory="$workspace/config/workspace-components.tsv"

fail() {
	echo "FEHLER: $*" >&2
	exit 1
}

require_text() {
	local file="$1"
	local text="$2"
	grep -Fq -- "$text" "$file" || fail "${file#"$workspace/"} enthält nicht: $text"
}

for required in \
	"$workspace/tests/coverage/composer.json" \
	"$workspace/tests/coverage/composer.lock" \
	"$workspace/tests/coverage/package.json" \
	"$workspace/tests/coverage/package-lock.json" \
	"$workspace/tests/coverage/merge-clover.php" \
	"$workspace/tests/coverage/execute-component-smoke.php" \
	"$workspace/scripts/measure-component-php-coverage" \
	"$workspace/scripts/measure-component-js-coverage" \
	"$workspace/scripts/check-coverage-baseline"; do
	[[ -f "$required" ]] || fail "Coverage-Pflichtdatei fehlt: ${required#"$workspace/"}"
done

require_text "$workspace/tests/coverage/composer.json" '"phpunit/phpcov": "^11.0"'
require_text "$workspace/tests/coverage/package.json" '"c8": "12.0.0"'
require_text "$workspace/.gitignore" 'node_modules/'
for executable in \
	"$workspace/scripts/measure-component-php-coverage" \
	"$workspace/scripts/measure-component-js-coverage" \
	"$workspace/scripts/check-coverage-baseline"; do
	[[ -x "$executable" ]] || fail "Coverage-Skript ist nicht ausführbar: ${executable#"$workspace/"}"
done
php -l "$workspace/tests/coverage/merge-clover.php" >/dev/null
php -l "$workspace/tests/coverage/execute-component-smoke.php" >/dev/null
require_text "$workspace/scripts/measure-component-php-coverage" 'php -d output_buffering=16384'

fixture_directory="$workspace/tests/fixtures/coverage"
passing="$(php "$workspace/tests/coverage/merge-clover.php" fixture "$fixture_directory" 50.00)"
[[ "$passing" == $'fixture\t2\t1\t50.00' ]] || fail 'Exakt erreichte PHP-Coverage-Baseline wird nicht akzeptiert.'
if php "$workspace/tests/coverage/merge-clover.php" fixture "$fixture_directory" 50.01 >/dev/null 2>&1; then
	fail 'PHP-Coverage-Rückgang wird nicht blockiert.'
fi
"$workspace/scripts/check-coverage-baseline" flz_ui_components js 54.05 >/dev/null
if "$workspace/scripts/check-coverage-baseline" flz_ui_components js 54.04 >/dev/null 2>&1; then
	fail 'JavaScript-Coverage-Rückgang unter die konfigurierte UI-Baseline wird nicht blockiert.'
fi

while IFS=$'\t' read -r path kind slug _runtime_link; do
	[[ "$path" == 'path' ]] && continue
	[[ "$kind" == 'plugin' || "$kind" == 'theme' ]] || continue
	repo="$workspace/$path"
	workflow="$repo/.github/workflows/tests.yml"
	[[ -f "$workflow" ]] || fail "Workflow fehlt für $slug."
	for text in \
		"php-version: '8.3'" \
		'coverage: xdebug' \
		'repository: Filzmann/tagore-plugins' \
		'composer install --working-dir=tagore-plugins/tests/coverage' \
		'measure-component-php-coverage'; do
		require_text "$workflow" "$text"
	done
done < "$inventory"

for slug in flz_ui_components flz_ags; do
	repo="$workspace/repositories/$slug"
	workflow="$repo/.github/workflows/tests.yml"
	[[ -f "$repo/tests/run-js.mjs" ]] || fail "JavaScript-Test-Runner fehlt für $slug."
	require_text "$repo/scripts/check-fast" 'node tests/run-js.mjs'
	for text in \
		'uses: actions/setup-node@v6' \
		'node-version: 24' \
		'npm ci --prefix tagore-plugins/tests/coverage --ignore-scripts' \
		'measure-component-js-coverage'; do
		require_text "$workflow" "$text"
	done
done

for slug in flz_wpdb_objects flz_shortcode_redirect flz_elternsprechtag flz_probeunterricht; do
	if [[ -f "$workspace/repositories/$slug/tests/run-js.mjs" ]]; then
		fail "Komponente ohne Produkt-JavaScript hat einen künstlichen JS-Coverage-Runner: $slug"
	fi
done

echo 'Coverage-Tooling-Vertrag: OK'
