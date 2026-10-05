# Current System State

Status: BF016 IN PROGRESS / OPEN; BF016.2/BF016.3/BF016.4 IMPLEMENTED AND VERIFIED; BF013/BF014/BF015/AF001-AF004 remain CLOSED

## Completed Implementation

BF001, BF002, BF002.1, BF002.2, BF003, BF004, BF005, BF006, BF007, BF008, BF009, BF010, BF011, BF012, and BF013 are implemented.
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

BF013 - Property & Ownership Foundation is implemented and verified:

- Migrations 009-016 add the Organization Property shell, Organization-scoped Owners, Ownerships and Parties, historical Acting Owner designations, System-only Global Physical Property Identities, historical links, and Property/Owner lifecycle history.
- Migration 017 expands the active Permission catalog from 22 to 30 with Property, Owner, Ownership, and Global Physical Identity view/manage capabilities.
- Services and repositories enforce lifecycle, privacy, share, current-record, historical preservation, and transaction rules; the reusable ULID contract uses Symfony UID 5.4 and QueryBuilder supports `FOR UPDATE` row locking.
- REST APIs use hierarchy scope for Organization Properties, `private_organization` scope for Owners and Ownerships, and `system_only` scope for Global Physical Identities.
- Organization candidates remain untrusted before authorization and are promoted to `auth.target.organization_id` only after successful authorization; unsupported generic target resource types fail explicitly.
- Real MariaDB 10.4.32 acceptance, HTTP/auth/domain regressions, rollback checks, legacy backend smoke, and isolated database cleanup passed.

AF001 - Admin UI Foundation is implemented:

- Added a Next.js App Router, TypeScript, Tailwind, TanStack Query, React Hook Form, Zod, and Lucide frontend under `frontend/`.
- Added centralized replaceable design tokens, source-owned UI primitives, protected Admin layout, responsive sidebar, header, dashboard shell, loading/error/403 states, and six polished placeholder routes.
- Added server-side HttpOnly cookie bridge for BF010 login/logout and `/auth/context`; raw tokens are not exposed to browser JavaScript or Web Storage.
- Added permission-aware navigation from backend context codes and authenticated server-aware Admin protection.
- Broader CRUD screens, Admin UI expansion, and final mobile product design remain deferred.

AF002 - Network Administration UI is implemented:

- `/admin/franchises` provides a permission-aware Franchise list, name/code search, and complete loading, error, empty, and restricted states.
- Authorized System Users can atomically create a Franchise, Organization-owned Position, catalog Permission assignments, and inactive initial administrator.
- Initial administrator credentials remain deferred; onboarding generates or exposes no password or token.
- `/admin/franchises/[id]` provides Franchise identity, hierarchy, lifecycle management, and authorized Partner Agency overview.
- Franchise deactivation preserves the Organization row through existing status-based archival.
- Database-backed onboarding, hierarchy, duplicate, rollback, overview, archive, and cleanup checks passed.

AF003 - Organization User Administration is completed:

- `/admin/users` provides scoped User listing, search, Organization and Position context, supported creation/editing, Position assignment, and status-based deactivation.
- `/admin/positions` provides scoped Position listing, search, Organization context, supported creation/editing, deactivation, catalog display, and Permission assignment.
- System support uses existing platform-wide scope; Franchise scope remains own plus direct child Organizations and Partner Agency scope remains own-only.
- API Permission delegation is limited to the actor's own effective capabilities, in addition to existing authentication, `permissions.assign`, and target-scope checks.
- User Organization ownership remains immutable and cross-Organization Position assignment remains rejected.
- PHP, TypeScript, lint, production build, database scope/integrity/delegation/status checks, cleanup, and manual browser QA passed. The AF003 branch was committed and pushed.

Listing Domain Architecture is approved but not implemented:

- `docs/architecture/LISTING_DOMAIN_ARCHITECTURE.md` is the canonical domain source.
- `docs/architecture/LISTING_PHYSICAL_DATA_MODEL.md` is the approved, not-implemented conceptual physical data-model source.
- ADR-002 records Property / Ownership / Listing separation.
- Residential Resale is the MVP scope with future property-category extensibility.
- Global authenticated marketplace visibility remains separate from administrative authority.
- Lifecycle, provenance, assignment, approvals, holds, sold, withdrawal, Owner privacy, media/document separation, and future integration boundaries are approved conceptually.
- The physical model distinguishes System-only Global Physical Property Identity from independent Organization Property records and defines conceptual Ownership, version, media, Sale, and transfer boundaries.
- BF013 implemented the approved Property & Ownership backend foundation. Listing workflows, rich Property Profile behavior, and frontend Property workflows remain excluded.
- `docs/architecture/PROPERTY_PROFILE_ARCHITECTURE.md` defines the approved, not-implemented Rich Property Profile, derived completeness, progressive persistence, Property Media, and future Add Unit workflow.
- `docs/architecture/PROPERTY_CATALOG_ARCHITECTURE.md` defines approved canonical Property catalogs, versioned Unit Type configuration, and Organization-private proposal governance. BF014.1 implements the catalog/configuration schema; BF014.2 implements six repositories/read models; and BF014.3 implements PropertyCatalogService, UnitTypeConfigurationService, GeographicLocationService, and DevelopmentCatalogService. Catalog HTTP/API and proposals remain unimplemented.
- `docs/database/SEED_DATA.md` defines an approved versioned, idempotent, non-destructive baseline seed strategy; no catalog seed scripts exist.
- Add Unit is frontend/business terminology only; the backend concept remains Organization Property. Property completeness is separate from Listing readiness and never creates a Listing.

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

The repository schema extends through migration 031 and was verified on isolated MariaDB 10.4.32; this is not a claim that migrations 018-031 were applied to the normal XAMPP database. The intentional BF006-BF008 staged `organizations` schema
contains `id`, `parent_organization_id`, `name`, `code`, `organization_type`, `status`, `created_at`,
and `updated_at`; `parent_organization_id` is indexed and self-references `organizations.id`.
The BF009 `users` schema contains the staged User fields plus nullable BF010 `password_hash` and BF011 `position_id`, referencing `organizations.id` and `positions.id`.
The BF010 `auth_tokens` table references `users.id` and stores only token hashes with expiry and revocation state.
The BF011 `positions` table references `organizations.id` and uses composite Organization/code uniqueness.
The BF012 `permissions` and `position_permissions` tables provide the controlled Position-derived Permission model.
BF013 adds `organization_properties`, `owners`, `ownerships`, `ownership_parties`, `authorized_acting_owner_designations`, `global_physical_property_identities`, `global_physical_identity_links`, and `property_owner_lifecycle_history`. The active Permission catalog contains 30 codes after migration 017.

BF014.1 added 13 catalog/configuration/seed-tracking tables through migrations 018-030; BF014.3A added migration 031 for configuration-allocation guards. These changes have zero baseline rows and no new Permission capabilities. See the [BF014.1 implementation record](../sprints/BF014.1-database-schema-and-integrity-constraints.md) for exact scope and verification.

## Not Implemented

- Direct User permissions, authorization expansion beyond BF012, roles, position hierarchy, team hierarchy, user profiles, transfers, registration, reset, refresh tokens, and sessions
- CRM, rich Property Profile persistence/integration, Property values/completeness/media/private documents, Catalog Proposals, Listings, Deals, Commissions, and Transfers
- Notifications, AI, Analytics, Integrations, and broader Admin UI modules beyond AF004 architecture
- A unified project-wide automated test runner; legacy BF006-BF007 verification remains primarily manual

## Approved Architecture, Not Implemented

The approved Admin Experience Architecture establishes one authenticated Admin Application and combines
Organization, Position, Permissions, and applicable resource scope to determine experience and authority.
System Franchise provisioning and Organization-scoped User/Position administration are implemented through AF002-AF003. Broader Partner Agency provisioning remains future work.

It also distinguishes Global Marketplace Visibility from Administrative Scope. The approved Listing Domain Architecture specifies how authenticated Users may browse marketplace-eligible Listings across the platform and may later submit permitted
cross-Organization Requests, while Listing management, reports, Users, commissions, and internal
operations remain scope-controlled. Listing, Request, Report, Commission, Team, invitation, and provisioning implementations are not present. Exact Listing Permission codes, rich Listing schema and APIs, and Team scope remain deferred.

Rich Property Profile and Property Catalog/Data Governance architecture closure is complete. Approved Property setup derives `INCOMPLETE`, `PENDING_REVIEW`, or `COMPLETE`; active complete records appear in Active Properties, active incomplete/review records appear in Drafts, and archived records appear in neither. Developer, Project, and Phase are optional. Canonical governance belongs to System Admin, while catalog proposals remain Organization-private until audited resolution. Arabic-first/RTL-first Add Unit UI remains future frontend scope.

## Known Discrepancies

- The global no-hard-delete policy conflicts with the current Organization physical DELETE behavior.
- The broader canonical Organization schema exceeds the intentional BF006-BF007 staged schema.
- ULID and audit standards are not present in the BF006-BF007 staged schema.
- Automated Organization tests do not exist.
- Authentication and authorization remain PHP-authoritative; the frontend consumes safe context and does not duplicate security calculations.

## Next Development Target

BF013 — Property & Ownership Foundation, BF014 — Canonical Property Catalog Foundation, BF015 — Property Profile Persistence Bridge, AF001–AF003, and AF004 — Property Administration UI — are **IMPLEMENTED AND VERIFIED / CLOSED**. AF004 delivers the authorized `/admin/properties` flow: Property list and shell creation, BF014/BF015 Property Data, BF013 Owner/Ownership composition, read-only review, and an honest Media/Private Document unavailable state. It creates no media or private-document persistence, frontend completeness truth, Listing, Marketplace, Request, Deal, Commission, transfer, or Listing side effect. Property setup review is not Listing Ready. BF016 is IN PROGRESS / OPEN; BF016.2, BF016.3 and BF016.4 are IMPLEMENTED AND VERIFIED, and no next internal unit is selected.

## BF016 — Organization and Property Setup Data Foundation

**BF016.2 — Franchise Basic Profile Implementation and BF016.3 — Property Administrative Details: IMPLEMENTED AND VERIFIED**, recorded 2026-10-05. The BF016 parent is **IN PROGRESS / OPEN** until BF016.5–BF016.6 are completed. No next internal unit is selected by this handoff. BF013, BF014, BF015 and AF004 remain closed.

Focused real-MariaDB acceptance and Operational Franchise Admin Activation passed. All 51 required BF016/BF013/BF014/BF015 test/regression entry points passed, including integrated suites and additional service/concurrency tests; all 147 process cleanup checks passed. PHP syntax and frontend typecheck, lint and build passed. No real development database schema/data was modified.

Organization logo/media, persisted Property images, documents/contracts, galleries/videos, Listing, Marketplace, Requests, Deals, Commissions, completeness and Listing Ready remain deferred. Property setup is not Listing readiness.

Migration synchronization is planned only for `directors_resale_platform` on `127.0.0.1:3306`: migrations 034 then 035 only, in repository order, no canonical seed impact, verified backup and explicit user approval required, all test/acceptance/demo/fixture data excluded, and preflight/post-apply checks required. Migrations 034 and 035 remain unapplied to the real database; no synchronization was executed.

The 14 mojibake sequences are pre-existing in two BF014 fixtures (eight in `BF014RepositoryDatabaseAcceptanceTest.php`, six in `BF014SchemaAcceptanceTest.php`), match `HEAD`, and were not changed by this work. No invalid UTF-8, replacement characters or new mojibake were introduced.

Canonical implementation evidence and synchronization preflight/backup/post-apply requirements: [BF016 implementation record](../sprints/BF016-organization-and-property-setup-data-foundation.md). Implementation and its original handoff were committed and pushed on codex-review in c06aa786; official merge remains human-owner controlled.

### Historical BF016.2/BF016.3 review evidence — 2026-10-05

Company profile now supports existing Organization name, System Franchise selection, private own-Franchise editing, optional street address and protected five-level canonical geography with legitimate skips, safe empty reads and ancestry hydration. Property Data/review now expose an immutable persisted `PROP-<ULID>` code, canonical address, optional positive DECIMAL(18,4) asking price with explicit EGP/USD/SAR/AED currency, and a labelled generic image placeholder without storage. Existing BF015 typed-value patches, atomic revisions and explicit conflict retry are preserved. Authorization remains backend-owned; no permission codes or Listing side effects were added.

Verification: 51/51 PHP matrix entry points passed (two focused BF016 acceptances, Operational Franchise Admin Activation, three BF013, 28 BF014 entry/integrated suites, 12 additional BF014 service/concurrency tests, five BF015). All 147 process cleanup checks passed. PHP syntax passed for 190 files. Frontend typecheck, lint, the BF016 canonical-geography/empty-profile/hydration/error contract acceptance and production build passed. The build completed final trace collection in an identical isolated source copy with shared installed dependencies because the running development server held the original `.next` trace; the development server was left running. Strict UTF-8 and diff checks passed. The 14 pre-existing mojibake sequences in the two BF014 fixtures match HEAD and were not changed; no new mojibake or replacement characters were introduced.

MariaDB remained available at 127.0.0.1:3306; configuration targeted exactly directors_resale_platform. Read-only before/after schema, row-count and data fingerprints matched for all 30 real tables. All newly created disposable databases were removed; the pre-existing directors_resale_platform_e1_test_9740 remained untouched. Repository migrations 001–035 are verified by exact ordered filename equality. Migrations 034 and 035 remain unapplied to the real database. Future synchronization requires a reviewed preflight, verified backup, explicit user approval, 034 then 035 only, no canonical seed impact, exclusion of all test/acceptance/demo/fixture data, and post-apply schema/index/constraint, application and unchanged-business-data checks. No real schema/data operation was performed.

BF016.2 and BF016.3 are IMPLEMENTED AND VERIFIED / READY for review on codex-review. BF016 parent remains OPEN; BF016.4–BF016.6 remain OPEN / PENDING and no next unit is selected. Full media/logo/image persistence, galleries/videos, private documents/contracts, Listing, Marketplace, Requests, Deals, Commissions, completeness and Listing Ready remain deferred. Official-branch merge remains controlled by the human owner.

## BF016.4 — Property Administrative Details Verification and Formal Acceptance

**IMPLEMENTED AND VERIFIED / READY**, 2026-10-05. User-authorized reconciliation keeps BF016.3 as implementation (commit c06aa786) and BF016.4 as verification, proven-gap fixing, regression evidence and formal acceptance. No gap was proven; no application code, tests, fields, permissions or migrations were changed or duplicated.

All 51 PHP matrix entries passed: BF016.2/BF016.3 focused acceptances, Operational Franchise Admin Activation, all BF013/BF014/BF015 entry points and integrated suites, and additional BF014 service/concurrency tests. PHP syntax passed for 190 files. Frontend typecheck, lint, existing contract acceptance and production build including final trace passed; the isolated build copy matched all 118 source hashes. Strict UTF-8, mojibake/replacement scans and diff checks passed; the 14 pre-existing BF014 fixture sequences remain unchanged.

All 146 process cleanup checks passed with no new disposable database residue. All 30 real-table schema/data/row-count fingerprints matched before/after. MariaDB 10.4.32 was available at 127.0.0.1:3306, with .env targeting exactly directors_resale_platform. No real schema/data changes or fixture imports occurred; migrations 034/035 remain unapplied there. The pre-existing e1_test_9740 database remained untouched. Verified backup, preflight, explicit approval and post-apply checks remain mandatory for future synchronization.

BF016.5 remains OPEN for the minimum one-primary-property-image foundation; the generic UI placeholder is not a persisted image. BF016.6 remains OPEN for final closure; BF016 parent remains OPEN. Media, galleries/video, Franchise logo, documents, Listing, Marketplace, Requests, Deals, Commissions, completeness and Listing Ready remain deferred. No next unit is selected. [Exact verification and acceptance evidence](../sprints/BF016-organization-and-property-setup-data-foundation.md#bf0164--verification-evidence).
