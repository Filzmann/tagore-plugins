#!/usr/bin/env bash
set -euo pipefail

workspace="$(cd "$(dirname "$0")/.." && pwd)"
inventory="$workspace/config/workspace-components.tsv"
quality_config="$workspace/config/quality-gates.tsv"
gate="$workspace/scripts/check-quality-gates"
release_output="$workspace/.quality-release-test.out"

cleanup() {
	rm -f "$release_output"
}
trap cleanup EXIT

fail() {
	echo "FEHLER: $*" >&2
	exit 1
}

for required in \
	"$quality_config" \
	"$gate" \
	"$workspace/docs/quality-gates.md" \
	"$workspace/docs/quality-rollout.md"; do
	[[ -f "$required" ]] || fail "Pflichtdatei fehlt: ${required#"$workspace/"}"
done

bash "$gate" --policy

mapfile -t slugs < <(awk -F '\t' 'NR > 1 { print $3 }' "$inventory")
for slug in "${slugs[@]}"; do
	count="$(awk -F '\t' -v slug="$slug" 'NR > 1 && $1 == slug { count++ } END { print count + 0 }' "$quality_config")"
	[[ "$count" -eq 1 ]] || fail "Quality-Konfiguration braucht genau eine Zeile für $slug."

	protocol="$workspace/repositories/$slug/docs/manual-acceptance.md"
	[[ -f "$protocol" ]] || fail "Abnahmeprotokoll fehlt: repositories/$slug/docs/manual-acceptance.md"
	for heading in \
		'# Manuelles Abnahmeprotokoll' \
		'## Kopfdaten' \
		'## Automatisierte Nachweise' \
		'## Manuelle Prüffälle' \
		'## Abschlussentscheidung'; do
		grep -Fq "$heading" "$protocol" || fail "Abnahmeprotokoll $slug enthält nicht: $heading"
	done
	for contract in \
		'## Commit-, Coverage- und Release-Gates' \
		'docs/manual-acceptance.md' \
		'85 Prozent'; do
		grep -Fq "$contract" "$workspace/repositories/$slug/AGENTS.md" \
			|| fail "Lokale Regeln für $slug enthalten nicht: $contract"
	done
	grep -Fq 'docs/manual-acceptance.md' "$workspace/repositories/$slug/README.md" \
		|| fail "README für $slug verweist nicht auf das Abnahmeprotokoll."
	grep -Fq 'Release-Gate in' "$workspace/repositories/$slug/ROADMAP.md" \
		|| fail "Roadmap für $slug weist die Release-Blockade nicht aus."
	commit_gate="$(awk -F '\t' -v slug="$slug" '$1 == slug { print $3; exit }' "$quality_config")"
	[[ "$commit_gate" == 'enforced' ]] || fail "Commit-Gate ist für $slug nicht enforced."
	if ! bash "$gate" --commit "$slug" >/dev/null; then
		fail "Commit-Gate ist für $slug nicht ausführbar grün."
	fi

	if bash "$gate" --release "$slug" >"$release_output" 2>&1; then
		fail "Noch nicht vollständig übernommener Release-Gate wurde für $slug freigegeben."
	fi
	grep -Fq 'RELEASE BLOCKIERT' "$release_output" \
		|| fail "Release-Blockade für $slug ist nicht eindeutig diagnostizierbar."
	rm -f "$release_output"
done

grep -Fq 'tests/check-quality-gates.sh' "$workspace/scripts/check-fast" \
	|| fail 'Quality-Gate-Vertrag ist nicht in scripts/check-fast eingebunden.'
grep -Fq 'scripts/check-quality-gates --release' "$workspace/.agents/skills/verify-wordpress-workspace/SKILL.md" \
	|| fail 'Release-Verifikation verweist nicht auf den strikten Quality-Gate.'
grep -Fq 'config/quality-gates.tsv' "$workspace/.agents/skills/create-wordpress-extension/SKILL.md" \
	|| fail 'Neue Komponenten erben die Quality-Gate-Konfiguration nicht.'

echo 'Quality-Gate-Vertrag: OK'
