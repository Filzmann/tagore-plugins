# Komponentenregeln: flz_shortcode_redirect

Zusätzlich gelten die Root-Regeln und die dortigen Skills
`work-in-wordpress-extension` und `test-driven-wordpress-change`.

Dieses Fachplugin kapselt ausschließlich den kontrollierten Redirect-Vertrag.
Ziele und Requestwerte müssen validiert, erlaubte Protokolle/Hosts bewusst
begrenzt und Ausgaben beziehungsweise Fehler sicher behandelt werden. Für jede
Verhaltensänderung zuerst einen fokussierten Test ergänzen; aktuell existiert
nur der Root-PHP-Syntaxcheck als Baseline.
