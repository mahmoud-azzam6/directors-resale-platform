# Current System State

Status: BF007 completed and documented

## Completed Implementation

BF001, BF002, BF002.1, BF002.2, BF003, BF004, BF005, BF006, and BF007 are implemented.
BF004 has no separate Git commit, but its service-container functionality is present in code.

BF006 - Organization Module is completed and verified:

- Organization CRUD is implemented.
- Organization API routes are implemented.
- The Organization routing Request-injection fix is committed as `a017c94`.
- The `organizations` table was restored and verified.
- BF006 smoke tests passed; no automated test suite exists.

BF007 - Franchise Management is completed and verified:

- Franchise records reuse the `organizations` table with `organization_type = franchise`.
- Franchise CRUD API routes are implemented.
- Franchise records require a System Organization parent through `parent_organization_id`.
- Franchise DELETE archives by setting `status` to `inactive`; active Franchise endpoints do not expose archived records.
- BF007 manual acceptance and BF006 Organization regression checks passed; no automated test suite exists.

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

`organizations` is the only application business table. Its intentional BF006-BF007 staged schema
contains `id`, `parent_organization_id`, `name`, `code`, `organization_type`, `status`, `created_at`,
and `updated_at`. `parent_organization_id` is indexed and self-references `organizations.id`.

## Not Implemented

- Users module
- Partner Agency module
- Authentication and authorization modules
- Automated test suite

## Known Discrepancies

- The global no-hard-delete policy conflicts with the current Organization physical DELETE behavior.
- The broader canonical Organization schema exceeds the intentional BF006-BF007 staged schema.
- ULID and audit standards are not present in the BF006-BF007 staged schema.
- Automated Organization tests do not exist.
- Authentication and authorization infrastructure is not available; BF007 retains the documented integration dependency without implementing a parallel security system.

## Next Development Target

BF007 is completed. Next development milestone requires explicit project decision.
