# Schrittweise Übernahme der BR-starken Quality-Gates

Stand: 22. August 2026. Der maschinenlesbare Ist-Stand steht ausschließlich in
`config/quality-gates.tsv`. Eine Phase wird pro Komponente erst hochgesetzt,
wenn ihre Nachweise im eigenen Repository grün sind.

## Phase 0 – Vertrag und Blockade

- verbindlichen Commit-, Coverage- und Releasevertrag festlegen;
- jede registrierte Komponente in der Gate-Konfiguration führen;
- komponentenlokale, ausfüllbare Abnahmeprotokolle vorbereiten;
- Release-Gate bis zur vollständigen Übernahme technisch blockieren.

Status: umgesetzt.

## Phase 1 – Reproduzierbare Komponenten-CI

Reihenfolge: `flz_wpdb_objects`, `flz_ui_components`,
`flz_shortcode_redirect`, `flz_ags`, `flz_elternsprechtag`,
`flz_probeunterricht`.

Je Komponente:

- Workflow für Pull Requests und Pushes auf `main`, `contents: read`,
  Concurrency und Timeouts;
- PHP-Matrix aus 8.1 und dem aktuellen Projektmaximum;
- Composer-Installation aus Lockdatei, PHP-Syntax, PHPCS und vollständige
  Komponenten-Smokes;
- bei Shared-Änderungen Provider-/Consumer-Checkout mit gleichnamigem Branch
  und sicherem `main`-Fallback;
- verpflichtende Lizenz und Changelog.

Status: umgesetzt und remote belegt. Die Draft-PRs aller sechs Komponenten
sowie des Workspaces sind am 22. August 2026 einschließlich PHP 8.1/8.5,
Komponenten-Smokes, JavaScript, Provider-/Consumer-Verträgen und Workspace-
Vertrag grün gelaufen. `ci_gate` steht deshalb für alle Komponenten auf
`enforced`. `commit_gate` bleibt bis zum grünen Wiederholungslauf gegen die
neu konfigurierten PHP-Baselines auf `transition`.

## Phase 2 – Coverage-Baseline und No-Regression

- gepinnte, lockfile-basierte PHP-Coverage-Werkzeuge bereitstellen;
- für `flz_ui_components` und `flz_ags` zusätzlich JavaScript-Coverage mit
  gepinntem `c8` erfassen;
- erste ehrliche Line-Coverage pro Komponente messen und als Baseline eintragen;
- CI gegen Baseline-Rückgang sperren; Zielwert 85 Prozent separat beibehalten;
- Coverage-Berichte nur unter ignorierten Buildpfaden erzeugen.

Erst nach reproduzierbarer Messung darf `php_gate` beziehungsweise `js_gate`
auf `enforced` wechseln.

Zwischenstand: Das zentral gepinnte PHPCOV-/c8-Tooling und die Xdebug-Jobs sind
remote reproduzierbar grün. Die gemessenen PHP-Baselines sind 54,99 Prozent
für `flz_wpdb_objects`, 54,34 Prozent für `flz_ui_components`, 65,09 Prozent
für `flz_shortcode_redirect`, 10,69 Prozent für `flz_elternsprechtag`,
8,16 Prozent für `flz_probeunterricht` und 16,34 Prozent für `flz_ags`.
JavaScript ist mit 54,05 Prozent für `flz_ui_components` und 41,46 Prozent für
`flz_ags` remote belegt und deshalb `enforced`.

Die PHP-Ratschen stehen zunächst auf `configured`: Der erste Lauf hat die
ehrlichen Werte bestimmt, aber noch nicht gegen diese Werte blockiert. Erst
ein grüner Wiederholungslauf mit der aktualisierten Gate-Konfiguration setzt
`php_gate`, `commit_gate` und `adoption_phase` auf `enforced`, `enforced` und
`2`. Lokales Xdebug fehlt weiterhin; dies ist nach dem Remote-Nachweis keine
Baseline-Lücke mehr, bleibt aber als lokale Ausführungslücke transparent.

## Phase 3 – Testlücken und manuelle Abnahme

- Altcode schrittweise auf mindestens 85 Prozent Line-Coverage anheben;
- Sicherheits-, Datenschutz-, Migrations-, Parallelitäts- und
  Provider-/Consumer-Invarianten vollständig abdecken;
- Browser-, Tastatur-, Screenreader-, Responsive-, Kontrast-, Mail- und
  Zwei-Prozess-Fälle aus den lokalen Protokollen durchführen;
- Ergebnisse mit neutralen Testdaten dokumentieren; keine Secrets oder
  personenbezogenen Echtdaten aufnehmen.

`acceptance` wechselt erst nach unterschriebener Gesamtentscheidung von
`prepared` auf `accepted`.

## Phase 4 – Reproduzierbare Release-Artefakte

- deterministischen Ein-Wurzel-Paketbau mit normalisierten Zeitstempeln,
  Besitzern und Dateireihenfolgen implementieren;
- Manifest, `SHA256SUMS` und äußere Prüfsumme erzeugen und testen;
- Entwicklungsdateien, Tests, Abhängigkeits-Caches, Symlinks, lokale Pfade und
  Secrets ausschließen;
- Archiv zweimal bauen und Bytegleichheit prüfen;
- Installation, Upgrade, Deaktivierung und Rückbau aus dem exakten Artefakt in
  einer sauberen DDEV-Instanz prüfen.

Danach darf `release_archive` auf `enforced` wechseln.

## Phase 5 – Releasefreigabe

Nur wenn alle vorherigen Phasen für die Komponente abgeschlossen sind, darf
`release_gate` auf `ready` wechseln. Tag, Push, Veröffentlichung, Signatur,
Staging oder Produktion benötigen weiterhin jeweils eine ausdrückliche
Freigabe. Ein fehlgeschlagenes oder nicht ausgeführtes Gate setzt den Status
wieder auf `blocked`.
