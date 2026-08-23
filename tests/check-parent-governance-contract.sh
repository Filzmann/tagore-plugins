#!/usr/bin/env bash
set -euo pipefail

workspace="$(cd "$(dirname "$0")/.." && pwd)"
inventory="$workspace/config/workspace-components.tsv"
source_file="$workspace/docs/parent-governance-contract.md"
start_marker='<!-- COMPONENT-AGENTS-GOVERNANCE:START -->'
end_marker='<!-- COMPONENT-AGENTS-GOVERNANCE:END -->'
canonical_skills=(
	'work-in-wordpress-extension'
	'test-driven-wordpress-change'
)

fail() {
	echo "Parent-Governance-Vertrag ungültig: $*" >&2
	exit 1
}

[[ -f "$source_file" ]] \
	|| fail 'kanonische Quelle fehlt: docs/parent-governance-contract.md'

contract="$(awk -v start="$start_marker" -v end="$end_marker" '
    $0 == start { capture = 1; next }
    $0 == end { capture = 0; found = 1; next }
    capture { print }
    END { if (!found) exit 1 }
' "$source_file")" \
	|| fail 'kanonischer Block ist nicht vollständig markiert'

[[ "$contract" == *'## Parent-Governance-Vertrag: 1'* ]] \
	|| fail 'Versionskennung fehlt'

while IFS=$'\t' read -r path kind slug runtime_link; do
	[[ "$path" == 'path' ]] && continue
	[[ "$kind" == 'plugin' || "$kind" == 'theme' ]] \
		|| fail "unbekannter Komponententyp für $path: $kind"
	agents="$workspace/$path/AGENTS.md"
	[[ -f "$agents" ]] || fail "AGENTS.md fehlt: $path"
	agents_text="$(<"$agents")"
	[[ "$agents_text" == *"$contract"* ]] \
		|| fail "lokale Governance-Projektion fehlt oder weicht ab: $path/AGENTS.md"

	for skill in "${canonical_skills[@]}"; do
		canonical_skill="$workspace/.agents/skills/$skill/SKILL.md"
		local_skill="$workspace/$path/.agents/skills/$skill/SKILL.md"
		[[ -f "$canonical_skill" && ! -L "$canonical_skill" ]] \
			|| fail "kanonischer Pflicht-Skill fehlt oder ist ein Symlink: $skill"
		[[ -f "$local_skill" && ! -L "$local_skill" ]] \
			|| fail "lokaler Pflicht-Skill fehlt oder ist ein Symlink: $path/$skill"
		cmp -s "$canonical_skill" "$local_skill" \
			|| fail "lokale Skill-Projektion weicht ab: $path/$skill"
	done
done < "$inventory"

echo 'Parent-Governance-Vertrag: OK'
