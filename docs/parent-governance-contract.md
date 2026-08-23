# Parent-Governance-Vertrag für Komponenten-Repositories

Diese Datei ist die kanonische Quelle für die Governance-Hierarchie zwischen
dem Parent-Workspace und den eigenständig versionierten Plugin- und
Theme-Repositories. Der markierte Block wird vollständig in jede lokale
`AGENTS.md` übernommen. Die Repositories bleiben dadurch bei einem
Einzel-Checkout arbeitsfähig; die Projektion darf den Parent-Vertrag weder
verkürzen noch abschwächen.

<!-- COMPONENT-AGENTS-GOVERNANCE:START -->
## Parent-Governance-Vertrag: 1

- Die für dieses Komponenten-Repository anwendbaren Regeln des
  Parent-Workspaces sind verbindlich. Dazu gehören insbesondere gemeinsame
  Architektur-, Sicherheits-, Plugin- und Theme-Verträge,
  Repositorygrenzen sowie Workspace-, Quality-, Delivery- und Release-Gates.
- Diese lokale `AGENTS.md` und die lokalen Skills bleiben die vollständige,
  ohne Parent-Checkout arbeitsfähige Repository-Steuerung. Die anwendbaren
  Parent-Regeln werden dafür hier oder in den lokalen Skills mitgeführt.
- Vor Arbeit an der Komponente ist der lokale Skill
  `work-in-wordpress-extension` zu verwenden. Für beobachtbare Änderungen
  gilt zusätzlich der lokale Skill `test-driven-wordpress-change`.
- Repository-lokale Regeln dürfen Parent-Verträge konkretisieren und
  verschärfen, aber nicht abschwächen oder umgehen.
- Bei einem Widerspruch gilt bis zur Klärung die strengere Regel. Die Arbeit
  stoppt, bis die kanonische Quelle bestimmt, die Regelprojektionen
  synchronisiert und eine erforderliche Entscheidung dokumentiert ist.
- Ist der Parent-Workspace nicht verfügbar, bleibt die lokale Steuerung
  wirksam. Vor Cross-Component-, Release- oder Delivery-Arbeit muss ein
  vermuteter neuerer Parent-Stand oder eine Regelungslücke zuerst gegen den
  Parent geprüft werden.
<!-- COMPONENT-AGENTS-GOVERNANCE:END -->

Die Versionskennung wird nur bei einer inhaltlichen Vertragsänderung erhöht.
Der Parent-Test `tests/check-parent-governance-contract.sh` liest die
Komponentenliste ausschließlich aus `config/workspace-components.tsv`,
vergleicht den markierten Block mit allen dort registrierten
Komponenten-Repositories und prüft deren beide lokalen Pflicht-Skills gegen
die kanonischen Parent-Fassungen.
