# Roadmap: schlankes Tagore-Theme

## Ziel

Ein neues Repository `flz-tagore` soll die erkennbare Struktur und Optik der
aktuellen Website mit einer kleinen, nachvollziehbaren Theme-Codebasis
realisieren. Es ersetzt langfristig `deep-light` 1.0.6, ohne dessen große
Customizer- und Spezialtemplate-Oberfläche fortzuführen. Das Theme enthält nur
Darstellung; Datenmodelle, Rollen und Workflows bleiben in Plugins.

Das installierte Deep-Light-Paket ist GPLv2-or-later und basiert auf
Underscores. Trotzdem wird zunächst ein Clean-Room-Ansatz verfolgt: sichtbare
Struktur, Inhalte, Abstände, Typografie und responsive Zustände werden
dokumentiert; Quellcode oder Assets werden nur übernommen, wenn Herkunft,
Lizenz, tatsächlicher Bedarf und Attribution dateibezogen geklärt sind.

## Priorität 0 – erst nach der Repository-Trennung

1. Eigenes Repository, Regeln, README, Tests und Releasepfad für `flz-tagore`
   anlegen; kein Themewechsel.
2. Theme-Typ nach Bestandsaufnahme entscheiden. Ausgangshypothese ist ein
   schlankes Classic Theme, weil die bestehende Seite aus einem Classic Theme
   migriert wird; ein Block Theme wird nur gewählt, wenn die redaktionellen
   Strukturen dadurch tatsächlich einfacher bleiben.
3. Unterstützte WordPress-/PHP-Versionen, Browserziel, Lizenz und
   Performancebudget dokumentieren.

## Priorität 1 – visuelle und strukturelle Inventur

1. In der lokalen DDEV-Kopie repräsentative Seitenzustände erfassen:
   Startseite, Standardseite, Beitrag, Archiv, Suche, 404 sowie alle sichtbaren
   Pluginformulare. Desktop, Tablet, Mobil, Tastatur und angemeldete
   Admin-Leiste getrennt dokumentieren.
2. Header, Logo, Navigation, Inhaltsbreite, Sidebar, Footer, Typografie,
   Farbwerte, Abstände, Breakpoints und wiederkehrende Komponenten als
   überprüfbare Design-Tokens erfassen.
3. Quellen des aktuellen Aussehens trennen: Theme-Dateien, Customizer-
   `theme_mods`, zusätzliches CSS, Block-/Editorstyles, Widgets, Menüs,
   Page-Builder-Ausgabe und Plugin-CSS. Datenbankwerte nur lesen/exportieren,
   nicht verändern.
4. Festhalten, welche Deep-Light-Spezialtemplates tatsächlich genutzt werden.
   Ungenutzte Course-, Gallery-, Portfolio-, Sermon- oder Page-Builder-Templates
   werden nicht nachgebaut.

## Priorität 2 – minimaler Aufbau

1. Semantische Template-Hierarchie mit `header.php`, `footer.php`,
   `index.php`, `page.php`, `single.php`, `archive.php`, `search.php`, `404.php`
   und kleinen Template Parts erstellen.
2. Ein zentrales Stylesheet mit CSS Custom Properties für Farben, Typografie,
   Maße und Breakpoints; keine Optionsmatrix und kein eigener Page Builder.
3. Core-Funktionen für Logo, Navigation, Beitragsbilder, Editorstyles und
   sinnvolle Wide-/Align-Unterstützung nutzen. Nur tatsächlich benötigte
   Widgetbereiche registrieren.
4. Lokale Schriftdateien nur bei geklärter Lizenz und echtem Bedarf; keine
   externen Font-/Tracking-Requests.
5. Plugin-Ausgaben über stabile Klassen und kleine optionale Styles integrieren,
   ohne Fachplugins vom Theme abhängig zu machen.

## Priorität 3 – Parität und Barrierefreiheit

1. Screenshot-/Browservergleich gegen die dokumentierte Ausgangsoptik; visuelle
   Abweichungen bewusst entscheiden statt Deep-Light-Regeln blind zu kopieren.
2. Tastaturmenü, sichtbare Fokuszustände, Skip-Link, Landmarken,
   Überschriftenhierarchie, Alternativtexte, Formlabels und Fehlermeldungen
   prüfen.
3. Responsive Verhalten, lange Titel, Zoom, schmale Viewports, reduzierte
   Bewegung und Kontrast testen.
4. Performancebudget für CSS/JS, Schriftdateien, Bildgrößen und Requests
   messen. Kein JavaScript ohne konkrete Interaktion.

## Priorität 4 – kontrollierte Migration

1. Menüs, Logo, Widgets, Startseitenzuordnung und zusätzliches CSS als
   explizite Migrationsmatrix erfassen; keine automatische Übernahme unbekannter
   Customizerdaten.
2. Theme in DDEV installieren, aber erst nach separater Freigabe aktivieren.
3. Vorher-/Nachher-Smokes für URLs, Templates, Plugins, Formulare, Suche,
   Fehlerseiten und mobile Navigation durchführen.
4. Rückbau durch Rückwechsel auf `deep-light` dokumentieren. Staging bleibt ein
   eigenes, zunächst trockenes Gate; Produktion wird nicht direkt geändert.

## Definition of Done

- kein benötigtes Layout hängt mehr von Deep Light, Deepcore oder einem
  Page-Builder-Themevertrag ab;
- die vereinbarten Referenzseiten sind visuell, responsiv und per Tastatur
  geprüft;
- Pluginfunktionen bleiben bei Themewechsel funktionsfähig;
- CSS/JS und Templates enthalten nur belegten Bedarf;
- Lizenz-/Attributionsdatei nennt jede übernommene Drittquelle;
- reproduzierbares Theme-Artefakt und dokumentierter Rückbau liegen vor.
