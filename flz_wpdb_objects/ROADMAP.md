# Roadmap: flz_wpdb_objects

## Prüfstatus

**Teilweise regelkonform / P1.** SQL-Werte werden zentral gebunden, dynamische
Bezeichner validiert, Fehler gekapselt und der umfangreiche CLI-Smoke besteht.
Als Shared-Plugin bietet die Komponente jedoch weiterhin einen API-Pfad für
dauerhafte Dateien im öffentlichen Upload-Verzeichnis und besitzt noch keinen
formal versionierten Verbraucher-/Kompatibilitätsnachweis.

## P1

1. `flz_wpdb_objects_create_csv_file()` als öffentlichen Exportpfad ablösen.
   Bevorzugter Vertrag: CSV sicher im Speicher erzeugen und nach vorgelagerter
   Autorisierung direkt streamen. Persistente Dateien nur für ausdrücklich
   genehmigte, nicht personenbezogene Anwendungsfälle über WordPress Filesystem
   API und dokumentierten Lebenszyklus zulassen.
2. Upload-Helper um vollständige serverseitige Dateiprüfung erweitern oder die
   Verantwortung als überprüfbaren Vertrag präzisieren: Uploadfehlercode,
   tatsächlicher Upload, Größen-/Zeilenlimits, MIME/Dateityp und sichere
   Fehlerfälle. Capability/Nonce verbleiben nachweisbar beim Fachworkflow.
3. Öffentliche API, unterstützte Verbraucher und Breaking-Change-Prozess
   dokumentieren; Verbraucher-Smokes für Elternsprechtag, Probeunterricht und
   AGs als Gate ausführen.

## P2

1. Tests für SQL-Identifier, Null-/IN-Abfragen, Hydrierung, Transaktionsfehler,
   CSV-Formelneutralisierung, große/ungültige Uploads und Download-Header
   ergänzen; reale WordPress-DB-Integration separat prüfen.
2. Schema-Helfer dokumentieren und sicherstellen, dass Fachmodelle eine eigene
   Schema-/Versionsquelle behalten; destruktive Tabellenmethoden klar als
   privilegierte interne Werkzeuge kennzeichnen.
3. Plugin-Header (Platzhalter-URIs, Textdomain, unterstützte PHP-/WordPress-
   Version) und Formatierung bereinigen.

## P3

1. Coverage der Kernklassen messen und die 85-Prozent-Zielmarke für wesentlich
   geänderten Code nachweisen.
2. Release-Artefakt und Rückwärtskompatibilität unabhängig von den
   Fachplugins reproduzierbar prüfen.
