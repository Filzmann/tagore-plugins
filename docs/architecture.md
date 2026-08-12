# Architekturverträge für Tagore WordPress

## Verantwortungsgrenzen

| Ebene | Verantwortung | Nicht hier ablegen |
| --- | --- | --- |
| Fachplugin | Verhalten, persistente Fachmodelle, Berechtigungen, Workflows | globale Theme-Darstellung |
| `flz_wpdb_objects` | kleine gemeinsame Datenbankverträge | fachliche UI oder fremde Tabellenhoheit |
| `flz_ui_components` | wiederverwendbare Rendering-/Blockverträge | Fachlogik oder Berechtigungsentscheidungen |
| Theme | Templates, Styles, Patterns, Präsentation | geschäftskritische Daten und Rollen |
| WordPress-Core | Benutzer, Rollen, Settings, REST, HTTP, Cron, Filesystem | parallele Eigenimplementierungen ohne Entscheidung |

Komponenten kommunizieren über kleine dokumentierte Hooks, Funktionen oder
Services. Sie laden keine internen Dateien eines Geschwister-Plugins und
fragen optionale Abhängigkeiten defensiv ab.

## Request-Grenzen

Jeder schreibende Pfad prüft in dieser Reihenfolge:

1. authentifizierten Akteur und konkrete Capability;
2. Nonce beziehungsweise REST-`permission_callback`;
3. Form, Typ, Bereich und fachliche Gültigkeit der Eingabe;
4. autorisierten Objekt-Scope;
5. atomare oder wiederholbare Änderung;
6. sichere, kontextbezogen escapte Antwort.

REST-Routen dürfen keinen offenen oder pauschal wahren
`permission_callback` für geschützte Daten verwenden. Cron und WP-CLI haben
keinen Browser-Nonce, benötigen aber einen expliziten Vertrauens- und
Idempotenzvertrag.

## Daten und Updates

Jede Komponente besitzt ihre Schema-/Versionsquelle selbst. Gemeinsame
Plugins bieten APIs, greifen aber nicht unaufgefordert auf Tabellen einer
Fachkomponente zu. Nicht triviale Änderungen folgen additivem
Expand–Migrate/Backfill–Contract, sofern Bestandsdaten existieren. Tests
decken frische Installation, relevantes Upgrade, ungültige Altdaten und
Wiederholung ab.

## Theme-Entwicklung

Vor dem Scaffold wird Classic oder Block Theme entschieden. Beide Varianten
brauchen mindestens:

- gültigen `style.css`-Header und eigene Textdomain;
- sichere Asset-Registrierung und lokale Abhängigkeiten;
- responsive, tastaturbedienbare und kontrastreiche Ausgabe;
- dokumentierte Unterstützung für relevante WordPress-Versionen;
- einen Smoke für Header/Struktur und eine visuelle Prüfung in DDEV.

Block Themes validieren zusätzlich `theme.json`, Templates und Parts. Classic
Themes escapen Template-Ausgaben und verwenden Core-Hooks statt Core- oder
Fremdtheme-Dateien zu kopieren. Child Themes werden nur mit geklärter
Lizenz-, Update- und Parent-Verfügbarkeit angelegt.

## Delivery-Grenze

Quellprüfung, lokale Aktivierung und Deployment sind getrennte Gates. Ein
Release-Artefakt enthält genau eine eigene Komponente, keine Tests, lokale
Konfiguration, Secrets, Entwicklungsabhängigkeiten oder Nachbar-Komponenten.
Das bestehende Staging-Skript liefert ausschließlich die registrierten
Plugins; Themes benötigen vor dem ersten Deployment einen eigenen
reproduzierbaren, standardmäßig trockenen Delivery-Pfad.
