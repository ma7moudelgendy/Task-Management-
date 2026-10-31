
---
trigger: glob
description: "Clean Code and AI-specific guardrails for production code."
globs: "*.php, *.ts, *.tsx, *.js, *.jsx, *.py, *.go, *.rs"
alwaysApply: true

---

# Clean Code Guard

## Functions & Naming
- Names reveal intent — no `data`, `result`, `temp`, `helper`, `manager`, `utils`, `handle_*`, `process_*`
- ≤20 lines, one level of abstraction, one thing per function
- ≤4 params (use DTO/config object beyond that)
- No boolean flags — split into two functions
- Command-Query Separation: return value XOR side effect, never both

## Comments
- Explain *why*, never *what* — delete paraphrasing comments
- Delete commented-out code, step-number scaffolding, banner comments
- Match file's existing style (casing, imports, error handling, logging)

## SOLID
- **SRP**: One actor per module (Accounting, Auth, Reporting each get their own class)
- **OCP**: Extend via new code, not edits — no type-tag branches for new variants
- **LSP**: No subclass refuses parent's contract (no "not implemented" overrides)
- **DIP**: Abstractions live with the client, not the implementation

## DRY / KISS / YAGNI
- DRY = don't duplicate *knowledge*, not text. Similar functions with different rules are not a violation.
- Wrong abstraction > duplication — re-inline before re-abstracting
- Cyclomatic complexity ≤10, nesting depth ≤5
- No speculative anything: no optional params, config flags, feature toggles, or base classes without a current caller

## Error Handling
- Catch only specific error types you can recover from — never broad catch-all handlers
- No null/empty returns from catch handlers unless the contract documents it
- No defensive guards for impossible cases (trust the type system)

## AI-Specific Guardrails
- Verify every import/external call against the actual installed library — do not hallucinate APIs
- No hardcoded "success" returns or fixture data in production code
- Re-derive from spec, don't copy-from-similar (off-by-one enters through copy-paste)
- Enumerate boundary cases (null/empty/one/many, off-by-one, even/odd) before writing them
- Strip dead code before delivery (unused imports, unreachable branches)

## Refactoring
- Preserve observable behavior — refactors and bug fixes are two separate operations, never bundle them
