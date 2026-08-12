# Priorisierte Workspace-Roadmap

Stand: 12. August 2026. Diese Roadmap fasst die statische Regelprüfung aller
in `config/workspace-components.tsv` registrierten Komponenten zusammen. Das
Inventar enthält sechs Plugins und noch kein eigenes Theme. Jedes Plugin
besitzt ein eigenes Git-Repository unter `repositories/`; die Detailpläne
liegen im jeweiligen Repository.

## Gesamturteil

**Teilweise regelkonform, mit nicht freigabefähigen P0-Befunden.** Struktur,
PHP-Syntax und vorhandene Smoke-Tests bestehen. Datenschutz, Deaktivierung,
geschützte Exporte, Nebenläufigkeit und belastbare WordPress-Integration sind
noch nicht durchgängig regelkonform. Eine Release- oder Deployment-Freigabe
folgt aus dieser Prüfung ausdrücklich nicht.

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

1. **Datenverlust bei Deaktivierung verhindern.**
   `flz_elternsprechtag` und `flz_probeunterricht` löschen beim Deaktivieren
   Tabellen und Rollen. Deaktivierung muss nichtdestruktiv werden; eine spätere
   Deinstallation braucht eine eigene dokumentierte Produktentscheidung,
   ausdrückliche Zustimmung und Tests mit Bestandsdaten.
2. **Personenbezogene Exporte aus öffentlichen Uploads entfernen.**
   Elternsprechtag und Probeunterricht erzeugen CSV-Dateien unter festen Namen
   im öffentlichen Upload-Verzeichnis. Auf nonce- und capability-geschützte,
   direkt gestreamte Downloads ohne persistente öffentliche Datei umstellen.
3. **Produktive/personenbezogene Testadressierung entfernen.**
   Der Testmodus des Elternsprechtags überschreibt Empfänger mit einer privaten
   Gmail-Adresse. Auf lokale, explizit konfigurierte Mail-Capture-Mechanismen
   ohne reale Personendaten umstellen.
4. **Buchungs- und Kapazitätsinvarianten atomar machen.**
   Doppelbuchungen beim Elternsprechtag sowie Überbuchung und inkonsistente
   Sitzplatzzähler beim Probeunterricht durch Transaktionen mit wirksamer
   Sperre/Constraint verhindern. Erlaubte und konkurrierende Negativfälle
   müssen das Ausbleiben verbotener Nebenwirkungen belegen.

## P1 – Sicherheits-, Datenschutz- und Persistenzverträge

1. Für `flz_elternsprechtag`, `flz_probeunterricht` und `flz_ags`
   Aufbewahrungs-, Auskunfts-, Anonymisierungs- und Löschverträge festlegen und
   mit WordPress-Privacy-Hooks sowie idempotenten Routinen umsetzen.
2. Legacy-Schemaänderungen von Elternsprechtag und Probeunterricht mit eigener
   DB-Version und additiven, wiederholbaren Upgradepfaden versehen; frische
   Installation und Upgrade mit synthetischen Bestandsdaten testen.
3. **Erledigt:** Direkte Includes interner Shared-Dateien durch kleine
   öffentliche Bootstrap-/API-Verträge und defensive Abhängigkeitsprüfung
   ersetzt.
4. Für jede Capability-, Nonce-, Upload-, Export- und öffentliche
   Formulargrenze mindestens einen erlaubten und einen verweigerten oder
   manipulierten Integrationstest ergänzen.
5. Den Redirect-Vertrag explizit auf erlaubte Ziele, Secret-Lebenszyklus und
   gewünschtes Verhalten bei fehlendem Secret festlegen und testen.

## P2 – Wartbarkeit und Nutzeroberfläche

1. Übersetzbare Texte und komponenteneigene Textdomains in allen Plugins
   vervollständigen; Header-Metadaten und Versionsquellen bereinigen.
2. `flz_ui_components` nur dort laden, wo Komponenten tatsächlich gerendert
   werden, Inline-Eventhandler abbauen und öffentliche Renderer-/Blockverträge
   mit Verbraucher- und JavaScript-Tests absichern.
3. Controller, Fachlogik, Datenzugriff und Templates in den beiden
   Legacy-Fachplugins schrittweise trennen; keine breite Umschreibung ohne
   vorher gesicherte Invarianten.
4. Browser-, Tastatur-, Responsive- und Kontrastprüfung aller öffentlichen
   Formulare und relevanten Admin-Oberflächen in DDEV dokumentieren.

## P3 – Delivery und kontinuierliche Qualität

1. **Erledigt:** PHPCS/WPCS ist pro Repository reproduzierbar installiert,
   ohne laufende DDEV-Instanz ausführbar und für alle sechs Plugins in
   `scripts/check-fast` integriert.
2. Coverage für neuen oder wesentlich geänderten ausführbaren Code messen und
   die angestrebten 85 Prozent nachweisen; Sicherheitsinvarianten unabhängig
   von der Quote vollständig testen.
3. Pro Plugin ein reproduzierbares Release-Artefakt ohne Nachbarkomponenten,
   Tests, lokale Konfiguration oder Entwicklungsabhängigkeiten verifizieren.

## Reihenfolge und Abhängigkeiten

1. P0 in `flz_elternsprechtag` und `flz_probeunterricht`.
2. Download-/Dateivertrag in `flz_wpdb_objects`, danach beide Verbraucher.
3. Privacy- und Migrationsverträge der drei Fachplugins.
4. Öffentliche Verträge und Tests der Shared-Plugins.
5. Redirect-Härtung, Internationalisierung, UI- und Delivery-Gates.

Änderungen an Schema, Rollen/Capabilities, öffentlichen Shared-APIs, Uploads,
Downloads oder mehreren Komponenten bleiben Stop-Gates: Vor Umsetzung sind
Risiko, Dateien, Tests und Rückbau konkret zu benennen und freizugeben.
