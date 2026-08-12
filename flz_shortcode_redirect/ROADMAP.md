# Roadmap: flz_shortcode_redirect

## Prüfstatus

**Teilweise regelkonform / P1.** Das Ziel wird mit `wp_safe_redirect()` auf
WordPress-erlaubte Hosts begrenzt, Eingabe wird bereinigt, Ausgabe escaped und
Fehler werden sicher behandelt. Der eigentliche Zugriffsvertrag über ein Secret
in URL und Seiteninhalt ist weder vollständig spezifiziert noch getestet; für
das Plugin gibt es nur den Root-Syntaxcheck.

## P1

1. Fachvertrag entscheiden und dokumentieren: exakte erlaubte Hosts/Protokolle,
   Verhalten bei leerem Ziel oder leerem Secret, angemeldete Rollen und
   gewünschte Lebensdauer des Zugangs. Keine stillschweigende Autorisierung
   allein durch UI-Sichtbarkeit.
2. Secret nicht dauerhaft im Klartext in Shortcode-Inhalt und Query-Logs als
   langfristigen Zugriffsnachweis verwenden. Einen WordPress-nativen,
   zeitbegrenzten/signierten oder capability-basierten Vertrag entwerfen;
   Umstellung als öffentlichen Kompatibilitätswechsel behandeln.
3. Fokussierte Tests ergänzen: angemeldet, korrekt/falsch/fehlendes Secret,
   interner erlaubter Redirect, externer/gefälschter Host, bereits gesendete
   Header und fehlende Shared-Logging-Abhängigkeit.

## P2

1. Redaktionellen Gutenberg-Block über
   `flz_ui_register_shortcode_block()` anbieten, sofern der Shortcode weiterhin
   redaktionell verwendet wird; Abhängigkeit defensiv behandeln.
2. Plugin-Header um Textdomain und unterstützte Versionen ergänzen; alle Texte
   konsistent über die komponenteneigene Domain übersetzen.
3. Redirect möglichst vor der Ausgabe in einer geeigneten WordPress-
   Requestphase ausführen; Shortcode-Fallback nur für unvermeidbare
   Kompatibilität beibehalten und testen.
