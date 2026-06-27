# Arbeitsregeln für KI-gestützte Entwicklung der Tagore-Plugins

Dieses Repository enthält ausschließlich eigene WordPress-Plugins für das Tagore-Gymnasium.

## Geltungsbereich

Dieses Repository `~/projects/tagore-plugins` enthält ausschließlich eigene WordPress-Plugins für das Tagore-Gymnasium. Grundsätzlich dürfen deshalb alle Plugin-Verzeichnisse in diesem Repository bearbeitet werden, solange sie eigene Tagore-Plugins sind und die übrigen Regeln eingehalten werden.

Aktuell gehören dazu insbesondere:

- flz_elternsprechtag
- flz_probeunterricht
- flz_wpdb_objects
- flz_ags
- flz_shortcode_redirect
- flz_ui_components

Neue eigene Plugins dürfen in diesem Repository ergänzt werden, wenn sie der `flz_`-Namenskonvention folgen.

Die Plugins aus `~/projects/tagore-plugins` müssen im eigentlichen WordPress-Plugin-Verzeichnis immer per Symlink verfügbar gemacht werden. Im WordPress-Plugin-Verzeichnis selbst werden außer dem Anlegen, Prüfen oder Entfernen dieser Symlinks keine Plugin-Dateien verändert.

Nicht bearbeitet werden dürfen:

- WordPress-Core
- Themes
- Fremdplugins
- Uploads
- produktive Konfigurationsdateien
- wp-config.php
- Zugangsdaten, Tokens, Secrets oder Serverkonfiguration

## Namenskonvention

Die bestehende Namenskonvention `flz_` ist für eigene Entwicklungen beizubehalten.

Für neue eigene Plugins, PHP-Funktionen, PHP-Klassenpräfixe, Shortcodes, Optionsnamen, Capabilities, AJAX-/REST-Actions, Cron-Hooks, CSS-/JS-Handles und Datenbanktabellen ist grundsätzlich ein `flz_`-Präfix bzw. ein eindeutig davon abgeleiteter Präfix zu verwenden.

Das AG-Plugin heißt `flz_ags`. Für AG-bezogene neue Namen gelten `flz_ags`, `flz_ag_*` und `flz-ags` als verbindliche Konvention. Alte Namen wie `tagore-ags`, `tagore_ags`, `tg_ag_*` oder `tg-ag` sind nicht weiterzuverwenden. Falls solche Namen lokal oder auf Staging noch vorkommen, gelten sie als Altbestand der Umbenennung und sollen gezielt entfernt oder migriert werden.

## Entwicklungsprinzip

Änderungen erfolgen lokal in DDEV. Danach wird getestet. Erst danach darf ein Transfer nach Staging vorbereitet werden. Production wird nie direkt geändert.

## Demo- und Seed-Daten

Demo-, Test- und Seed-Daten sollen bevorzugt aus lokal vorhandenen, verwalteten WordPress-Strukturen oder Plugin-Daten abgeleitet werden, z. B. aus bestehenden Seiten, Beiträgen, Optionen oder Modelltabellen.

Keine harten Demo-Listen aus externen Produktions-URLs, kopierten Live-Daten oder frei erfundenen fachlichen Platzhaltern verwenden, wenn die Daten lokal aus der WordPress-Struktur gewonnen werden können.

Falls Demo-Daten nicht zuverlässig ableitbar sind, müssen fehlende Felder klar leer bleiben oder als nicht verfügbar behandelt werden, statt fachliche Scheindaten zu erzeugen.

## Sicherheitsregeln für WordPress-Code

Bei jeder Änderung sind zu prüfen:

- Eingaben mit sanitize_text_field(), sanitize_email(), absint(), wp_kses_post() oder passenden Alternativen bereinigen.
- Ausgaben mit esc_html(), esc_attr(), esc_url(), wp_kses_post() oder passenden Alternativen escapen.
- Admin-Aktionen mit current_user_can() absichern.
- Formularaktionen und AJAX/REST-Endpunkte mit Nonces absichern.
- Datenbankzugriffe über $wpdb->prepare() oder kontrollierte interne Helper aus flz_wpdb_objects.
- Keine SQL-Strings aus ungeprüften Request-Daten bauen.
- Keine Secrets in Dateien schreiben.
- Keine externen Requests einbauen, ohne sie explizit zu dokumentieren.

## Besondere Regel für flz_wpdb_objects

flz_wpdb_objects ist ein gemeinsames Hilfsplugin. Änderungen daran können flz_elternsprechtag, flz_probeunterricht und weitere eigene Plugins betreffen.

Jede Änderung an flz_wpdb_objects braucht deshalb:

- kurze Begründung
- Liste der betroffenen abhängigen Plugins
- Rückwärtskompatibilitätsprüfung
- Migrationshinweis, falls Datenbankstruktur oder API geändert wird

## Arbeitsweise

Vor jeder größeren Änderung:

1. Neuen Git-Branch anlegen.
2. Änderung klein halten.
3. Nach der Änderung Tests/Checks ausführen.
4. Geänderte Dateien nennen.
5. Zweck, Risiko und Teststand zusammenfassen.

## Lokale Test- und DDEV-Regeln

Das Plugin-Repository `~/projects/tagore-plugins` ist kein DDEV-Projekt. Dort liegt keine `.ddev/config.yaml`.

Die lokale WordPress-/DDEV-Instanz liegt unter:

`~/projects/tagore-local`

DDEV-Befehle wie `ddev start`, `ddev wp`, `ddev exec` oder `ddev composer` müssen deshalb immer aus `~/projects/tagore-local` heraus ausgeführt werden, sofern nicht ausdrücklich ein anderer DDEV-Projektpfad genannt wird.

Codeänderungen erfolgen im Plugin-Repository:

`~/projects/tagore-plugins`

Die Standardprüfungen für dieses Repo werden aus dem Plugin-Repository gestartet:

```bash
cd ~/projects/tagore-plugins
./scripts/phpcs-flz-ags.sh
./scripts/check-local.sh
```

Wenn DDEV nicht läuft, zuerst starten mit:

```bash
cd ~/projects/tagore-local
ddev start
```

Danach zurück ins Plugin-Repository und die Checks erneut ausführen:

```bash
cd ~/projects/tagore-plugins
./scripts/phpcs-flz-ags.sh
./scripts/check-local.sh
```

Für neue Tests gilt: Testdateien und Testskripte im Plugin-Repository anlegen, aber WordPress-, WP-CLI-, Composer- oder PHPUnit-Befehle, die eine WordPress-/DDEV-Umgebung brauchen, über die DDEV-Instanz `~/projects/tagore-local` ausführen.

## Codex-Commit-Regel

Codex darf Git-Commits nur nach ausdrücklicher Freigabe durch den Nutzer erstellen.

Vor einem Commit muss Codex anzeigen:

- `git status --short`
- `git diff --stat`
- die konkret zu committenden Dateien
- die vorgeschlagene Commit-Message

Codex darf nur ausdrücklich benannte Dateien stagen. `git add .` ist nicht erlaubt, außer der Nutzer verlangt es ausdrücklich.

Wenn weitere uncommitted Changes existieren, müssen Commits fachlich getrennt bleiben.

Codex darf nicht pushen und nicht auf Staging oder Produktion deployen, außer der Nutzer verlangt dies ausdrücklich.
