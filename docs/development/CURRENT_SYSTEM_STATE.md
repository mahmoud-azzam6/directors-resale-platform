# Current System State

Status: BF008 implemented and documented

## Completed Implementation

BF001, BF002, BF002.1, BF002.2, BF003, BF004, BF005, BF006, BF007, and BF008 are implemented.
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

BF008 - Partner Agency Management is implemented:

- Partner Agency records reuse `organizations` with `organization_type = partner_agency`.
- Partner Agency CRUD API routes enforce a direct Franchise parent through `parent_organization_id`.
- Partner Agency endpoints exclude System Organization, Franchise, and inactive Partner Agency records.
- DELETE archives by setting `status` to `inactive`; the row is not physically deleted.
- No Partner Agency table or BF008 migration was required.

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

`organizations` is the only application business table. Its intentional BF006-BF008 staged schema
contains `id`, `parent_organization_id`, `name`, `code`, `organization_type`, `status`, `created_at`,
and `updated_at`. `parent_organization_id` is indexed and self-references `organizations.id`.

## Not Implemented

- Users, authentication, and authorization
- CRM, Property, Listings, Deals, Commissions, and Transfers
- Notifications, AI, Analytics, Integrations, and Frontend
- Automated test suite

## Known Discrepancies

- The global no-hard-delete policy conflicts with the current Organization physical DELETE behavior.
- The broader canonical Organization schema exceeds the intentional BF006-BF007 staged schema.
- ULID and audit standards are not present in the BF006-BF007 staged schema.
- Automated Organization tests do not exist.
- Authentication and authorization infrastructure is not available; BF008 retains the documented integration dependency without implementing a parallel security system.

## Next Development Target

No next milestone is selected. Selection and design of BF009 require a separate project decision.
