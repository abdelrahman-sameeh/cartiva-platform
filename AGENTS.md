# AGENTS.md — Core Engineering Rules

This file contains **project-wide rules only**. It must stay small, general, and independent of any feature, service, integration, or vendor.

## 1. Guiding principles

- **KISS:** prefer the simplest correct, readable solution. Avoid cleverness and unnecessary layers.
- **YAGNI:** implement only what the current requirement needs. Do not build for hypothetical features.
- **DRY:** reuse existing logic and extract repeated business rules when there is a real benefit. Do not abstract prematurely.
- **SOLID (pragmatically):** keep responsibilities focused, dependencies clear, and components replaceable when there is a real reason. Do not introduce patterns or interfaces just to satisfy a checklist.
- **Consistency:** follow the project's established naming, conventions, and style before introducing a new approach.
- **Clarity first:** use descriptive names, short focused methods, explicit behavior, and minimal comments that explain *why*, not the obvious *what*.

## 2. Before making changes

1. Inspect relevant existing code, tests, migrations, configuration, and project conventions.
2. Understand the request and identify its scope and edge cases. Do not invent requirements.
3. Reuse current framework capabilities and existing abstractions where appropriate.
4. For changes involving architecture, database schema, public interfaces, or several files, propose a short plan (files, rationale, risks) **and wait for approval** before editing.
5. For an analysis or review request, **do not edit files**.

## 3. Implementation rules

- Make the **smallest coherent change** that solves the task.
- Keep unrelated behavior, file formatting, naming, and folder structure untouched.
- Favor composition and dependency injection where they improve clarity or testing; avoid unnecessary interfaces, traits, repositories, factories, and base classes.
- Do not put unrelated responsibilities into one class, and do not split trivial behavior into many one-method classes.
- Keep controllers focused on HTTP concerns; use request validation and authorization where appropriate.
- Keep models focused on data, relationships, casts, and cohesive model behavior.
- Place nontrivial business workflows in a suitable application/domain component **only when needed**.
- Keep external integration details separate from core business rules when practical.
- Respect existing APIs and database contracts; explain any breaking change before implementing it.
- Do not add dependencies or change configuration without a clear reason.

## 4. File and folder organization

Follow existing Laravel conventions unless the repository has an established alternative:

| Responsibility | Usual location |
| --- | --- |
| HTTP endpoints | `app/Http/Controllers/` |
| Request validation / authorization | `app/Http/Requests/` |
| Eloquent models | `app/Models/` |
| Resource authorization | `app/Policies/` |
| Reusable business workflows (when justified) | `app/Services/` |
| Asynchronous work (when justified) | `app/Jobs/` |
| Database schema | `database/migrations/` |
| Automated tests | `tests/Feature/` and `tests/Unit/` |

- Place code based on its **responsibility**, not arbitrarily.
- Use names that describe the purpose of a class or file.
- Do not create empty directories, placeholder files, or elaborate folder hierarchies for future work.
- Do not reorganize unrelated files as part of a feature task.

## 5. Security and data integrity

- Validate untrusted input and authorize access to protected resources.
- Never trust client-provided ownership, permissions, calculated totals, or sensitive state changes without server-side verification.
- Do not hardcode secrets or expose sensitive data in source code, responses, or logs.
- Use database constraints, transactions, and concurrency protection where required by the actual workflow.
- Handle failures explicitly; do not silently swallow exceptions.
- Make retryable operations safe against duplicate execution where relevant.

## 6. Testing and review

- Add or update focused tests for meaningful behavior; consider success, failure, authorization, and relevant edge cases.
- Do not introduce real external network calls into routine automated tests.
- Run relevant available checks, such as:

  ```bash
  php artisan test
  ./vendor/bin/pint --test
  git diff --check
  ```

- Inspect `git diff` before declaring work complete. Avoid unrelated changes.
- Report which tests/checks were **actually** run and their results; never claim unrun checks passed.
- Do not automatically commit, push, or merge unless explicitly asked.

## 7. Communicating changes

After implementation, provide a concise summary:

1. What changed and why.
2. Files added or modified.
3. Any important design decisions or trade-offs.
4. Tests run and outcomes.
5. Remaining risks, assumptions, or manual checks.

The developer must be able to understand and review the code; **do not treat working output as sufficient proof of quality**.

## 8. Feature-specific instructions

**Do not add feature, service, provider, or business-domain rules to this root file.**

When a feature genuinely needs additional guidance, keep it separate, for example:

- `docs/features/<feature>.md` for feature behavior and design decisions.
- A nearer `AGENTS.md` inside a relevant directory for instructions scoped to that directory.

Read applicable feature documentation before working in that area. Create additional instruction files **only when needed and approved**; keep them consistent with these core rules.
