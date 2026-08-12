# Komponentenregeln: flz_ui_components

Zusätzlich gelten die Root-Regeln und die dortigen Skills
`work-in-wordpress-extension` und `test-driven-wordpress-change`.

Dieses gemeinsame Infrastruktur-Plugin stellt Rendering-, Formular-,
Validierungs- und Blockverträge bereit. Es enthält keine Fachlogik oder
Berechtigungsentscheidungen. Öffentliche Renderer-/Helper-Änderungen brauchen
Verbraucherprüfung. Relevante Smokes: `php tests/renderer-smoke.php` und
`php tests/validator-smoke.php`; anschließend Root-`scripts/check-fast`.
