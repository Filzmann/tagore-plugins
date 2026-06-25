# flz_wpdb_objects

## Fehlervertrag

Datenbank-, Modell-, Hook- und Dateisystemfehler werden als
`flz_wpdb_objects\FlzWpdbObjectsException` weitergegeben. Beim Ergänzen von
Kontext bleibt die ursprüngliche Exception über `getPrevious()` erhalten.
Aufrufer sollen die vollständige Kette protokollieren und an der UI-Grenze eine
separate, escapte Meldung ohne interne Details ausgeben.

## Transaktionen

`FlzWpdbTransaction::run($callback, $operation)` führt mehrere
Modelloperationen atomar aus. Start, Commit und Rollback werden geprüft. Bei
einem Fehler enthält die weitergereichte Exception den fachlichen Vorgang und
die ursprüngliche Ursache; schlägt auch das Rollback fehl, werden beide Fehler
genannt.

Die API-Erweiterung ist additiv. Bestehende Modellmethoden und Tabellen werden
nicht geändert. Betroffene abhängige Plugins:

- `flz_ags`: AGs, Termine, Demo-Setup und Anmeldungen.
- `flz_elternsprechtag`: Buchungen, Termin-Reset und Lehrkräfte-Import.
- `flz_probeunterricht`: Anmeldungen, Schulplätze und Teilnehmer-Reset.

Für `flz_wpdb_objects` selbst ist keine Datenbankmigration erforderlich.
`flz_ags` stellt seine noch nicht produktiven Tabellen separat auf
modellabgeleitete Namen um; der dortige README-Migrationshinweis beschreibt den
bewussten Verlust vorhandener Testdaten.

## Checks

Die Fehler- und Transaktionspfade können ohne WordPress-Datenbank geprüft
werden:

```bash
php flz_wpdb_objects/tests/error-handling-smoke.php
```
