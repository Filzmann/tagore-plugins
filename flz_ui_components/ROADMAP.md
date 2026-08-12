# Roadmap: flz_ui_components

## Prüfstatus

**Weitgehend strukturell, aber nur teilweise nachgewiesen / P1.** Renderer und
Validator escapen beziehungsweise validieren kontextbezogen, die Admin-Demo
prüft Capability und Nonce, Fokuszustände sind vorgesehen und beide CLI-Smokes
bestehen. Der große öffentliche UI-Vertrag, JavaScript und reale
Barrierefreiheit sind noch nicht durch Verbraucher-/Browsertests abgesichert.

## P1

1. Öffentliche Renderer-, Validator-, Block- und Assetverträge versionieren;
   Änderungen müssen mindestens die drei Fachverbraucher testen. Breaking
   Changes benötigen Migration und Freigabe.
2. Sicherheits-Contract-Tests für alle HTML-Kontexte und frei übergebbaren
   Attribute ergänzen, insbesondere URL, `content`, `description`, versteckte
   Felder, Karten, CSV-Hinweise und Blockattribute.
3. Assets nur auf Seiten/Requests laden, die FLZ-Komponenten verwenden. Das
   aktuelle globale Frontend- und Admin-Enqueue durch registrieren-plus-
   bedarfsgesteuertes Enqueue ersetzen und Verbraucher-Smokes hinzufügen.

## P2

1. Inline-`onclick`/`window.confirm` aus Renderer-Ausgabe entfernen und über das
   registrierte Script mit CSP-freundlichen Data-Attributen anbinden.
2. JavaScript-Tests für Fokusfalle, Escape, dynamische Matrixzeilen,
   bearbeitbare Zeilen und Block-Inspector ergänzen.
3. Alle nutzersichtbaren Preset-, Fehler- und Demotexte über
   `flz-ui-components` übersetzbar machen; Editor-Script-Übersetzungen laden.
4. Semantik, Tastaturführung, Screenreader-Namen, Kontrast und Responsive-
   Verhalten der Komponentenbibliothek im Browser dokumentiert prüfen.

## P3

1. API-Referenz aus ausführbaren Beispielen/Tests ableiten und veraltete
   Beispiele automatisiert erkennen.
2. CSS-/JS-Build- und Größenbudget sowie Coverage für den Shared-Vertrag als
   CI-Gate definieren.
