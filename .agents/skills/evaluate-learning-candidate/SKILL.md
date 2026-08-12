---
name: evaluate-learning-candidate
description: Evaluate a reusable observation from Tagore WordPress work before proposing it as a durable rule, skill, script, test, hook, inventory contract, or documentation update. Use when work reveals a possible learning or the user asks to preserve one; do not use for ordinary summaries, guesses, temporary workarounds, or automatic rule edits.
---

# Evaluate a learning candidate

1. Gather reproducible evidence from code, tests, logs, documented WordPress
   behavior or explicit user confirmation.
2. Reject one-off state, speculation, sensitive content, temporary workaround,
   task-specific TODOs and facts better enforced by existing code/tests.
3. Classify the correct target:
   - root `AGENTS.md`: stable cross-component safety/architecture rule;
   - component `AGENTS.md`: stable component-specific domain rule;
   - skill: bounded repeatable workflow with clear trigger;
   - script/test/inventory: deterministic mechanically enforceable behavior;
   - `docs/`: explanatory human knowledge.
4. Prefer a technical check over prose when deterministic. Check for
   duplication and contradiction before proposing a new source.
5. Do not edit a durable rule without explicit approval. Record only open,
   evidence-backed candidates in `docs/learning-candidates.md`.

Use this proposal format:

```text
Mögliches Learning:
- Ebene: Workspace / Plugin / Theme / mehrere Komponenten
- Ziel: <AGENTS.md, Skill, Skript, Test, Inventar oder docs/...>
- Status: verifiziert / plausibel / unbestätigt / verworfen
- Evidenz: <reproduzierbarer Nachweis>
- Grund: <künftiger Nutzen und Einordnung>
- Vorgeschlagener Inhalt: <konkreter kurzer Vertrag>
```
