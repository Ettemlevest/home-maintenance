# AI Guidance & Planning Documents

Everything an AI session (or a human) needs to understand what this app is and how it gets built. The application itself lives at the repository root (from Phase 0 onward); this folder holds the planning material and its history.

## Structure and precedence

```text
prompts/
├─ plan/      CURRENT — the implementation plan; start here
├─ specs/     SOURCE — the product vision the plan was distilled from
└─ archive/   HISTORY — early drafts and resolved reviews; never treat as guidance
```

**Precedence: `plan/` overrides `specs/`, `specs/` override `archive/`.** Where the plan makes a decision (scope cuts, schema changes, the four work item types, multi-asset rules), that decision wins over anything a spec says. Each spec carries a status banner as a reminder.

## Contents

| Folder | What's inside |
|---|---|
| [`plan/`](./plan/README.md) | MVP scope and simplifications, resolved technical decisions, 17 implementation phases, seed data template |
| [`specs/`](./specs/) | The ten source documents (`01-overview` … `10-future-plans`): requirements, schema draft, statuses and due dates, recurrence, integrations, dashboard/UX, architecture, deployment, deferred features |
| [`archive/`](./archive/) | `drafts/` — the earliest planning notes; `todo-items.md` — the planning cross-check review, fully resolved by `plan/02` (kept as the lookup table for its #1–#33 numbering) |

## Reading order for a new session

1. [`plan/README.md`](./plan/README.md) — stack, principles, document map
2. [`plan/01-mvp-scope-and-simplifications.md`](./plan/01-mvp-scope-and-simplifications.md) — what gets built, what was cut
3. [`plan/02-technical-decisions.md`](./plan/02-technical-decisions.md) — the settled decisions
4. [`plan/03-implementation-phases.md`](./plan/03-implementation-phases.md) — the build order; find the current phase
5. `specs/` — only when the plan defers to them for feature intent or worked examples
