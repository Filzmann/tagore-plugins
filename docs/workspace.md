# Tagore WordPress Workspace

## Struktur

```text
tagore-plugins/
|-- AGENTS.md
|-- README.md
|-- tagore-wordpress.code-workspace
|-- .agents/skills/                 # wiederholbare WordPress-Abläufe
|-- .codex/                         # lokale Agentenrollen und Grenzen
|-- config/workspace-components.tsv # kanonisches Inventar
|-- docs/
|-- scripts/                        # Prüf- und Laufzeitharness
`-- repositories/                  # ignorierter Container eigenständiger Repos
    |-- flz_wpdb_objects/.git/
    `-- ...

../tagore-local/                    # getrennte DDEV-/WordPress-Laufzeit
`-- public/wp-content/
    |-- plugins/<slug> -> tagore-plugins/repositories/<slug>
    `-- themes/<slug>  -> tagore-plugins/repositories/<slug>
```

Das Repository bleibt aus historischen Gründen `tagore-plugins` benannt und
fungiert nur noch als Koordinator. `repositories/` ist im Koordinator ignoriert;
jeder Unterordner ist ein eigenständiges Git-Repository. Das Inventar verbindet
Repos, Slugs und Laufzeitlinks, ohne eine zweite Komponentenliste einzuführen.

## VS Code

`tagore-wordpress.code-workspace` öffnet den Koordinator, jedes Plugin-Repo und
die lokale Laufzeit als getrennte Wurzeln:

- `Tagore WordPress Coordination`: Inventar, Regeln und Harness;
- je eine benannte Plugin-Wurzel: der tatsächlich versionierte Quellcode;
- `Tagore Local Runtime`: die DDEV-Konfiguration. Der große WordPress-Baum
  `public/` ist in Explorer, Suche und Dateiwächter ausgeblendet.

Die Tasks bieten Struktur-/Schnellchecks, den lokalen Integrationscheck sowie
DDEV- und WP-CLI-Diagnosen. Pfade werden relativ zum Workspace bestimmt und
nicht über ein persönliches Home-Verzeichnis zum Projektvertrag gemacht.

## Komponenten

`config/workspace-components.tsv` ist die einzige manuell gepflegte Liste.
`scripts/check-workspace-structure` prüft daraus Pfade, Slugs, Plugin-Header
und Theme-Header. Neue Komponenten werden mit dem Skill
`create-wordpress-extension` registriert.

`config/quality-gates.tsv` ist keine zweite Komponentenliste, sondern die
gegen das Inventar geprüfte Zustandsprojektion für Commit-, CI-, Coverage-,
Abnahme- und Release-Gates. Fehlende oder zusätzliche Zeilen blockieren den
Workspace-Check. Zielvertrag und Übernahmephasen stehen in
`docs/quality-gates.md` und `docs/quality-rollout.md`.

Plugins heißen `flz_<name>`; Theme-Slugs heißen `flz-<name>`. Ein eigenes
Theme wird als `repositories/<slug>` entwickelt und nicht aus dem installierten
Fremdtheme `deep-light` heraus verändert.

## Lokale Laufzeit

DDEV-Befehle werden nur in `../tagore-local` ausgeführt:

```bash
cd ../tagore-local
ddev start
ddev describe
ddev wp core version
```

`scripts/link-local-components` erzeugt ausschließlich fehlende Symlinks für
registrierte eigene Komponenten. Es überschreibt keine realen Verzeichnisse
oder abweichenden Links. Da es die Laufzeit verändert, wird es nur auf
ausdrücklichen Auftrag ausgeführt.

Prüfpfade:

```bash
./scripts/check-workspace-structure # deklarative Struktur
./scripts/check-fast                # Struktur, JSON, PHP-Syntax, Git-Diff
./scripts/check-local.sh            # DDEV, WP-CLI, Symlinks und HTTP
./scripts/check-quality-gates --policy
./scripts/check-quality-gates --commit <slug>
./scripts/check-quality-gates --release <slug>
```

Der lokale Integrationscheck setzt laufendes DDEV voraus. Ein erfolgreicher
Schnellcheck ersetzt keine Browser-, Accessibility- oder echte
WordPress-Integrationsprüfung. Während der gestuften Übernahme blockieren
`--commit` normale Produktcommits und `--release` jede Releasefreigabe, bis die
jeweilige Komponenten-Konfiguration alle Pflichtnachweise als erzwungen
ausweist.
