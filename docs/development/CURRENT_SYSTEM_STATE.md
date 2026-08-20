# Current System State

Status: AF002 implemented and verified

## Completed Implementation

BF001, BF002, BF002.1, BF002.2, BF003, BF004, BF005, BF006, BF007, BF008, BF009, BF010, BF011, and BF012 are implemented.
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
- At BF009 completion, Authentication, Authorization, Positions, Permissions, User Profiles, and Transfers were deferred.

BF010 - Authentication Foundation is implemented:

- Existing Users may authenticate with securely hashed passwords established through the narrow CLI development mechanism.
- Login issues cryptographically random bearer tokens while persisting only SHA-256 token hashes with expiry and revocation lifecycle fields.
- `/auth/login` and health remain public; `/auth/logout`, `/auth/me`, and existing business routes require authentication.
- BF010 authentication resolves the active User only. At BF010 completion, Authorization, Roles,
  Permissions, Positions, Profiles, Transfers, reset, registration, refresh tokens, and sessions were deferred.

BF011 - Dynamic Positions is implemented:

- Positions belong to one Organization and use Organization-scoped code uniqueness.
- Users have nullable `position_id` with active same-Organization assignment, reassignment, and clearing.
- Position deactivation preserves the database row and existing User `position_id` relationships.
- Position endpoints require BF010 authentication. At BF011 completion, Authorization, Roles,
  Permissions, Position hierarchy, Team hierarchy, and BF012 were deferred.

BF012 - Permissions & Authorization is implemented:

- Permission capabilities are controlled by the seeded catalog and assigned to Positions through `position_permissions`.
- Authorization requires an active User, active same-Organization Position, active Permission, and valid Organization scope.
- Scope resolves as System platform-wide, Franchise plus direct child Partner Agencies, or Partner Agency own-only.
- Existing business lists are filtered at the persistence/service boundary; resource and mutation routes return 403 for out-of-scope targets.
- Missing/invalid Authentication remains 401; authenticated Permission or scope failure is 403.
- Permission assignment uses an explicit CLI bootstrap for initial development and authorized atomic API synchronization thereafter.
- Direct User permissions, Roles, Position/Team hierarchy, Admin UI, and full audit/event history are deferred.

AF001 - Admin UI Foundation is implemented:

- Added a Next.js App Router, TypeScript, Tailwind, TanStack Query, React Hook Form, Zod, and Lucide frontend under `frontend/`.
- Added centralized replaceable design tokens, source-owned UI primitives, protected Admin layout, responsive sidebar, header, dashboard shell, loading/error/403 states, and six polished placeholder routes.
- Added server-side HttpOnly cookie bridge for BF010 login/logout and `/auth/context`; raw tokens are not exposed to browser JavaScript or Web Storage.
- Added permission-aware navigation from backend context codes and authenticated server-aware Admin protection.
- AF003, broader CRUD screens, Admin UI expansion, and final mobile product design remain deferred.

AF002 - Network Administration UI is implemented:

- `/admin/franchises` provides a permission-aware Franchise list, name/code search, and complete loading, error, empty, and restricted states.
- Authorized System Users can atomically create a Franchise, Organization-owned Position, catalog Permission assignments, and inactive initial administrator.
- Initial administrator credentials remain deferred; onboarding generates or exposes no password or token.
- `/admin/franchises/[id]` provides Franchise identity, hierarchy, lifecycle management, and authorized Partner Agency overview.
- Franchise deactivation preserves the Organization row through existing status-based archival.
- Database-backed onboarding, hierarchy, duplicate, rollback, overview, archive, and cleanup checks passed.

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
The BF012 `permissions` and `position_permissions` tables provide the controlled Position-derived Permission model.

## Not Implemented

- Direct User permissions, authorization expansion beyond BF012, roles, position hierarchy, team hierarchy, user profiles, transfers, registration, reset, refresh tokens, and sessions
- CRM, Property, Listings, Deals, Commissions, and Transfers
- Notifications, AI, Analytics, Integrations, broader Admin UI modules, and AF003
- Automated test suite

## Approved Architecture, Not Implemented

The approved Admin Experience Architecture establishes one authenticated Admin Application and combines
Organization, Position, Permissions, and applicable resource scope to determine experience and authority.
It approves System provisioning of Franchises, permitted Franchise provisioning of Partner Agencies,
and Organization-scoped User administration as future workflows.

It also distinguishes Global Marketplace Visibility from Administrative Scope. Future authenticated
Users may browse marketplace-eligible available Listings across the platform and may submit permitted
cross-Organization Requests, while Listing management, reports, Users, commissions, and internal
operations remain scope-controlled. Listing, Request, Report, Commission, Team, invitation, and
provisioning implementations are not present. Exact Listing Permission codes and Team scope remain deferred.

## Known Discrepancies

- The global no-hard-delete policy conflicts with the current Organization physical DELETE behavior.
- The broader canonical Organization schema exceeds the intentional BF006-BF007 staged schema.
- ULID and audit standards are not present in the BF006-BF007 staged schema.
- Automated Organization tests do not exist.
- Authentication and authorization remain PHP-authoritative; the frontend consumes safe context and does not duplicate security calculations.

## Next Development Target

No next milestone is selected. AF002 is implemented and verified. AF003 is not selected or designed.
