# Komponentenregeln: flz_wpdb_objects

Zusätzlich gelten die Root-Regeln und die dortigen Skills
`work-in-wordpress-extension` und `test-driven-wordpress-change`.

Dieses gemeinsame Infrastruktur-Plugin besitzt kleine Datenbank-, Transaktions-
und Fehlerverträge. Vor Änderungen abhängige Fachplugins ermitteln und
Rückwärtskompatibilität prüfen. Es übernimmt weder fachliche Tabellenhoheit
noch UI. Schema- oder öffentliche API-Änderungen fallen unter die Stop-Regeln
des Root. Relevanter Smoke: `php tests/error-handling-smoke.php`; anschließend
Verbrauchertests und Root-`scripts/check-fast` ausführen.
