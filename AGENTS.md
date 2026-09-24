# Directors Resale Platform — Agent Execution Rules

These repository rules govern AI-assisted work. They preserve the Architect's approved scope, the review-branch workflow, and the backend/frontend boundaries recorded in the canonical project documentation.

## Role separation

- The Architect / Technical Project Manager defines scope, architecture, boundaries, and acceptance criteria.
- Codex / Terra executes only the approved scoped internal unit.
- The human project owner controls the final merge into `feature/listing-domain-architecture`.

## Branch workflow

- Perform all implementation work on `codex-review`.
- Never implement directly on `feature/listing-domain-architecture`.
- Never merge into the official branch.
- Never force-push the official branch.
- After completing a task, commit and push to `origin/codex-review`.
- The Architect reviews `codex-review` on GitHub.
- Only after explicit approval may the human owner merge `codex-review` into `feature/listing-domain-architecture`.

## Completion workflow

Before reporting a completed unit:

1. Review the final diff.
2. Run the required tests and checks for that unit.
3. Run `git diff --check`.
4. Run `git status --short`.
5. Confirm that only intended files changed.
6. Commit the completed task.
7. Push to `origin/codex-review`.
8. Report changed files, summary, verification results, commit SHA, and a `READY` or `NOT READY` verdict.

## Scope discipline

- Work on one internal unit at a time.
- Do not start future units automatically.
- Do not redesign approved architecture.
- Do not invent sprint numbers or names without checking current documentation.
- Do not modify unrelated files.
- Preserve historical documentation and valid UTF-8.
- Do not introduce mojibake.

## Architecture boundaries

- Preserve the separation of Property, Ownership, Listing, Request, Sale Closing, and Commission.
- Property Setup Complete is not Listing Ready.
- Property or Profile saves must not create Listing side effects.
- Do not fake media or private-document persistence.
- Do not invent frontend completeness truth when the backend does not provide it.
- Backend authorization remains authoritative; frontend permission checks are UX only.
- Parent Franchise access does not automatically grant private child Partner Agency Property, Profile, Owner, or Ownership access.
- Reuse the existing AF001–AF003 frontend foundation.
- Preserve Arabic-first and RTL-first conventions.
- Do not create a parallel design system.

## Current project status

- BF013, BF014, and BF015 are **IMPLEMENTED AND VERIFIED / CLOSED**.
- AF001, AF002, and AF003 are **IMPLEMENTED AND VERIFIED / CLOSED**.
- AF004 — Property Administration UI — is **ARCHITECTURE APPROVED / NOT IMPLEMENTED**.
- AF004.1 is approved.
- AF004.2 is the next implementation unit.

Follow the current canonical documentation when a unit-specific contract adds more specific instructions. Do not add implementation-specific rules that conflict with those documents.