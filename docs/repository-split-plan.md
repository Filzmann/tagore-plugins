# Plan zur Trennung der WordPress-Komponenten

## Zielbild

`tagore-plugins` ist der schlanke Koordinations-Workspace. Der Plugin-Quellcode
liegt in sechs unabhängigen Git-Repositories im ignorierten Container
`repositories/`; das siebte Repository entsteht später für das Theme:

| Repository | Typ | Harte Laufzeitabhängigkeiten |
| --- | --- | --- |
| `flz_wpdb_objects` | Shared-Plugin | keine |
| `flz_ui_components` | Shared-Plugin | keine |
| `flz_shortcode_redirect` | Fachplugin | keine; Logging über `flz_wpdb_objects` optional |
| `flz_elternsprechtag` | Fachplugin | `flz_wpdb_objects`, `flz_ui_components` |
| `flz_probeunterricht` | Fachplugin | `flz_wpdb_objects`, `flz_ui_components` |
| `flz_ags` | Fachplugin | `flz_wpdb_objects`, `flz_ui_components` |
| `flz-tagore` | Theme | keine harte Plugin-Abhängigkeit |

Das Theme-Repository wird erst nach Abschluss der Plugin-Trennung erzeugt. Die
inhaltliche Theme-Roadmap steht in `docs/theme-rebuild-roadmap.md`.

## Umsetzungsstand der Git-Strategie

1. **Erledigt:** Workspace-Baseline als benannter Commit gesichert.
2. **Erledigt:** Pro Plugin mit `git subtree split --prefix=<slug>` eine
   komponentenspezifische Historie ohne Umschreiben der Workspace-Historie
   erzeugt.
3. **Erledigt:** Sechs unabhängige Repositories unter `repositories/<slug>`
   erzeugt, auf `main` gesetzt und von einem irreführenden lokalen `origin`
   getrennt.
4. **Erledigt:** Jedes Plugin besitzt eigenständige Regeln, Roadmap, README,
   Composer-/PHPCS-Metadaten und `scripts/check-fast`.
5. **Erledigt:** Der Koordinator behält nur Inventar, Architekturverträge,
   Skills, Checks und lokale Orchestrierung; es gibt keine Submodule.
6. **Erledigt:** alte, nun redundante Plugin-Unterverzeichnisse entfernt und
   Laufzeit-Symlinks auf die eigenständigen Repositories umgestellt.

Remote-Repositories, Pushes und GitHub-Projekte sind ein getrenntes Gate. Ohne
angegebene Zielorganisation werden nur lokale Git-Repositories erstellt.

## Abhängigkeitsvertrag

Die drei Fachplugins deklarieren ihre harten Abhängigkeiten weiterhin über den
WordPress-Header `Requires Plugins`. Dieser Core-Vertrag verhindert
Aktivierung/Deaktivierung bei unerfüllten Abhängigkeiten, garantiert aber weder
Versionen noch Lade-Reihenfolge.

Zusätzlich gilt daher:

- Shared-Plugins veröffentlichen genau eine Bootstrap-Datei, eine
  Versionskonstante und dokumentierte öffentliche Klassen/Funktionen.
- Fachplugins laden niemals Dateien aus einem Geschwister-Repository über
  `WP_PLUGIN_DIR` oder relative Pfade.
- Fachplugins initialisieren ihre eigentliche Integration erst nach dem
  normalen Plugin-Ladevorgang und prüfen Klasse, Funktion und Mindestversion.
- Aktivierung prüft dieselben Verträge und bricht mit `WP_Error`/verständlicher
  Adminmeldung ab, statt teilweise Tabellen oder Rollen anzulegen.
- `flz_shortcode_redirect` behält sein Shared-Logging optional und seinen
  lokalen, datensparsamen Fallback.
- Provider-Tests und Verbraucher-Smokes bilden gemeinsam das Release-Gate.

## CSV-Vertrag für datenführende Plugins

CSV ist ein fachlicher Portabilitätsvertrag und kein generischer Dump. Jeder
Import prüft Capability, Nonce, Dateifehler, Größe, Kopfzeile, Typen,
Referenzen, Duplikate und fachliche Invarianten. Vor dem Schreiben erfolgt ein
Dry-Run mit Fehlerbericht; die eigentliche Änderung ist atomar und
wiederholbar. Exporte werden direkt gestreamt und hinterlassen keine
personenbezogene Datei im öffentlichen Upload-Verzeichnis.

| Plugin | Datensatz | Import | Export | Handlungsbedarf |
| --- | --- | --- | --- | --- |
| Elternsprechtag | Lehrkräfte | vorhanden | vorhanden | sicherer Direktdownload, Roundtrip-Test |
| Elternsprechtag | Termine/Buchungen | Teilimport | vorhanden | vollständiges, versioniertes Roundtrip-Schema |
| Elternsprechtag | Einstellungen | fehlt | fehlt | Portabilitätsentscheidung und Umsetzung |
| Probeunterricht | Schulen | vorhanden | vorhanden | sicherer Direktdownload, Roundtrip-Test |
| Probeunterricht | Teilnehmende | fehlt | vorhanden | Import mit Schulreferenz und Kapazitätsprüfung |
| Probeunterricht | Einstellungen | fehlt | fehlt | Portabilitätsentscheidung und Umsetzung |
| AGs | AGs und Slots | fehlt | fehlt | gemeinsames relationales CSV-Schema ergänzen |
| AGs | Anmeldungen | fehlt | vorhanden | Import mit Duplikat-, Slot- und Kapazitätsprüfung |
| AGs | Einstellungen | fehlt | fehlt | Portabilitätsentscheidung und Umsetzung |

`flz_wpdb_objects`, `flz_ui_components` und `flz_shortcode_redirect` erzeugen
keine eigenen fachlichen Datensätze. Ihre CSV-Helfer beziehungsweise
Darstellungskomponenten sind Infrastruktur, kein eigener Import-/Exportbedarf.

## Bereinigung alter KI-/Agentenreste

### Entfernen

- `00_ki_projektkonfiguration_tagore_wordpress.md`: reiner Legacy-Weiterleiter
  ohne eigenen Vertrag.
- `docs/local-development.md`: veraltete, hart codierte Pfade und eine
  konkurrierende, unvollständige Komponentenliste. Gültige Inhalte werden vor
  dem Entfernen in `docs/workspace.md`, Inventar und Scripts übernommen.
- komponentenfremde Kopien von Workspace-Skills oder `.codex`-Agenten in den
  neuen Plugin-Repositories.

### Behalten und neu zuordnen

- Root-`AGENTS.md`: nur Regeln des Koordinations-Workspace.
- `.agents/skills/`: nur wiederholbare, komponentenübergreifende Abläufe im
  Koordinations-Workspace.
- `.codex/agents/explorer.toml` und `reviewer.toml`: sinnvolle read-only Rollen
  für Workspace-Prüfung; keine Legacy-Reste.
- Plugin-`AGENTS.md`: selbstständige, kurze Regeln des jeweiligen Repositories.
- AG-Namenskonvention, DDEV-Grenze, Shared-API-Verbraucherprüfung und
  Commit-/Deployment-Gates: bereits sinnvoll im aktuellen Regelsystem
  verankert und nach der Trennung in den passenden Scope zu übertragen.

Die Suche in aktuellem Dateibaum und Git-Historie ergab keine zusätzlichen
`CLAUDE.md`, `GEMINI.md`, `.cursorrules` oder Copilot-Regeldateien.

## Verifikation der Trennung

- alter und neuer Dateibaum pro Plugin sind inhaltlich identisch;
- neuer Git-Log enthält alle komponentenrelevanten Commits;
- jeder Repo-Schnellcheck und vorhandene Smoke-Test besteht isoliert;
- statische Abhängigkeits-Smokes belegen Mindestversionen, verzögerten Bootstrap
  und das Fehlen direkter Nachbar-Includes; die Laufzeitfälle fehlender, zu alter
  und verspätet geladener Provider bleiben bis zum DDEV-Test offen;
- Inventar und Workspace-Scripts finden alle sechs aktuellen Repositories relativ und
  führen keine konkurrierende Komponentenliste;
- Laufzeit-Symlinks zeigen exakt auf die neuen Repositories;
- **Offen:** DDEV/WP-CLI erkennt Version und Status jeder Komponente; Docker
  steht in der aktuellen Umgebung nicht zur Verfügung.
- kein Commit, Push, Themewechsel oder Deployment erfolgt implizit.

## Rückbau

Die alten Dateien bleiben über den Workspace-Commit `78a4af6` und die
`split/<slug>`-Zweige wiederherstellbar. Nach der Umschaltung können die
Laufzeitlinks auf einen ausgecheckten historischen Pfad zurückgesetzt werden.
Die bestehende Git-Historie wurde nicht umgeschrieben.
