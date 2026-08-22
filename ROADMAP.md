# Priorisierte Workspace-Roadmap

Stand: 22. August 2026. Diese Roadmap fasst die statische und lokale Prüfung aller
in `config/workspace-components.tsv` registrierten Komponenten zusammen. Das
Inventar enthält sechs Plugins und noch kein eigenes Theme. Jedes Plugin
besitzt ein eigenes Git-Repository unter `repositories/`; die Detailpläne
liegen im jeweiligen Repository.

Die komponentenübergreifende Prüfung von Demo- und Beispieldaten ist in
`docs/demo-data-audit.md` dokumentiert.

## Gesamturteil

**Funktionaler Härtungsstand ohne offene P0-Befunde; Delivery-Gates noch
blockiert.** Struktur, PHP-Syntax,
PHPCS und alle Komponenten-Smokes bestehen. Datenschutz, nichtdestruktive
Deaktivierung, geschützte Exporte, additive Migrationen und zentrale
Nebenläufigkeitsinvarianten sind umgesetzt und lokal mit WordPress/DDEV
verifiziert. Nach dem verbindlich übernommenen BR-starken Qualitätsvertrag sind
CI, gemessene No-Regression-Coverage, ausgefüllte Abnahmeprotokolle und
reproduzierbare Release-Artefakte noch schrittweise nachzuweisen. Bis dahin ist
keine Komponente als Release Candidate freigegeben.

## P0 – Repository-Trennung als Arbeitsgrundlage

1. **Erledigt:** Für jedes Plugin ein eigenes lokales Git-Repository mit
   erhaltener komponentenspezifischer Historie herstellen.
2. **Erledigt:** `tagore-plugins` zum reinen Koordinations-Workspace umbauen:
   relatives Inventar, gemeinsame Skills und Checks, aber kein Plugin-Quellcode.
3. **Erledigt:** Direkte Includes aus Nachbar-Plugins durch native
   WordPress-Abhängigkeiten plus defensive, versionierte Bootstrap-Verträge
   ersetzt.
4. **Erledigt:** Alte KI-/Agenten-Weiterleiter und konkurrierende Dokumentation
   bereinigen; sinnvolle Regeln in den jeweils zuständigen Scope übertragen.
5. Erst danach das unabhängige Theme-Projekt `flz-tagore` gemäß
   `docs/theme-rebuild-roadmap.md` beginnen.

## P0 – vor weiterer fachlicher Erweiterung

1. **Erledigt:** Elternsprechtag und Probeunterricht deaktivieren
   nichtdestruktiv. Lokale Aktivieren–Deaktivieren–Aktivieren-Läufe erhielten
   Tabellen, Rollen, Capabilities und alle Bestandsdatensätze.
2. **Erledigt:** Personenbezogene CSV-Dateien werden capability- und
   nonce-geschützt direkt gestreamt. Die beiden öffentlichen Altdateien wurden
   entfernt und werden nicht erneut erzeugt.
3. **Erledigt:** Der Elternsprechtag-Testmodus verwendet ausschließlich
   `private-test@example.test`; Smokes sperren Gmail-/Googlemail-Adressen.
4. **Erledigt:** Buchung, Gesamt-/Schulkapazität und aktive AG-Anmeldung sind
   transaktional beziehungsweise durch eindeutige Datenbankverträge abgesichert.

## P1 – Sicherheits-, Datenschutz- und Persistenzverträge

1. **Erledigt:** `flz_elternsprechtag`, `flz_probeunterricht` und `flz_ags`
   besitzen WordPress-Privacy-Exporter/-Eraser sowie eine standardmäßig
   deaktivierte, konfigurierbare Aufbewahrung mit 24 Monaten als Vorschlag.
2. **Erledigt:** Die drei Fachplugins verwenden eigene DB-Versionen und
   additive, idempotente Upgradepfade; reale lokale Bestände wurden migriert,
   Alt-Tabellen nicht gelöscht.
3. **Erledigt:** Direkte Includes interner Shared-Dateien durch kleine
   öffentliche Bootstrap-/API-Verträge und defensive Abhängigkeitsprüfung
   ersetzt.
4. **Weitgehend erledigt:** Komponenten-Smokes decken erlaubte und verweigerte
   Upload-, Export-, Formular-, Datenschutz- und Nebenläufigkeitsfälle ab. Ein
   kompletter WordPress-Integrationstest sämtlicher Requestvarianten bleibt P2.
5. **Erledigt:** Redirects verwenden seitengebundene HMAC-Signaturen mit Ablauf,
   `wp_validate_redirect()` und `wp_safe_redirect()`; das Klartext-Secret wurde
   aus Code und bestehender Inhaltsseite entfernt.

## P2 – Wartbarkeit und Nutzeroberfläche

1. Übersetzbare Texte und komponenteneigene Textdomains in allen Plugins
   vervollständigen; Header-Metadaten und Versionsquellen bereinigen.
2. **Erledigt:** `flz_ui_components` registriert Assets global, lädt sie aber
   nur bei Nutzung. Inline-Eventhandler wurden durch Data-Attribute und
   delegierte JavaScript-Handler ersetzt; Vertrags-Smokes sind vorhanden.
3. Controller, Fachlogik, Datenzugriff und Templates in den beiden
   Legacy-Fachplugins schrittweise trennen; keine breite Umschreibung ohne
   vorher gesicherte Invarianten.
4. Browser-, Tastatur-, Responsive- und Kontrastprüfung aller öffentlichen
   Formulare und relevanten Admin-Oberflächen in DDEV dokumentieren.

## P3 – Delivery und kontinuierliche Qualität

1. **Erledigt:** PHPCS/WPCS ist pro Repository reproduzierbar installiert,
   ohne laufende DDEV-Instanz ausführbar und für alle sechs Plugins in
   `scripts/check-fast` integriert.
2. **Vertrag und Rollout konfiguriert:** PHP-/JavaScript-Coverage getrennt
   messen, die erste ehrliche Baseline gegen Rückgang sperren und Altcode
   schrittweise auf 85 Prozent anheben. Neuer oder wesentlich geänderter Code
   erreicht sofort mindestens 85 Prozent; Sicherheitsinvarianten bleiben
   vollständig abzudecken.
3. **Phase 0 erledigt:** Komponentenlokale Abnahmeprotokolle und ein strikt
   blockierendes Release-Vorgate sind vorbereitet. CI, Messwerkzeuge,
   ausgefüllte Abnahmen und reproduzierbare Ein-Wurzel-Artefakte folgen
   komponentenweise gemäß `docs/quality-rollout.md`.

## Reihenfolge und Abhängigkeiten

1. `flz_wpdb_objects` 2.0.0 und danach `flz_ui_components` 0.2.0.
2. `flz_shortcode_redirect` 2.0.0 als unabhängige Breaking-Änderung.
3. `flz_ags` 0.6.0, `flz_elternsprechtag` 1.1.0 und
   `flz_probeunterricht` 1.1.0 nach den Shared-Plugins.
4. Danach Internationalisierung, Browser-/Barrierefreiheitsabnahme,
   Zwei-Prozess-Integration und reproduzierbare Release-Artefakte abschließen.

Änderungen an Schema, Rollen/Capabilities, öffentlichen Shared-APIs, Uploads,
Downloads oder mehreren Komponenten bleiben Stop-Gates: Vor Umsetzung sind
Risiko, Dateien, Tests und Rückbau konkret zu benennen und freizugeben.
