# Prüfung von Demo- und Beispieldaten

Stand: 22. August 2026. Geprüft wurden alle im Komponenten-Inventar
registrierten Plugin-Repositories, ihre Tests und die lokale DDEV-Datenbank.
Konkrete Personenwerte wurden im Prüfbericht nicht ausgegeben.

## Ergebnis nach Komponenten

| Komponente | Ergebnis |
| --- | --- |
| `flz_wpdb_objects` | Keine eigenen Fach- oder Demo-Datensätze; Tests verwenden technische, synthetische Werte. |
| `flz_ui_components` | Nichtpersistente Admin-Demo. Reale Schuladresse und -URL wurden durch reservierte `example.test`-Werte ersetzt und per Smoke-Test abgesichert. |
| `flz_shortcode_redirect` | Keine Demo-Datensätze. Klartext-Secrets werden nicht mehr akzeptiert; Links verwenden eine kurzlebige, seitengebundene HMAC-Signatur. Das Legacy-Attribut wurde aus der vorhandenen lokalen Inhaltsseite entfernt. |
| `flz_elternsprechtag` | Demo-Personen sind klar als Demo benannt und nutzen ausschließlich `example.test`. Lokal wurden zwei solche Eltern-Datensätze gefunden; keine abweichend adressierte Demo-Person. |
| `flz_probeunterricht` | Demo-Personen im Quellcode sind klar synthetisch und nutzen `example.test`; lokal ist derzeit keine Demo-Person gespeichert. Die Standard-Schulliste enthält öffentliche Organisationsnamen, keine Privatdaten. |
| `flz_ags` | Das bisherige Demo-Setup übernahm Titel, Freitext, Leitungsnamen und Bilder veröffentlichter Seiten. Neue Demo-Daten sind nun synthetisch; nur Seitenbezug, Jahrgänge und Terminstruktur bleiben erhalten. |

## Grenzen der Bestandsprüfung

Die AG-Tabellen enthalten acht bestehende Kurse, davon acht mit Leitungsangabe
und sieben mit Bild. Das Schema besitzt keine Herkunfts- oder Demo-Markierung.
Diese Datensätze wurden deshalb nicht verändert: Eine automatisierte
Klassifikation oder Bereinigung könnte fachliche Bestandsdaten beschädigen.
Vor einer späteren Datenmigration ist ein expliziter Provenienzvertrag nötig.

Offizielle Funktionsadressen und öffentliche Schulnamen wurden nicht als
Privatdaten bewertet. Zugangsdaten, private Schlüssel und private E-Mail-
Adressen wurden im aktuellen Quellstand nicht gefunden.

## Dauerhafte Nachweise

- `flz_ui_components/tests/demo-data-privacy-smoke.php` verbietet reale
  Organisationsadresse und -URL in der UI-Demo.
- `flz_ags/tests/demo-data-privacy-smoke.php` verhindert, dass der
  AG-Demo-Konverter wieder Titel, Freitext, Leitungsnamen oder Bilder aus
  Inhaltsseiten übernimmt.
- Die Komponenten-Smokes und PHPCS-Sicherheitsbaseline laufen über
  `scripts/check-fast` des Koordinations-Workspace.
