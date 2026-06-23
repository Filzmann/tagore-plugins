# Lokale Entwicklungsumgebung Tagore-Plugins

## Grundstruktur

Lokaler WordPress-/DDEV-Klon:
/home/filzmann/projects/tagore-local

Eigenes Plugin-Repository:
/home/filzmann/projects/tagore-plugins

Lokale WordPress-URL:
https://tagore-local.ddev.site

## Eigene Plugins

Dieses Repository enthält die eigenen Tagore-Plugins:

- flz_elternsprechtag
- flz_probeunterricht
- flz_wpdb_objects
- flz_shortcode_redirect
- flz_ags

## Namenskonvention

Für neue eigene Entwicklungen gilt die flz_-Konvention.

Für das AG-Plugin gelten insbesondere:

- Pluginordner: flz_ags
- Plugin-Datei: flz_ags.php
- PHP-Präfix: flz_ags / Flz_Ags / FLZ_AGS
- DB-Präfix: flz_ag_*
- Asset-Handle: flz-ags
- Shortcodes: flz_ag_liste, flz_ag_anmeldung

Alte Namen wie tagore-ags, tagore_ag, tg_ag_* oder tg-ag sind nicht weiterzuverwenden.

## Symlink-Einbindung in WordPress

Die eigenen Plugins liegen nicht direkt im WordPress-Klon, sondern im Plugin-Repository.

Beispiel:

/home/filzmann/projects/tagore-local/public/wp-content/plugins/flz_ags
-> /home/filzmann/projects/tagore-plugins/flz_ags

Damit diese Symlinks im DDEV-Webcontainer funktionieren, enthält der DDEV-Klon diese Datei:

/home/filzmann/projects/tagore-local/.ddev/docker-compose.tagore-plugins.yaml

Sie bindet das Plugin-Repository in den Container ein.

## Standardablauf

Vor Änderungen:

cd /home/filzmann/projects/tagore-plugins
git status
git switch main
git switch -c feature/kurzer-name

Nach Änderungen lokal prüfen:

cd /home/filzmann/projects/tagore-plugins
./scripts/check-local.sh

Vor einem Commit:

git diff
git status

Commit:

git add <dateien>
git commit -m "Kurze präzise Beschreibung"

## Lokaler Check

Der Standardcheck liegt hier:

scripts/check-local.sh

Er prüft:

- Git-Status
- PHP-Syntax aller eigenen Plugin-Dateien
- DDEV-Status
- Status der eigenen WordPress-Plugins
- HTTP-Status der lokalen Website

## Bekannte lokale Besonderheiten

Der lokale WordPress-Klon enthält einen lokalen Deepcore-Kompatibilitätsfix, damit die importierte Staging-Seite unter der lokalen PHP-Version ohne Fatal Error läuft. Dieser Fix gehört nicht zum Plugin-Repository.

Die wiederkehrenden Notices/Deprecated-Meldungen aus Deep, Photo Gallery, Easy Fancybox, Tangible Loops and Logic und Deepcore sind Fremdplugin-/Theme-Kompatibilitätsmeldungen. Sie sind nicht Teil der eigenen Pluginentwicklung, solange sie keinen Fatal Error erzeugen.
