#!/usr/bin/env bash
set -euo pipefail

workspace="$(cd "$(dirname "$0")/.." && pwd)"
config="$workspace/.codex/config.toml"

fail() {
	echo "Agenten-Routing-Vertrag ungültig: $*" >&2
	exit 1
}

[[ -f "$config" ]] || fail 'Codex-Konfiguration fehlt.'

for agent in routine_worker standard_worker complex_worker critical_reviewer; do
	file="$workspace/.codex/agents/$agent.toml"
	[[ -f "$file" && ! -L "$file" ]] || fail "Rollenprofil fehlt oder ist Symlink: $agent"
	grep -Fq "[agents.$agent]" "$config" || fail "Rolle fehlt in config.toml: $agent"
	grep -Fq "config_file = \"agents/$agent.toml\"" "$config" \
		|| fail "Rollenprofil ist nicht kanonisch referenziert: $agent"
	grep -Fq "name = \"$agent\"" "$file" || fail "Rollenname weicht ab: $agent"
done

for marker in ROUTINE STANDARD COMPLEX CRITICAL_REVIEW ESCALATION_REQUIRED; do
	grep -Fq "$marker" "$workspace/AGENTS.md" \
		|| fail "AGENTS.md beschreibt den Routing-Marker nicht: $marker"
done

grep -Fq 'sandbox_mode = "read-only"' "$workspace/.codex/agents/critical_reviewer.toml" \
	|| fail 'critical_reviewer ist nicht read-only.'
for worker in routine_worker standard_worker complex_worker; do
	grep -Fq 'sandbox_mode = "workspace-write"' "$workspace/.codex/agents/$worker.toml" \
		|| fail "$worker hat keinen workspace-write Sandboxvertrag."
done

echo 'Agenten-Routing-Vertrag: OK'
