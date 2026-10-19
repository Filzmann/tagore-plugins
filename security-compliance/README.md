# Security-Basislinie

Die Basislinie prüft den aktuellen Quellbaum auf versehentlich enthaltene
Secrets und alle registrierten Composer-Lockdateien auf bekannte
Schwachstellen. Sie ist zunächst diagnostisch: Ein erfolgreicher Lauf ist kein
Releaseurteil und ersetzt keine komponentenspezifischen Sicherheits- und
WordPress-Prüfungen.

[`scanner-tools.tsv`](scanner-tools.tsv) ist die einzige Quelle für
Version, Download-URL und SHA-256 der Scanner. `scripts/install-security-scanners`
prüft das Artefakt vor der Ausführung. Ausnahmen und ein blockierendes
CI-Gate werden erst nach einem bewusst geprüften Baseline-Lauf ergänzt; bis
dahin wird kein Fund still unterdrückt.
