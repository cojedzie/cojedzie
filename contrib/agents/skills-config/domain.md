# Domain Docs

How the engineering skills should consume this repo's domain documentation when exploring the codebase. This repo is
single-context: one glossary and one set of ADRs for both `api/` and `front/`.

## Before exploring, read these

- **`contrib/GLOSSARY.md`**: the project glossary. It is not agent-specific, so it sits next to the ADRs rather than
  in `contrib/agents/`.
- **`contrib/adr/`**: read the ADRs that touch the area you're about to work in. Only the top level is current; skip
  `contrib/adr/archive/` unless a current ADR refers to one. New ADRs follow the format and template in
  `contrib/adr/README.md`.
- **`contrib/agents/wiki/`**: the project knowledge base. Read the page(s) matching the topic, as listed in
  `AGENTS.md`.

If any of these files don't exist, **proceed silently**. Don't flag their absence; don't suggest creating them
upfront. The `/domain-modeling` skill (reached via `/grill-with-docs` and `/improve-codebase-architecture`) creates
them lazily when terms or decisions actually get resolved.

## File structure

```
/
├── contrib/
│   ├── GLOSSARY.md
│   ├── adr/
│   │   ├── README.md                  ← format and template
│   │   ├── 0001-behavioural-tests-with-behat.md
│   │   └── archive/                   ← deprecated and superseded, skip
│   └── agents/wiki/
├── api/
└── front/
```

## Use the glossary's vocabulary

When your output names a domain concept (in an issue title, a refactor proposal, a hypothesis, a test name), use the
term as defined in `contrib/GLOSSARY.md`. Don't drift to synonyms the glossary explicitly avoids.

If the concept you need isn't in the glossary yet, that's a signal: either you're inventing language the project
doesn't use (reconsider) or there's a real gap (note it for `/domain-modeling`).

## Flag ADR conflicts

If your output contradicts an existing ADR, surface it explicitly rather than silently overriding:

> _Contradicts ADR 0025 (imported schedules are versioned by validity), but worth reopening because…_
