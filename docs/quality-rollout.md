# Schrittweise Übernahme der BR-starken Quality-Gates

Stand: 22. August 2026. Der maschinenlesbare Ist-Stand steht ausschließlich in
`config/quality-gates.tsv`. Eine Phase wird pro Komponente erst hochgesetzt,
wenn ihre Nachweise im eigenen Repository grün sind.

## Phase 0 – Vertrag und Blockade

- verbindlichen Commit-, Coverage- und Releasevertrag festlegen;
- jede registrierte Komponente in der Gate-Konfiguration führen;
- komponentenlokale, ausfüllbare Abnahmeprotokolle vorbereiten;
- Release-Gate bis zur vollständigen Übernahme technisch blockieren.

Status: mit diesem Rollout-Schritt umgesetzt.

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

Danach wechseln `ci_gate` und – soweit alle übrigen Commitbedingungen erfüllt
sind – `commit_gate` auf `enforced`.

## Phase 2 – Coverage-Baseline und No-Regression

- gepinnte, lockfile-basierte PHP-Coverage-Werkzeuge bereitstellen;
- für `flz_ui_components` und `flz_ags` zusätzlich JavaScript-Coverage mit
  gepinntem `c8` erfassen;
- erste ehrliche Line-Coverage pro Komponente messen und als Baseline eintragen;
- CI gegen Baseline-Rückgang sperren; Zielwert 85 Prozent separat beibehalten;
- Coverage-Berichte nur unter ignorierten Buildpfaden erzeugen.

Erst nach reproduzierbarer Messung darf `php_gate` beziehungsweise `js_gate`
auf `enforced` wechseln.

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
