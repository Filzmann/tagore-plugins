# Härtung von flz_wpdb_objects

## Begründung

Die Legacy-Methoden `get_by_where()`, `get_all()` und `count()` akzeptierten frei
formulierte SQL-WHERE-Fragmente. Dadurch konnten Aufrufer Werte per
Stringverkettung in Abfragen einsetzen. Die Methoden wurden entfernt; die
betroffenen Leseabfragen werden jetzt über validierte Spaltennamen und
vorbereitete Werte aufgebaut.

## Betroffene Plugins

- `flz_wpdb_objects`: Legacy-Methoden entfernt und Modell-Hydrierung,
  Formulardaten sowie CSV-Helfer gehärtet.
- `flz_elternsprechtag`: Abfragen, Formulardaten und CSV-Aufrufe umgestellt.
- `flz_probeunterricht`: Abfragen und Formulardaten umgestellt.
- `flz_ags`: geprüft, verwendet die entfernten Methoden nicht.

## API-Migration

- `get_by_where( $where )` wird durch `get_by_fields( $fields )` ersetzt.
- `get_all( $where, $order_by )` wird durch
  `get_all_by( $fields, $order_by, $order, $limit )` ersetzt.
- `count( $where )` wird durch `count_by( $fields )` ersetzt.
- `createCsv( ... )` wird durch `flz_wpdb_objects_create_csv( ... )` ersetzt.
- Die unpräfixierten und repo-intern unbenutzten Helfer `debug()` und
  `write_log()` wurden ohne Ersatz entfernt.

`$fields` ist ein assoziatives Array aus Spaltennamen und Werten. Mehrere Felder
werden mit `AND` verknüpft. `null` erzeugt `IS NULL`; Arraywerte erzeugen eine
`IN`-Bedingung. Die Sortierung akzeptiert einen validierten Spaltennamen sowie
separat `ASC` oder `DESC`.

`assignPostData( $data, $allowed_fields )` erfordert jetzt eine explizite Liste
erlaubter Felder und übernimmt die Property `id` grundsätzlich nicht mehr. Alle
Aufrufer in diesem Repository verwenden passende Feldlisten, damit interne
Status- oder Token-Properties nicht per Mass Assignment geändert werden können.

Die Setting-Helfer in Elternsprechtag und Probeunterricht verwenden ebenfalls
`get_by_fields()` und enthalten keine eigenen Raw-SQL-Abfragen mehr. Beim Reset
der Probeunterricht-Teilnehmer wurde ein doppeltes Raw-`TRUNCATE` entfernt; die
übergebene neue Sitzplatzzahl wird nun gespeichert.

Die automatische Auflösung von Fremdschlüsseln berücksichtigt weiterhin
Spalten mit dem Suffix `_id`. Sie wird jetzt aber nur ausgeführt, wenn eine
gleichnamige Property ohne `_id` eindeutig mit einer Unterklasse von
`FlzWpdbObject` typisiert ist. Unbekannte Beziehungen bleiben Rohwerte und
verursachen keinen Reflection-Fehler mehr.

Das Entfernen der öffentlichen Legacy-Methoden und unpräfixierten Helfer ist eine
bewusste, nicht rückwärtskompatible API-Änderung. Alle Aufrufer in diesem
Repository sind migriert. Weitere lokale oder externe Aufrufer müssen vor einem
Update nach dem obigen Schema angepasst werden.

Eine Datenbankmigration ist nicht erforderlich: Tabellenstruktur und gespeicherte
Daten bleiben unverändert.

## Verbleibende Risiken

- `delete_table()` und `truncate_table()` sind technische Modellmethoden ohne
  eigene Capability- oder Nonce-Prüfung. Sie dürfen nur aus bereits abgesicherten
  Aktivierungs- oder Admin-Aktionen aufgerufen werden.
- Die bestehenden Elternsprechtag-CSV-Exporte werden weiterhin unter festen
  Dateinamen im öffentlich erreichbaren WordPress-Uploadverzeichnis erzeugt.
  Dateinamen sind nun gegen Pfadmanipulation geschützt, die personenbezogenen
  Exporte sollten aber in einem eigenen Schritt auf einen authentifizierten,
  nonce-geschützten Download ohne dauerhaft öffentliche Datei umgestellt werden.
- CSV-Zeilen werden weiterhin von den Fachmodellen als fertige Zeichenketten
  geliefert. Werte mit Trennzeichen, Zeilenumbrüchen oder führenden
  Tabellenkalkulations-Formeln sind damit noch nicht zentral neutralisiert.
- Die historischen Lifecycle-Hooks `beforeDelete()` und `afterDelete()` werden
  sowohl für einzelne Zeilen als auch für ganze Tabellen verwendet. Eine
  Trennung wäre wartbarer, wäre aber eine weitere API-Änderung und ist deshalb
  nicht Bestandteil dieser Härtung.
