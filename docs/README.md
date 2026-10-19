# Dokumentationslandkarte

Diese Datei ordnet die Workspace-Dokumentation. Sie ist kein zweiter
Regel- oder Statusspeicher: Für operative Entscheidungen gelten immer die
hier benannten kanonischen Quellen.

## Laufende Verträge

| Datei | Aufgabe | Kanonische Quelle für |
| --- | --- | --- |
| [`workspace.md`](workspace.md) | Aufbau, lokale Laufzeit und Workspace-Werkzeuge | Workspace-Struktur und lokale Prüfpfade |
| [`architecture.md`](architecture.md) | komponentenübergreifende technische Grenzen | Architektur- und Sicherheitsverträge |
| [`parent-governance-contract.md`](parent-governance-contract.md) | synchronisierter Parent-Block für Komponentenregeln | Governance-Projektion in Komponenten-`AGENTS.md` |
| [`quality-gates.md`](quality-gates.md) | Bedeutung und Anforderungen der Quality-Gates | Commit-, Coverage- und Releasevertrag |
| [`quality-rollout.md`](quality-rollout.md) | Übernahmeweg und belegte Meilensteine | Prozess der schrittweisen Gate-Übernahme |
| [`learning-candidates.md`](learning-candidates.md) | noch unentschiedene, belegte Beobachtungen | offene Learning Candidates |

Der aktuelle, maschinenlesbare Gate-Status steht ausschließlich in
[`../config/quality-gates.tsv`](../config/quality-gates.tsv); die registrierten
Komponenten ausschließlich in
[`../config/workspace-components.tsv`](../config/workspace-components.tsv).

## Aktive Planung

| Datei | Aufgabe |
| --- | --- |
| [`plans/theme-rebuild.md`](plans/theme-rebuild.md) | Voraussetzung, Umfang und Abnahmekriterien für das künftige eigene Theme |
| [`../ROADMAP.md`](../ROADMAP.md) | priorisierte, komponentenübergreifende Workspace-Arbeit |

Komponentenbezogene Roadmaps, API- und Migrationshinweise liegen immer im
jeweiligen Komponenten-Repository.

## Abgeschlossene Nachweise und Entscheidungen

| Datei | Aufgabe |
| --- | --- |
| [`records/demo-data-audit.md`](records/demo-data-audit.md) | nachvollziehbarer Nachweis der Demo- und Beispieldatenprüfung vom 22. August 2026 |
| [`records/repository-split.md`](records/repository-split.md) | Entscheidungs- und Umsetzungsprotokoll der abgeschlossenen Repository-Trennung |
| [`flz_wpdb_objects: Härtung 2.0.0`](../repositories/flz_wpdb_objects/docs/records/2.0.0-hardening-migration.md) | historische API-Härtung und Migration des Shared-Plugins 2.0.0 |

Records beschreiben den damaligen Befund oder Entscheid. Sie ändern keine
laufenden Verträge und sind keine aktuelle Handlungsanweisung.
