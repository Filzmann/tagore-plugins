# AGENTS.md – Tagore WordPress

## Zweck und Routing

Dieses Repository ist der Koordinations-Workspace für eigenständige
WordPress-Plugin- und Theme-Repositories des Tagore-Gymnasiums. Die lokale
WordPress-Laufzeit bleibt getrennt unter `../tagore-local`; WordPress-Core,
Uploads und fremde Erweiterungen gehören nicht in dieses Repository.

- Menschlicher Einstieg: `README.md`
- Workspace und lokale Laufzeit: `docs/workspace.md`
- Architektur und WordPress-Verträge: `docs/architecture.md`
- Wiederholbare Abläufe: `.agents/skills/`
- Kanonisches Komponenten-Inventar: `config/workspace-components.tsv`
- Unverbindliche Beobachtungen: `docs/learning-candidates.md`

Vor Arbeit an einer bestehenden Komponente ist der Skill
`work-in-wordpress-extension` zu verwenden. Für beobachtbare Änderungen gilt
zusätzlich `test-driven-wordpress-change`. Neue Plugins oder Themes werden nur
mit `create-wordpress-extension` angelegt. Workspace-Prüfungen folgen
`verify-wordpress-workspace`.

## Geltungsbereich und Grenzen

- Eigener Quellcode liegt ausschließlich in den im Komponenten-Inventar
  registrierten, eigenständigen Git-Repositories unter `repositories/`.
- Neue Plugins verwenden `flz_<name>`, neue Themes `flz-<name>` als Slug.
  Hooks, Optionen, Tabellen, REST-Routen, Nonce-Actions, Handles, PHP-Symbole
  und Textdomains erhalten einen daraus eindeutig abgeleiteten Präfix.
- Eigene Komponenten werden in `../tagore-local/public/wp-content/plugins`
  beziehungsweise `themes` ausschließlich per Symlink bereitgestellt. Im
  Laufzeitverzeichnis wird kein eigener Quellcode direkt bearbeitet.
- Nicht bearbeitet werden WordPress-Core, fremde Plugins oder Themes,
  Uploads, `wp-config.php`, produktive Konfigurationen, Secrets und
  Serverkonfiguration – außer ein konkreter Auftrag nennt exakt die
  erforderliche, zulässige Änderung.
- Das vorhandene Fremdtheme `deep-light` und Kopien in `../tagore` sind keine
  Quellen für eigene Theme-Entwicklung. Eine Übernahme oder Ableitung braucht
  vorab eine Lizenz-, Herkunfts- und Scope-Entscheidung.
- `config/workspace-components.tsv` ist die einzige manuell gepflegte Liste
  eigener Komponenten. Workspace, Skripte und Dokumentation dürfen keine
  konkurrierende Komponentenliste etablieren.

## Architektur- und Sicherheitsverträge

- Fachlogik, WordPress-Integration, Datenzugriff und Darstellung bleiben
  getrennt. Templates rendern; sie autorisieren nicht und führen keine
  komplexen Datenänderungen aus.
- Vor neuer Infrastruktur WordPress-Core-APIs verwenden: Settings, Options,
  Metadata, Roles/Capabilities, REST API, Cron, HTTP API, Filesystem API,
  Transients, i18n, Theme JSON und Block APIs. Eine parallele Eigenlösung
  braucht eine dokumentierte Begründung und Freigabe.
- Deny by default, Least privilege und server-side first gelten für Admin,
  REST, AJAX, Formularaktionen, Cron und WP-CLI. Sichtbarkeit in der UI erteilt
  keine Berechtigung.
- Eingaben möglichst am Rand validieren und mit kontextspezifischen
  WordPress-Funktionen sanitizen. Ausgaben spät und kontextbezogen escapen.
  Schreibende Browser-Requests brauchen Capability-Prüfung und Nonce; REST
  braucht einen echten `permission_callback`.
- SQL-Werte werden über `$wpdb->prepare()` oder kontrollierte Helper aus
  `flz_wpdb_objects` gebunden. Tabellen- und Spaltennamen stammen niemals aus
  ungeprüften Requestdaten.
- Externe Requests verwenden die WordPress HTTP API, definierte Timeouts und
  sichere Fehlerbehandlung. Neue externe Ziele, Telemetrie oder Übertragung
  personenbezogener Daten brauchen vorherige Freigabe.
- Fehler werden diagnostizierbar, datensparsam und ohne Secrets protokolliert.
  Fachplugins verwenden vorhandene zentrale Fehlermechanismen, derzeit
  `FlzWpdbObjectsException::log_error()`, soweit passend. Nutzermeldungen
  enthalten keine internen Details.
- Für jede relevante Information wird eine kanonische Quelle bestimmt.
  Konfiguration, Rollen, Status, Schema, Versionen, Slugs und Komponentenlisten
  werden nicht unabhängig doppelt gepflegt.
- Gemeinsamer Code wird nur extrahiert, wenn mindestens zwei Komponenten
  denselben semantischen Vertrag benötigen und dieser testbar ist.
  `flz_ui_components` und `flz_wpdb_objects` bleiben kleine, stabile Grenzen;
  Fachkomponenten greifen nicht direkt auf interne Dateien anderer
  Fachkomponenten zu.

## Plugin- und Theme-Verträge

- Plugins kapseln Verhalten, Datenmodelle, Integrationen und Inhalte, die bei
  einem Themewechsel erhalten bleiben müssen. Themes verantworten Darstellung,
  Templates, Styles, Patterns und bewusst themegebundene Präsentationslogik.
- Ein Theme darf keine geschäftskritischen Datenmodelle, Rollen oder
  Workflows besitzen. Plugin-Funktionalität darf nicht vom aktiven Theme
  abhängen; optionale Darstellungserweiterungen prüfen ihre Abhängigkeiten.
- Classic Themes nutzen WordPress Template Hierarchy und Hooks; Block Themes
  nutzen gültiges `theme.json`, Templates, Parts und Patterns. Die Wahl wird
  pro Theme in dessen Dokumentation festgehalten.
- Assets werden über `wp_enqueue_script()`, `wp_enqueue_style()` oder die
  vorgesehenen Block-APIs registriert. Versionen werden nachvollziehbar
  bestimmt; Inline-Code und globale CSS-Eingriffe bleiben begründet und klein.
- Frontend-Shortcodes eigener Plugins werden bei redaktioneller Nutzung nach
  Möglichkeit über `flz_ui_register_shortcode_block()` zusätzlich als Block
  angeboten. Der Shortcode kann stabile Rendering-Grenze bleiben.
- Übersetzbare UI-Texte verwenden die komponenteneigene Textdomain. Keine
  personenbezogenen, produktiven oder erfundenen fachlichen Scheindaten in
  Fixtures, Screenshots, Beispielen oder Dokumentation.
- Deaktivierung eines Plugins und Wechsel eines Themes dürfen die Site nicht
  durch ungeprüfte Funktionsaufrufe fatal beschädigen. Optionale Abhängigkeiten
  werden defensiv geprüft und im Admin verständlich gemeldet.

## Testgetriebene Änderungen und Qualität

- Neue Funktionen, Fehlerkorrekturen und sonstige Änderungen beobachtbaren
  Verhaltens folgen `test-driven-wordpress-change`: Invariante bestimmen,
  passenden zunächst roten Test nachweisen, minimal implementieren,
  Regressionen prüfen, dann refaktorieren.
- Reine Dokumentations-, Konfigurations- oder mechanische Änderungen erhalten
  eine passende Syntax-/Strukturprüfung statt künstlicher TDD-Tests.
- Sicherheitsgrenzen belegen mindestens einen erlaubten und einen sinnvollen
  verweigerten, ungültigen oder manipulierten Fall sowie das Ausbleiben
  verbotener Nebenwirkungen.
- PHP, JavaScript, CSS, HTML und JSON werden auf der kleinsten ehrlichen Ebene
  geprüft. WordPress-Integration, Hooks, REST, Datenbank und Rendering werden
  bei Bedarf in der lokalen DDEV-Instanz verifiziert.
- Oberflächen bleiben semantisch, responsiv, per Tastatur bedienbar, mit
  sichtbaren Fokuszuständen und verständlichen Labels/Fehlern. Bedeutung wird
  nicht nur über Farbe, Hover oder Zeigerinteraktion vermittelt.
- Für neuen oder wesentlich geänderten ausführbaren Code werden 85 Prozent
  Line-Coverage angestrebt. Sicherheitsinvarianten müssen unabhängig von der
  Quote vollständig abgedeckt sein.

## Persistenz, Updates und Datenschutz

- Vor persistenten Änderungen Zustände, Vorbedingungen, Zielzustand,
  Nebenwirkungen, Wiederholbarkeit, Fehlerfälle und Nebenläufigkeit bestimmen.
- Schemaänderungen verwenden versionierte, idempotente Upgradepfade. Bereits
  ausgelieferte Migrationen werden nicht rückwirkend geändert. Frische
  Installation und Upgrade mit synthetischen Bestandsdaten werden geprüft.
- Destruktive Deinstallation ist von Deaktivierung getrennt und löscht Daten
  nur nach dokumentierter Produktentscheidung und ausdrücklicher Zustimmung.
- Personenbezogene Daten werden minimiert, zweckgebunden und mit geklärten
  Aufbewahrungs-, Auskunfts- und Löschpfaden verarbeitet. Logs und Tests sind
  keine Ablage für Echtdaten.

## Lokale Laufzeit und Delivery

- DDEV wird ausschließlich aus `../tagore-local` gesteuert. Zustandsändernde
  DDEV-, WordPress-, WP-CLI-, Datenbank-, Aktivierungs-, Import- oder
  Bereinigungsbefehle brauchen einen konkreten Auftrag oder eine ausdrückliche
  Freigabe.
- Lokale Pfade, Benutzer, URLs und Zugangsdaten sind keine Produktionsannahmen.
  Jeder Wechsel zu Staging oder Produktion ist eine ausdrücklich benannte
  Umgebungsgrenze.
- Produktionssysteme werden nie direkt geändert. Staging-Deployments bleiben
  trocken, bis `--apply` ausdrücklich beauftragt ist. Theme-Delivery benötigt
  einen eigenen geprüften Pfad; das vorhandene Plugin-Deployskript ist dafür
  nicht zu verwenden.
- Eine lokale Lieferung ist erst verifiziert, wenn Symlink, WP-CLI-Status,
  relevante Tests, Assets und die sichtbare Oberfläche geprüft wurden.

## Stop-Regeln

Wenn der Auftrag den Risikobereich nicht bereits ausdrücklich umfasst, vor
der Umsetzung Risiko, Dateien, Tests und Rückbau nennen und Freigabe einholen
bei:

- Datenbankschema, Migrationen oder bestehenden Daten;
- Rollen, Capabilities, Nonces, Authentifizierung oder Zugriffsschutz;
- öffentlichen Verträgen gemeinsamer Plugins oder mehreren Komponenten;
- Uploads, Dateipfaden, Downloads oder Dokumenterzeugung;
- Aktivierung, Themewechsel, Datenimport, DDEV-/WordPress-Konfiguration;
- neuen Produktionsabhängigkeiten oder externen Diensten;
- Löschung, Umbenennung, größerer Verschiebung oder breitem Refactoring;
- unklarem Rollback oder konkurrierenden Quellen ohne geklärte Autorität.

Sofort stoppen, wenn Produktionszugriff, Git-Historienumschreibung, Verlust
fachlicher Regeln oder Änderungen außerhalb des Auftrags nötig würden.

## Learning Candidates

Beobachtungen werden nicht automatisch zu Regeln. Wiederverwendbare,
belegbare Kandidaten werden mit `evaluate-learning-candidate` klassifiziert
und bis zur ausdrücklichen Entscheidung nur in
`docs/learning-candidates.md` geführt.

## Git und Definition of Done

- Vor größeren Änderungen einen fachlich benannten Branch verwenden.
- Keine Commits, Pushes oder Deployments ohne ausdrückliche Freigabe; niemals
  `git add .` verwenden. Bestehende fremde Änderungen bleiben unangetastet.
- Vor einem Commit `git status --short`, `git diff --stat` und
  `git diff --name-only` zeigen und nur benannte Dateien stagen.
- Mindestens `scripts/check-fast` und die relevanten Komponenten-/DDEV-Checks
  ausführen. Ein nicht ausgeführter Laufzeitcheck wird als Nachweislücke
  benannt, nicht als bestanden dargestellt.
- Der Abschlussbericht nennt Scope, geänderte Dateien, Prüfungen und
  Ergebnisse, ausgelassene Prüfungen mit Grund, Risiken, Learning Candidates
  und finalen Git-Status. Kein Commit ist Teil der Definition of Done.
