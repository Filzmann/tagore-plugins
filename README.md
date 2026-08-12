# Tagore WordPress Workspace

Koordinations-Workspace für die eigenständigen WordPress-Plugin-Repositories
und das geplante Theme des Tagore-Gymnasiums.

## Einstieg

1. `tagore-wordpress.code-workspace` in VS Code öffnen.
2. DDEV bei Bedarf über den Task `DDEV: start tagore-local` starten.
3. Plugin-Code nur im jeweiligen Repository unter `repositories/` bearbeiten.
4. Eigene Komponenten per Symlink in `../tagore-local` bereitstellen.
5. Vor Abschluss `./scripts/check-fast` und bei Laufzeitänderungen
   `./scripts/check-local.sh` ausführen.

Jedes Plugin unter `repositories/<slug>/` besitzt eine eigene Git-Historie,
Regeln, Roadmap und Prüfwerkzeuge. Das geplante Theme erhält ebenfalls ein
eigenes Repository. WordPress-Core, Fremderweiterungen und Uploads bleiben in
der getrennten lokalen Laufzeit.

Weitere Details: [Workspace](docs/workspace.md),
[Architektur](docs/architecture.md) und [Agentenregeln](AGENTS.md).
