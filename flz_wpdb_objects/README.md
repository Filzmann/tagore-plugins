# flz_wpdb_objects

## Modelltabellen und Custom-Queries

Tabellennamen werden zentral aus dem konkreten Modellnamen abgeleitet und in
Snake-Case pluralisiert. Beispiele:

- `FlzEstTeacher` → `{prefix}flz_est_teachers`
- `FlzPuParticipant` → `{prefix}flz_pu_participants`
- `FLZ_AGS_Course` → `{prefix}flz_ags_courses`

Fachplugins sollen keine eigenen Tabellennamen ableiten und keine
SQL-Strings aus Request-Daten bauen. Einfache Abfragen laufen über
`get_by_id()`, `get_by_fields()`, `get_all_by()` und `count_by()`. Wenn ein
Modell kontrollierte JOINs oder fachliche Sortierungen braucht, stehen
`query_rows()` und `query_models()` als geschützte Helper bereit. SQL-Fragmente
müssen dabei weiterhin aus dem Modell selbst stammen; Nutzwerte werden über
Platzhalter übergeben.

Für historische statische `afterInsert()`-Hooks gibt es
`last_insert_id()`. Neue Logik sollte nach `save()` die Objekt-ID verwenden;
falls ein alter Hook die Insert-ID braucht, bleibt der `$wpdb`-Zugriff damit
zentral geprüft und im Fehlerfall als Modellfehler erklärbar.

## CSV

CSV-Ausgabe arbeitet zentral mit Arrays statt mit modellindividuellen
`getCsvLine()`-Methoden:

- `flz_wpdb_objects_create_csv_file($header, $rows, $filename)` schreibt eine
  Datei in das Upload-Verzeichnis und liefert die URL.
- `flz_wpdb_objects_send_csv_download($header, $rows, $filename)` sendet einen
  Download direkt an den Browser.

Die alte Funktion `flz_wpdb_objects_create_csv()` und modellbasierte
`getCsvLine()`-Relikte wurden entfernt. CSV-Zellen werden weiterhin gegen
Tabellenkalkulations-Formeln neutralisiert.

## Fehlervertrag

Datenbank-, Modell-, Hook- und Dateisystemfehler werden als
`flz_wpdb_objects\FlzWpdbObjectsException` weitergegeben. Beim Ergänzen von
Kontext bleibt die ursprüngliche Exception über `getPrevious()` erhalten.
Aufrufer sollen die vollständige Kette protokollieren und an der UI-Grenze eine
separate, escapte Meldung ohne interne Details ausgeben.

Für App-Logging steht zusätzlich
`FlzWpdbObjectsException::log_error($error, $plugin_slug, $context)` bereit.
Damit bleibt das Logformat über alle eigenen Plugins gleich: Plugin, fachlicher
Vorgang und vollständige Ursachekette werden gemeinsam protokolliert. Sichtbare
Fehlermeldungen bleiben bewusst Aufgabe des jeweiligen Fachplugins.

## Transaktionen

`FlzWpdbTransaction::run($callback, $operation)` führt mehrere
Modelloperationen atomar aus. Start, Commit und Rollback werden geprüft. Bei
einem Fehler enthält die weitergereichte Exception den fachlichen Vorgang und
die ursprüngliche Ursache; schlägt auch das Rollback fehl, werden beide Fehler
genannt.

Betroffene abhängige Plugins:

- `flz_ags`: AGs, Termine, Demo-Setup und Anmeldungen.
- `flz_elternsprechtag`: Buchungen, Termin-Reset und Lehrkräfte-Import.
- `flz_probeunterricht`: Anmeldungen, Schulplätze und Teilnehmer-Reset.

### Migrationshinweis 1.4.0

Die Tabellennamen für Modelle mit CamelCase-Klassen wurden von der alten
Kleinschreibung auf Snake-Case umgestellt. Das betrifft insbesondere
`flz_elternsprechtag` und `flz_probeunterricht`. Die Umgebung ist noch nicht
produktiv; deshalb gibt es bewusst keine Alt-Tabellen-Kompatibilität und keine
automatische Datenmigration. Lokal genügt es, die betroffenen Plugins zu
deaktivieren/aktivieren oder alte Testtabellen gezielt zu entfernen und neu
anzulegen.

## Checks

Die Fehler- und Transaktionspfade können ohne WordPress-Datenbank geprüft
werden:

```bash
php flz_wpdb_objects/tests/error-handling-smoke.php
```
