#!/usr/bin/env bash
set -euo pipefail

workspace="$(cd "$(dirname "$0")/.." && pwd)"
pins="$workspace/security-compliance/scanner-tools.tsv"

fail() {
	echo "Security-Basislinien-Vertrag ungültig: $*" >&2
	exit 1
}

for file in SECURITY.md security-compliance/README.md security-compliance/scanner-tools.tsv \
	scripts/install-security-scanners scripts/run-security-baseline; do
	[[ -f "$workspace/$file" ]] || fail "Datei fehlt: $file"
done
for script in scripts/install-security-scanners scripts/run-security-baseline; do
	[[ -x "$workspace/$script" ]] || fail "Skript ist nicht ausführbar: $script"
done

[[ "$(head -n 1 "$pins")" == $'tool\tversion\tplatform\turl\tsha256\tarchive' ]] \
	|| fail 'Scanner-Pins haben keinen gültigen Header.'
for tool in gitleaks osv-scanner; do
	row="$(awk -F '\t' -v tool="$tool" 'NR > 1 && $1 == tool && $3 == "linux-amd64" { print; exit }' "$pins")"
	[[ -n "$row" ]] || fail "Linux-Pin fehlt: $tool"
	IFS=$'\t' read -r name version platform url sha256 archive <<<"$row"
	[[ "$version" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || fail "Ungültige Version: $tool"
	[[ "$url" == https://github.com/* ]] || fail "Pin hat keine HTTPS-Quelle: $tool"
	[[ "$sha256" =~ ^[a-f0-9]{64}$ ]] || fail "Pin hat keine SHA-256-Prüfsumme: $tool"
done

grep -Fq 'ls-files --cached --others --exclude-standard' "$workspace/scripts/run-security-baseline" \
	|| fail 'Secret-Scan ist nicht auf Git-Quellumfang begrenzt.'
grep -Fq 'composer.lock' "$workspace/scripts/run-security-baseline" \
	|| fail 'Dependency-Scan enthält keine Composer-Lockdateien.'

echo 'Security-Basislinien-Vertrag: OK'
