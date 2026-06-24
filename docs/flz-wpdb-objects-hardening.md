# Härtung von flz_wpdb_objects

## Begründung

Die Legacy-Methoden `get_by_where()`, `get_all()` und `count()` akzeptierten frei
formulierte SQL-WHERE-Fragmente. Dadurch konnten Aufrufer Werte per
Stringverkettung in Abfragen einsetzen. Die Methoden wurden entfernt; die
betroffenen Leseabfragen werden jetzt über validierte Spaltennamen und
vorbereitete Werte aufgebaut.

## Betroffene Plugins

- `flz_wpdb_objects`: Legacy-Methoden entfernt.
- `flz_elternsprechtag`: Abfragen auf die sicheren Methoden umgestellt.
- `flz_probeunterricht`: Abfragen auf die sicheren Methoden umgestellt.
- `flz_ags`: geprüft, verwendet die entfernten Methoden nicht.

## API-Migration

- `get_by_where( $where )` wird durch `get_by_fields( $fields )` ersetzt.
- `get_all( $where, $order_by )` wird durch
  `get_all_by( $fields, $order_by, $order, $limit )` ersetzt.
- `count( $where )` wird durch `count_by( $fields )` ersetzt.

`$fields` ist ein assoziatives Array aus Spaltennamen und Werten. Mehrere Felder
werden mit `AND` verknüpft. `null` erzeugt `IS NULL`; Arraywerte erzeugen eine
`IN`-Bedingung. Die Sortierung akzeptiert einen validierten Spaltennamen sowie
separat `ASC` oder `DESC`.

Das Entfernen der öffentlichen Legacy-Methoden ist eine bewusste, nicht
rückwärtskompatible API-Änderung. Alle Aufrufer in diesem Repository sind
migriert. Weitere lokale oder externe Aufrufer müssen vor einem Update nach dem
obigen Schema angepasst werden.

Eine Datenbankmigration ist nicht erforderlich: Tabellenstruktur und gespeicherte
Daten bleiben unverändert.
