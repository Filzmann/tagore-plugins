---
name: verify-wordpress-workspace
description: Verify the Tagore WordPress source workspace, registered plugins/themes, local symlinks, DDEV integration, or release readiness at an explicitly selected depth. Use for workspace health checks and completion evidence; a diagnostic run is not a deployment or release approval.
---

# Verify the WordPress workspace

## Choose depth

- `structure`: run `scripts/check-workspace-structure` for rules, skills,
  inventory, paths, headers and workspace JSON.
- `fast`: run `scripts/check-fast` for structure, PHP syntax and diff hygiene.
- `local`: run `scripts/check-local.sh` for registered symlinks, DDEV, WP-CLI
  and HTTP after confirming runtime commands are authorized.
- `component`: add the target component's own tests and relevant shared
  provider/consumer tests.
- `commit`: run `scripts/check-quality-gates --commit <slug>`; during the
  documented transition this deliberately blocks normal product commits until
  the component's CI and coverage adoption is complete.
- `release`: run `scripts/check-quality-gates --release <slug>` first. Require
  clean repositories, accepted manual protocol, enforced coverage/CI and a
  reproducible component-specific artifact. Never treat a diagnostic run or
  the plugin staging script as a release verdict or theme delivery.

## Procedure

1. Read root and applicable component rules. Inspect `git status --short`.
2. Resolve scope from `config/workspace-components.tsv`; do not use a copied
   list.
3. Run the selected level from the repository root. Escalate sandbox access
   narrowly if Docker/DDEV diagnostics require it; do not reinterpret an
   environment failure as a product defect.
4. For behavior touching capabilities, nonces, REST/AJAX, SQL, paths or
   privacy, require focused allow/deny evidence.
5. For UI/theme work, report automated structure separately from browser,
   keyboard, responsive, contrast and visual evidence.
6. A release check that reports `RELEASE BLOCKIERT` remains blocked; do not
   bypass it with a Fast-/Local-Check or reinterpret a transition commit as a
   release candidate.

## Verdict

Return `bestanden`, `teilweise geprüft`, or `fehlgeschlagen`. List exact
commands/results, environment, components, skipped gates and residual risks.
Never mutate runtime, activate/deactivate, switch themes, deploy, commit or
stage merely to complete verification.
