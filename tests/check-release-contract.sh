#!/usr/bin/env bash
set -euo pipefail

workspace="$(cd "$(dirname "$0")/.." && pwd)"
builder="$workspace/scripts/build-component-release"
verifier="$workspace/scripts/verify-component-release"
temporary="$(mktemp -d)"

cleanup() {
	rm -rf "$temporary"
}
trap cleanup EXIT

fail() {
	echo "FEHLER: $*" >&2
	exit 1
}

for executable in "$builder" "$verifier"; do
	[[ -x "$executable" ]] || fail "Release-Werkzeug ist nicht ausführbar: ${executable#"$workspace/"}"
done

slug='flz_shortcode_redirect'
fixture="$temporary/$slug"
mkdir -p "$fixture/docs" "$fixture/includes" "$fixture/tests" "$fixture/vendor" "$fixture/.github"
printf '%s\n' '<?php' '/*' 'Plugin Name: Fixture' 'Version: 1.2.3' '*/' > "$fixture/$slug.php"
printf '%s\n' '# Fixture' > "$fixture/README.md"
printf '%s\n' 'SPDX-License-Identifier: GPL-2.0-or-later' > "$fixture/LICENSE"
printf '%s\n' '# Changelog' '## Unreleased' '## 1.2.3' > "$fixture/CHANGELOG.md"
printf '%s\n' '# Manuelles Abnahmeprotokoll' > "$fixture/docs/manual-acceptance.md"
printf '%s\n' '<?php echo "fixture";' > "$fixture/includes/runtime.php"
printf '%s\n' 'nicht paketieren' > "$fixture/tests/test.php"
printf '%s\n' 'nicht paketieren' > "$fixture/vendor/dev.php"
printf '%s\n' 'nicht paketieren' > "$fixture/.github/workflow.yml"
printf '%s\n' 'nicht paketieren' > "$fixture/AGENTS.md"

git -C "$fixture" init -q
git -C "$fixture" add AGENTS.md CHANGELOG.md LICENSE README.md .github docs includes tests vendor "$slug.php"
git -C "$fixture" -c user.name='Tagore Test' -c user.email='test@example.invalid' \
	commit -q -m 'fixture'

"$builder" "$slug" "$fixture" "$temporary/first"
touch -t 202608220101 "$fixture/$slug.php" "$fixture/includes/runtime.php"
"$builder" "$slug" "$fixture" "$temporary/second"
diff -qr "$temporary/first" "$temporary/second" >/dev/null \
	|| fail 'Release-Artefakte sind bei identischem Commit nicht bytegleich.'

archive="$temporary/first/$slug-1.2.3.zip"
[[ -f "$archive" ]] || fail 'Erwartetes Release-ZIP fehlt.'
[[ "$(unzip -Z1 "$archive" | head -n 1)" == "$slug/" ]] \
	|| fail 'Release-ZIP besitzt nicht genau den erwarteten Wurzelordner.'
for required in \
	"$slug/$slug.php" \
	"$slug/README.md" \
	"$slug/LICENSE" \
	"$slug/CHANGELOG.md" \
	"$slug/docs/manual-acceptance.md"; do
	unzip -Z1 "$archive" | grep -Fxq "$required" || fail "Pflichtinhalt fehlt im ZIP: $required"
done
if unzip -Z1 "$archive" | grep -Eq '(^|/)(\.git|\.github|\.agents|\.codex|tests|vendor|node_modules|scripts)(/|$)|AGENTS[.]md$'; then
	fail 'Release-ZIP enthält Entwicklungs- oder Agentendateien.'
fi
(
	cd "$temporary/first"
	sha256sum --check SHA256SUMS >/dev/null
)
awk -F '\t' -v slug="$slug" '
	NR == 1 {
		valid = ($1 == "slug" && $2 == "version" && $3 == "git_commit" && $4 == "archive" && $5 == "sha256")
	}
	NR == 2 {
		valid = valid && $1 == slug && $2 == "1.2.3" \
			&& length($3) == 40 && $3 ~ /^[0-9a-f]+$/ \
			&& $4 == slug "-1.2.3.zip" \
			&& length($5) == 64 && $5 ~ /^[0-9a-f]+$/
	}
	END { exit !(valid && NR == 2) }
' "$temporary/first/manifest.tsv" || fail 'Release-Manifest ist unvollständig.'

printf '%s\n' 'dirty' >> "$fixture/README.md"
if "$builder" "$slug" "$fixture" "$temporary/dirty" >/dev/null 2>&1; then
	fail 'Releasebau aus einem unsauberen Repository wurde nicht blockiert.'
fi
git -C "$fixture" restore README.md
ln -s README.md "$fixture/includes/link"
git -C "$fixture" add includes/link
git -C "$fixture" -c user.name='Tagore Test' -c user.email='test@example.invalid' \
	commit -q -m 'symlink fixture'
if "$builder" "$slug" "$fixture" "$temporary/symlink" >/dev/null 2>&1; then
	fail 'Symlink im Releaseinhalt wurde nicht blockiert.'
fi

while IFS=$'\t' read -r path kind component _runtime_link; do
	[[ "$path" == 'path' ]] && continue
	[[ "$kind" == 'plugin' ]] || continue
	repo="$workspace/$path"
	[[ -x "$repo/scripts/build-release" ]] || fail "Komponenten-Builder fehlt: $path/scripts/build-release"
	for contract in \
		'TAGORE_WORDPRESS_WORKSPACE' \
		'scripts/build-component-release'; do
		grep -Fq "$contract" "$repo/scripts/build-release" \
			|| fail "Komponenten-Builder für $component enthält nicht: $contract"
	done
	workflow="$repo/.github/workflows/tests.yml"
	for contract in \
		'Release-Artefakt' \
		"verify-component-release $component"; do
		grep -Fq "$contract" "$workflow" \
			|| fail "Release-CI für $component enthält nicht: $contract"
	done
	archive_gate="$(awk -F '\t' -v slug="$component" '$1 == slug { print $12; exit }' "$workspace/config/quality-gates.tsv")"
	[[ "$archive_gate" == 'configured' || "$archive_gate" == 'enforced' ]] \
		|| fail "Release-Artefakt-Gate ist für $component nicht konfiguriert."
done < "$workspace/config/workspace-components.tsv"

echo 'Release-Artefakt-Vertrag: OK'
