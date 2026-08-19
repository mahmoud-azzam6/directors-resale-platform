# Current System State

Status: BF011 implemented and documented

## Completed Implementation

BF001, BF002, BF002.1, BF002.2, BF003, BF004, BF005, BF006, BF007, BF008, BF009, BF010, and BF011 are implemented.
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

BF009 - User Management is implemented:

- Users belong to exactly one active System, Franchise, or Partner Agency Organization.
- User email is globally unique and `organization_id` is immutable through normal updates.
- User DELETE deactivates with `status = inactive`; the database row remains present.
- Authentication, Authorization, Positions, Permissions, User Profiles, and Transfers are deferred.

BF010 - Authentication Foundation is implemented:

- Existing Users may authenticate with securely hashed passwords established through the narrow CLI development mechanism.
- Login issues cryptographically random bearer tokens while persisting only SHA-256 token hashes with expiry and revocation lifecycle fields.
- `/auth/login` and health remain public; `/auth/logout`, `/auth/me`, and existing business routes require authentication.
- Authentication resolves the active User only; Authorization, Roles, Permissions, Positions, Profiles, Transfers, reset, registration, refresh tokens, and sessions are deferred.

BF011 - Dynamic Positions is implemented:

- Positions belong to one Organization and use Organization-scoped code uniqueness.
- Users have nullable `position_id` with active same-Organization assignment, reassignment, and clearing.
- Position deactivation preserves the database row and existing User `position_id` relationships.
- Position endpoints require BF010 authentication; Authorization, Roles, Permissions, Position hierarchy, Team hierarchy, and BF012 remain deferred.

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

The application business tables are `organizations` and `users`. The intentional BF006-BF008 staged `organizations` schema
contains `id`, `parent_organization_id`, `name`, `code`, `organization_type`, `status`, `created_at`,
and `updated_at`; `parent_organization_id` is indexed and self-references `organizations.id`.
The BF009 `users` schema contains the staged User fields plus nullable BF010 `password_hash` and BF011 `position_id`, referencing `organizations.id` and `positions.id`.
The BF010 `auth_tokens` table references `users.id` and stores only token hashes with expiry and revocation state.
The BF011 `positions` table references `organizations.id` and uses composite Organization/code uniqueness.

## Not Implemented

- Authorization, roles, permissions, position hierarchy, team hierarchy, user profiles, transfers, registration, reset, refresh tokens, and sessions
- CRM, Property, Listings, Deals, Commissions, and Transfers
- Notifications, AI, Analytics, Integrations, and Frontend
- Automated test suite

## Known Discrepancies

- The global no-hard-delete policy conflicts with the current Organization physical DELETE behavior.
- The broader canonical Organization schema exceeds the intentional BF006-BF007 staged schema.
- ULID and audit standards are not present in the BF006-BF007 staged schema.
- Automated Organization tests do not exist.
- Authentication is implemented as an identity layer; authorization remains deferred.

## Next Development Target

No next milestone is selected. BF012 is not selected or designed.
