# Verbindlicher Commit-, Coverage- und Release-Vertrag

Dieser Vertrag übernimmt die Stärke der Quality- und Delivery-Gates aus
[`Filzmann/br-nextcloud-apps`](https://github.com/Filzmann/br-nextcloud-apps)
für den Tagore-WordPress-Workspace. Die Systeme unterscheiden sich technisch;
die Schutzwirkung ist gleich: Ein Diagnose- oder Fast-Check ist niemals ein
Releaseurteil, Coverage darf nicht unbemerkt sinken und kein rotes oder
unverifiziertes Pflichtgate wird übersprungen.

`config/quality-gates.tsv` ist die kanonische Zustandskonfiguration der
schrittweisen Übernahme. Die Komponentenmenge wird weiterhin ausschließlich
aus `config/workspace-components.tsv` abgeleitet und dagegen geprüft.

Beim CI-Gate bedeutet `pending`, dass der Vertrag noch fehlt. `configured`
bedeutet, dass Workflow und lokaler Strukturtest vollständig vorliegen, aber
noch kein grüner Remote-Lauf für den betreffenden Stand belegt ist. Erst ein
erfolgreicher Pull-Request- oder `main`-Lauf darf den Status auf `enforced`
setzen. Release- und normales Commit-Gate akzeptieren nur `enforced`.

## Commit-Gate

Vor jedem normalen Komponenten-Commit sind erforderlich:

1. `git status --short`, `git diff --stat` und `git diff --name-only` aus dem
   tatsächlich betroffenen Repository;
2. fokussierte Tests, die Komponenten-Suite, relevante Provider-/Consumer-
   Verträge, PHPCS und `git diff --check` ohne Fehler;
3. ein grüner CI-Vertrag auf Pull Requests und `main`, mit minimalen
   `contents: read`-Berechtigungen und einer PHP-Matrix aus deklariertem
   Minimum und aktuellem Projektmaximum;
4. getrennte PHP-/JavaScript-Line-Coverage ohne Rückgang gegen die festgelegte
   Baseline; neuer oder wesentlich geänderter ausführbarer Code erreicht
   mindestens 85 Prozent, Sicherheitsinvarianten unabhängig davon vollständig;
5. ausschließlich einzeln benannte Staging-Dateien, niemals `git add .`, keine
   getrackten Build-, Coverage-, Cache-, Dump-, Secret- oder Schlüsseldateien.

Solange `commit_gate` einer Komponente `transition` ist, sind normale Feature-
und Releasecommits blockiert. Ausdrücklich beauftragte, eng begrenzte
Quality-Rollout-Commits dürfen die fehlenden Gates schrittweise herstellen und
werden als Übergangsausnahme ausgewiesen.

## Coverage-Gate

- PHP und JavaScript werden getrennt gemessen. Komponenten ohne ausführbares
  JavaScript verwenden ausschließlich `n/a`/`not-applicable`.
- Die erste reproduzierbare Messung wird als Baseline eingetragen. Ein späterer
  Rückgang blockiert CI und Commit-Gate.
- Baselines werden nur angehoben. Eine Absenkung braucht eine ausdrückliche,
  dokumentierte Entscheidung mit Ursache und Rückgewinnungsplan.
- Ziel sind mindestens 85 Prozent Line-Coverage je Sprache und Komponente.
  Die Baseline-Ratsche erlaubt die schrittweise Sanierung von Altcode, senkt
  aber nicht die 85-Prozent-Anforderung für neuen oder wesentlich geänderten
  Code.
- Coverage ist ein Delivery-Indikator, kein Ersatz für fachlich aussagekräftige
  Allow-/Deny-, Fehler-, Migrations-, Nebenwirkungs- und Integrationstests.

## Release-Gate

Ein Release ist nur freigabefähig, wenn für die exakten enthaltenen Commits:

1. Workspace und alle einbezogenen Komponenten-Repositorys sauber sind;
2. Commit-, CI-, Coverage-, Provider-/Consumer- und Security-Gates grün sind;
3. Version, Changelog, Abhängigkeiten und unterstützte WordPress-/PHP-Versionen
   konsistent sind;
4. das komponentenlokale `docs/manual-acceptance.md` vollständig ausgefüllt
   und mit Gesamtentscheidung `abgenommen` oder ausdrücklich `mit Auflagen
   abgenommen` abgeschlossen ist;
5. Installation, Upgrade aus der relevanten Vorversion, Deaktivierung,
   Datenschutz, sichtbare Oberfläche und dokumentierter Rückbau geprüft sind;
6. ein bytegleich reproduzierbares Archiv mit genau einer Plugin-/Theme-Wurzel
   entsteht und keine `.git`, `.github`, `.agents`, `.codex`, Tests,
   Dependency-Caches, lokalen Pfade, Secrets oder Symlinks enthält;
7. Manifest und Prüfsummen mindestens Slug, Version, vollständigen Git-Commit
   und SHA-256 des Artefakts festhalten;
8. README, Lizenz, Changelog, Installations-/Rückbauhinweise und das leere oder
   ausgefüllte Abnahmeprotokoll zum definierten Delivery-Vertrag gehören;
9. das erzeugte Artefakt in einer sauberen lokalen WordPress-Umgebung installiert
   beziehungsweise aktualisiert und über WP-CLI, HTTPS, Assets und sichtbare UI
   geprüft wurde.

`scripts/check-quality-gates --release <slug>` ist das strikte maschinelle
Vorgate. Ein Diagnose-, Fast- oder lokaler Quellcheck darf niemals als Ersatz
oder Releasefreigabe bezeichnet werden. Bauen, Signieren, Taggen, Pushen,
Publizieren und Deployen bleiben getrennte, ausdrücklich zu autorisierende
Aktionen.
