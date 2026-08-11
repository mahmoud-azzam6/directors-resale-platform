# Current System State

Status: Documentation synchronized after BF006

## Completed Implementation

BF001, BF002, BF002.1, BF002.2, BF003, BF004, BF005, and BF006 are implemented.
BF004 has no separate Git commit, but its service-container functionality is present in code.

BF006 - Organization Module is completed and verified:

- Organization CRUD is implemented.
- Organization API routes are implemented.
- The Organization routing Request-injection fix is committed as `a017c94`.
- The `organizations` table was restored and verified.
- BF006 smoke tests passed; no automated test suite exists.

## Implemented Request Path

```text
public/index.php
  -> HTTP Kernel / Request
  -> Router
  -> Organization Controller
  -> Organization Service
  -> Organization Repository
  -> Query Builder / PDO
  -> MySQL
```

## Current Database State

`organizations` is the only application business table. Its intentional BF006 staged schema
contains only `id`, `name`, `code`, `organization_type`, `status`, `created_at`, and `updated_at`.

## Not Implemented

- Franchise module
- Users module
- Partner Company module
- Authentication and authorization modules
- Automated test suite

## Known Discrepancies

- The global no-hard-delete policy conflicts with the current Organization physical DELETE behavior.
- The broader canonical Organization schema exceeds the intentional BF006 staged schema.
- ULID and audit standards are not present in the BF006 staged schema.
- Automated Organization tests do not exist.

## Next Development Target

Next development milestone requires explicit project decision. The untracked
`docs/sprints/BF007-franchise-management.md` file is a DRAFT / NOT APPROVED.
