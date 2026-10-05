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

## Environment Schema Synchronization Agent

After an approved implementation unit is merged or explicitly approved for environment synchronization, the Environment Schema Synchronization Agent must:

1. Inspect the repository migrations and canonical seed packages required by the approved unit.
2. Compare the required schema and canonical reference data with the configured real development database.
3. Clearly distinguish schema migrations, canonical/system seed data, test fixtures, acceptance-test data, and local demo/business data.
4. Never copy test fixtures, acceptance-test records, test users, test properties, test owners, test tokens, or other temporary test data into the real development database.
5. Never apply a migration or data change to the real database without confirming the configured database name, identifying the exact target database, producing a preflight report, taking a verified backup, listing the exact migrations and canonical seed packages to be applied, and receiving explicit approval for the database synchronization step.
6. Apply only the approved missing migrations and official canonical seed packages, in repository-defined order.
7. Do not rerun already-applied migrations unless the user explicitly approves a recovery procedure.
8. Stop on schema conflicts, seed collisions, unexpected existing data, failed backup, or any uncertainty.
9. Remember that MariaDB DDL may implicitly commit; do not claim transaction rollback can undo completed DDL.
10. Verify expected tables and columns, indexes and constraints, canonical permissions/reference data, application boot, and relevant authenticated endpoint behavior after synchronization.
11. Produce a before/after report containing the database name; backup path and verification; migrations and canonical seed packages applied; records intentionally excluded; verification results; and remaining differences.
12. Keep application tests isolated. Real-database synchronization must never import test-generated business records.

Required workflow:

```text
Implementation
→ Review
→ Approval
→ Schema/Data Sync Plan
→ Verified Backup
→ Explicit User Approval
→ Apply to Real Development Database
→ Verify
→ Report
```

Synchronization is never automatic because tests passed. It always requires explicit approval for the database operation.

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
- AF004 — Property Administration UI — is **IMPLEMENTED AND VERIFIED / CLOSED**.
- AF004.1 through AF004.6 are **IMPLEMENTED AND VERIFIED / CLOSED**.
- No current sprint or next internal unit is selected.

Follow the current canonical documentation when a unit-specific contract adds more specific instructions. Do not add implementation-specific rules that conflict with those documents.
