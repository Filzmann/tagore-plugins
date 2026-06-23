# Arbeitsregeln für KI-gestützte Entwicklung der Tagore-Plugins

Dieses Repository enthält ausschließlich eigene WordPress-Plugins für das Tagore-Gymnasium.

## Geltungsbereich

Bearbeitet werden dürfen nur diese Plugin-Verzeichnisse:

- flz_elternsprechtag
- flz_probeunterricht
- flz_wpdb_objects
- tagore-ags

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

Bestehende abweichende Namen wie `tagore-ags` oder bereits angelegte Tabellenpräfixe wie `tg_ag_*` gelten als Bestand. Sie dürfen nicht beiläufig umbenannt werden. Eine Umbenennung ist nur mit ausdrücklicher Entscheidung, Migrationsplan, Datenbankmigration, Rückwärtskompatibilitätsprüfung und Staging-Test zulässig.

## Entwicklungsprinzip

Änderungen erfolgen lokal in DDEV. Danach wird getestet. Erst danach darf ein Transfer nach Staging vorbereitet werden. Production wird nie direkt geändert.

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
