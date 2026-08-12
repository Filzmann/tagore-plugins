# Tagore WordPress Workspace

Gemeinsamer Entwicklungs-Workspace für die eigenen WordPress-Plugins und
-Themes des Tagore-Gymnasiums.

## Einstieg

1. `tagore-wordpress.code-workspace` in VS Code öffnen.
2. DDEV bei Bedarf über den Task `DDEV: start tagore-local` starten.
3. Quellcode nur in diesem Repository bearbeiten.
4. Eigene Komponenten per Symlink in `../tagore-local` bereitstellen.
5. Vor Abschluss `./scripts/check-fast` und bei Laufzeitänderungen
   `./scripts/check-local.sh` ausführen.

Bestehende Plugins bleiben aus Kompatibilitätsgründen im Repository-Root.
Neue eigene Themes liegen unter `themes/<theme-slug>/`. WordPress-Core,
Fremderweiterungen und Uploads bleiben in der getrennten lokalen Laufzeit.

Weitere Details: [Workspace](docs/workspace.md),
[Architektur](docs/architecture.md) und [Agentenregeln](AGENTS.md).
