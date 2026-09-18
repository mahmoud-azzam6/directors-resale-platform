# Sprint Log

---

# Sprint 0

## Name

Enterprise Foundation

---

## Status

Completed ✅

---

## Duration

Architecture Phase

---

## Objective

Design the complete enterprise architecture before implementation.

---

## Completed

- Repository Structure
- Documentation Structure
- Product Vision
- Business Rules
- Edge Cases
- AI Context
- Architecture Documentation
- Database Architecture
- Database Standards
- Reference Data Module
- Master Index
- ADR Foundation
- GitHub Repository
- Git Workflow
- Documentation Freeze v1.0

---

## Deliverables

- Enterprise Repository
- Documentation Foundation
- Architecture Decisions
- Database Standards
- Development Workflow

---

## Lessons Learned

- Single Source of Truth is mandatory.
- Architecture decisions should always be documented using ADRs.
- Documentation should be completed before implementation.
- Every module should have clear ownership and responsibilities.

---

## Result

Sprint completed successfully.

The project is now ready for implementation.

---

# Sprint 1

> Historical roadmap entry: this sequence predates the completed BF001-BF007 implementation track.
> It is preserved for history and does not describe the current sprint or next selected milestone.

## Name

Database Foundation

---

## Status

Planned / sequencing requires explicit project decision

---

## Objective

Build the complete Reference Data foundation.

---

## Planned Deliverables

- Database Contract Template
- DB001 - Countries
- DB002 - Languages
- DB003 - Currencies
- DB004 - Currency Rates
- DB005 - States
- DB006 - Cities
- DB007 - Districts
- DB008 - Postal Codes

---

## Historical Current Task

No active implementation task was selected when this roadmap entry was written.

---

## Historical Next Task

At that time, the next development milestone required an explicit project decision.

---

## Notes

Reference Data has not been implemented. This sprint remains planned.

---

# Backend Implementation Milestones

## BF001-BF005 - Foundation

Status: Completed

The completed foundation includes bootstrap, the service container, database abstractions,
query builder, base repository, router, standardized responses, exception handling, and
the HTTP Kernel/Request abstraction.

## BF006 - Organization Module

Status: Completed

- Organization CRUD and API routes implemented.
- The `organizations` table was restored and verified using the intentional seven-field BF006 schema.
- Routing Request injection was fixed in commit `a017c94`.
- BF006 smoke tests passed; no automated test suite exists.

## BF007 - Franchise Management

Status: Completed

- Implemented Franchise management using Organization-backed records; no `franchises` table was created.
- Added `parent_organization_id` through a staged self-referencing migration.
- Added Franchise CRUD API routes and System Organization parent validation.
- Implemented status-based archival (`inactive`) for Franchise DELETE.
- Verified BF007 manual acceptance and BF006 Organization regression checks; no automated test suite exists.
- Deferred ULID, extended audit, authentication, and authorization infrastructure according to the staged scope.

## BF008 - Partner Agency Management

Status: Implemented on 2026-08-16

- Added Organization-backed Partner Agency model, repository, validator, service, controller, and CRUD routes.
- Enforced an existing Franchise as the required direct parent and isolated endpoints to active Partner Agencies.
- Reused `organizations` and `parent_organization_id`; no separate table or migration was required.
- Implemented status-based archival with `status = inactive` and preserved the database row.
- Preserved the deferred ULID, audit, authentication, and authorization scope.
- PHP syntax and autoload verification passed; no automated test framework exists, and database-backed checks were unavailable because MySQL was not running.
- No subsequent milestone was selected.

## BF009 - User Management

Status: Implemented on 2026-08-19

- Added the staged User model, repository, validator, service, controller, DI bindings, and CRUD routes.
- Added `database/migrations/003_create_users_table.sql` with Organization foreign key/index and global unique email.
- Enforced active supported Organization ownership and rejected normal User Organization changes.
- Implemented DELETE as status-based deactivation while preserving User rows and active endpoint visibility rules.
- BF009 database-backed acceptance tests and BF006-BF008 regression endpoint checks passed.
- Temporary verification data was removed; no automated test framework exists.
- Authentication, Authorization, Positions, Permissions, User Profiles, and Transfers remain deferred.
- No subsequent milestone was selected.

## BF010 - Authentication Foundation

Status: Implemented on 2026-08-19

- Added `password_hash` credentials and the `auth_tokens` migration with User ownership, hashed token storage, expiry, and revocation.
- Added login, logout, current User, authentication service, controller, and reusable bearer middleware.
- Protected existing Organization, Franchise, Partner Agency, and User business routes without changing their business rules.
- Added the narrow CLI-only initial password setup mechanism; no public registration, password reset, refresh tokens, or browser sessions.
- BF010 database-backed login/token/logout/me acceptance tests and authenticated BF006-BF009 regression checks passed.
- Temporary verification data was removed; no automated test framework exists.
- Authentication is identity-only; Authorization, Roles, Permissions, Positions, User Profiles, Transfers, and BF011 remain deferred.
- No subsequent milestone was selected.

## BF011 - Dynamic Positions

Status: Implemented on 2026-08-19

- Added the Position model, repository, validator, service, controller, DI bindings, and authenticated CRUD routes.
- Added `positions` and nullable `users.position_id` migrations with Organization/Position foreign keys and required indexes.
- Enforced Organization-scoped Position code uniqueness, active supported Organization ownership, and immutable Position ownership.
- Added User Position assignment, reassignment, clearing, same-Organization enforcement, and inactive-Position rejection.
- Position deactivation preserves rows and existing User relationships.
- BF011 database-backed acceptance tests and authenticated BF006-BF010 regression checks passed.
- Temporary verification data was removed; no automated test framework exists.
- Authorization, Permissions, Roles, Position hierarchy, Team hierarchy, User Profiles, Transfers, and BF012 remain deferred.
- No subsequent milestone was selected.

## BF012 - Permissions & Authorization

Status: Implemented on 2026-08-19

- Added controlled Permission catalog seed and Position-Permission persistence with composite uniqueness.
- Added Permission catalog and Position-Permission APIs with atomic replacement and explicit CLI bootstrap.
- Added reusable Authorization and Organization Scope services over BF010 authenticated User context.
- Enforced 401 versus 403 semantics, System/Franchise/Partner Agency scope, resource checks, scoped creates, and list isolation across BF006-BF011 APIs.
- Preserved BF009 Organization-transfer protection and BF011 same-Organization User/Position integrity.
- BF012 database-backed two-branch scope testing and authenticated BF006-BF011 regression checks passed.
- Temporary verification data was removed; no automated test framework exists.
- Direct User permissions, hard-coded roles, Position/Team hierarchy, Admin UI, audit engine, and future modules remain deferred.
- No subsequent Backend sprint was selected.

## AF001 - Admin UI Foundation

Status: Implemented on 2026-08-20

- Added the `frontend/` Next.js App Router foundation with TypeScript, Tailwind, TanStack Query, React Hook Form, Zod, and Lucide.
- Added centralized Directors Gold/Charcoal-informed design tokens and source-owned reusable UI primitives.
- Added real Login, server-side HttpOnly cookie bridge, logout, safe `/auth/context`, protected Admin layout, responsive Sidebar/Header, and context-only Dashboard.
- Added permission-aware navigation and polished placeholders for Organizations, Franchises, Partner Agencies, Users, Positions, and Permissions.
- Added reusable loading, generic error, 403, empty, and not-found states without implementing full CRUD.
- Frontend typecheck, build, lint, HTTP login/context/logout integration, permission-aware navigation, forbidden state, expired-session handling, and PHP syntax checks passed.
- Raw tokens were absent from Web Storage and not returned to frontend JavaScript; temporary backend verification data was removed.
- AF002 and AF003 remain deferred; no subsequent frontend phase was selected.

## AF002 - Network Administration UI

Status: Implemented on 2026-08-20

- Replaced the Franchise placeholder with permission-aware list, search, detail, lifecycle, and Partner Agency hierarchy views.
- Added atomic onboarding that reuses Franchise, Position, Permission assignment, and User services.
- Provisioned the initial administrator as inactive with an Organization-owned Position and selected catalog Permissions; credentials and invitations remain deferred.
- Preserved backend authorization, Organization scope, hierarchy validation, and status-based Franchise archival.
- Verified TypeScript, lint, PHP syntax, database success/validation/duplicate/hierarchy/rollback/archive behavior, and temporary-data cleanup.
- AF003 and later Organization administration, transfer, Listing, reporting, and notification work remain deferred; no next milestone was selected.

## AF003 - Organization User Administration

Status: Implemented on 2026-08-23; manual browser QA pending

- Replaced Users and Positions placeholders with Organization-aware operational administration screens.
- Reused BF009-BF012 scope, CRUD, lifecycle, Position ownership, Permission catalog, and authorization behavior.
- Added scope-filtered Organization context routes for User and Position administration.
- Restricted API Permission delegation to the authenticated actor's own effective capabilities while retaining target scope and `permissions.assign` enforcement.
- Verified System, Franchise, Partner Agency, sibling rejection, cross-Organization Position rejection, User transfer rejection, lifecycle preservation, PHP, TypeScript, lint, build, and cleanup.
- Manual browser QA remains required before sprint closure. AF004 and future transfer, Listing, reporting, Team, and Partner Agency provisioning work remain deferred.

## Listing Domain Architecture

Status: Approved and documented on 2026-08-31; not implemented

- Established `docs/architecture/LISTING_DOMAIN_ARCHITECTURE.md` as the canonical Listing-domain source.
- Approved Residential Resale MVP scope with future category extensibility, Property/Ownership/Listing separation, provenance and assignment, lifecycle, approvals, hold/sold/withdrawal behavior, marketplace eligibility, Owner privacy, and future integration boundaries.
- Added ADR-002 and concise canonical Listing business rules and edge cases.
- No physical schema, migration, API, frontend, exact Listing Permission codes, or implementation sprint was created or selected.

## Physical Listing Data Model Architecture

Status: Approved and documented on 2026-09-01; not implemented

- Established `docs/architecture/LISTING_PHYSICAL_DATA_MODEL.md` as the canonical conceptual physical-model source.
- Approved the System-only Global Physical Property Identity and independent Organization Property model, Ownership/Owner history, material versions, media/document separation, Sale/Ownership Transfer boundary, and logical constraints.
- No SQL schema, migration, application code, API, frontend, test, or implementation sprint was created or selected.

## BF013 - Property & Ownership Foundation

Status: Implemented and verified; closed on 2026-09-06

- Established `docs/sprints/BF013-property-ownership-foundation.md` as the authoritative implementation contract.
- Scoped the foundation to Organization Property, Organization Owner, Ownership Parties, historical Acting Owner designations, and System-only Global Physical Identity link history.
- Explicitly excluded Listing workflows, rich Property Profile, catalogs, media/documents, Sale/Transfer, matching algorithms, and frontend implementation.
- Replaced the obsolete tentative LF sprint-family naming with the approved BF backend/domain convention.
- Completed 2A schema/migrations, 2B QueryBuilder row locking, 2C repositories, 2D.0 reusable ULID infrastructure, 2D.1 Property/Owner services, 2D.2 Ownership aggregate services, 2D.3 Global Identity services, 2E.1 permissions/authorization, 2E.2 HTTP integration, and 2E.3 database-backed acceptance/regression.
- Added migrations 009-017, eight domain tables, eight BF013 Permission codes, and scoped REST APIs without implementing Listing or frontend workflows.
- Verified migrations 001-017 on MariaDB 10.4.32, database constraints and nullable uniqueness guards, lifecycle/history preservation, transactional rollback, HTTP and authorization contracts, privacy boundaries, legacy backend compatibility, regressions, and cleanup with `remaining_bf013_test_databases=0`.
- No subsequent sprint was selected.

## Rich Property Profile and Property Catalog Architecture

Status: Approved and documented on 2026-09-09; not implemented

- Commit `97e2a38` created `docs/architecture/PROPERTY_PROFILE_ARCHITECTURE.md` and `docs/architecture/PROPERTY_CATALOG_ARCHITECTURE.md` as the canonical Rich Property Profile and Property Catalog/Data Governance sources.
- Reconciled the Listing Physical Data Model with derived completeness, versioned Unit Type configuration, typed Measurements/Attributes, Organization-private proposals, Property Media processing, and conceptual integrity rules.
- Established `docs/database/SEED_DATA.md` as the approved versioned, idempotent, non-destructive baseline seed strategy.
- Performed documentation architecture only: no schema, migrations, seeds, backend, frontend, or tests were implemented, and no subsequent sprint was selected.

## BF014 - Canonical Property Catalog Foundation

Current status: IN PROGRESS

Selection/documentation status on 2026-09-13: SELECTED / DOCUMENTED / NOT IMPLEMENTED

- Contract: [BF014 canonical property catalog foundation](../sprints/BF014-canonical-property-catalog-foundation.md).
- BF013 remains IMPLEMENTED AND VERIFIED / CLOSED. Property Catalog is APPROVED / PARTIALLY IMPLEMENTED through BF014.1 schema and BF014.2 repositories/read models; Rich Property Profile and Listing remain APPROVED / NOT IMPLEMENTED.
- Locked six Categories, 20 Unit Types, six Measurement Definitions, 15 Attribute Definitions, nine options, 20 initial V1 configurations and their rule matrices, Egypt plus exactly 27 Governorates, and zero Developer/Project/Phase seeds.
- Documented System-only catalog management, authorized reads, dynamic property-form configuration, seven versioned seed packages, provenance, and seed rerun safety.
- Proposed BF014.1-BF014.8 implementation units; no code, migrations, seed execution, tests, APIs, or frontend implemented by this task. No BF015 or subsequent sprint selected.
- Earlier entries saying no subsequent sprint was selected describe their historical closure dates.

### BF014.1 - Database Schema + Integrity Constraints (internal BF014 unit)

Status: IMPLEMENTED AND VERIFIED / CLOSED

Implementation commit: `4008a76` - feat: add BF014 canonical property catalog schema

- Canonical [implementation record](../sprints/BF014.1-database-schema-and-integrity-constraints.md); the parent BF014 contract remains authoritative for scope, matrices, non-goals, and Definition of Done.
- Migrations 018-030 added exactly 13 tables; verified on isolated real MariaDB 10.4.32 with 60 CHECKs, 36 restricted FKs, 25 unique indexes, and two PERSISTENT guards. All new tables started empty; Permission count remained 30.
- BF013 database acceptance and eight existing non-database regression scripts passed; disposable database cleanup verified.
- At BF014.1 closure, the next internal unit was BF014.2 - Repositories + Domain Read Models - NEXT / NOT STARTED; BF014.3-BF014.8 were NOT STARTED.
- BF014 remains IN PROGRESS, not completed. No BF015 or later sprint is selected. No implementation was added by this documentation closure.

### BF014.2 - Repositories + Domain Read Models (internal BF014 unit)

Status: IMPLEMENTED AND VERIFIED / CLOSED; documentation closure 2026-09-14

- Canonical [BF014.2 implementation record](../sprints/BF014.2-repositories-and-domain-read-models.md); the parent BF014 Sprint Contract remains authoritative.
- BF014.2A: `ae607cf` - Core Catalog Repository Foundation; BF014.2B: `17bef9b` - Unit Type Configuration Repository; BF014.2C: `7151acd` - Geography + Development Catalog Repositories; BF014.2D: `2669b12` - Real MariaDB Repository Acceptance. BF014.2E records documentation closure here.
- Exactly six repositories, PHPDoc array read models, explicit batch aggregate composition, scoped persistence and caller-owned transactions. Seed ledger persistence remains deferred to BF014.5; Services, APIs, seeds and dynamic forms remain unimplemented.
- MariaDB 10.4.32 acceptance passed against migrations 001-030; 51 + 51 aggregate rules used six queries; two-connection locking and 14 nullable Project filter cases passed. BF013 DB acceptance and all eight non-DB regressions passed; disposable database/server cleanup verified and normal development database untouched.
- Next internal unit: BF014.3 - Catalog / Configuration Services - NEXT / NOT STARTED. BF014.4-BF014.8 remain NOT STARTED; BF015 is NOT SELECTED. BF014 remains IN PROGRESS. Rich Property Profile and Listing remain APPROVED / NOT IMPLEMENTED.
- This closure changes documentation only; no production code, migrations, tests or frontend changes.

### BF014.3C - UnitTypeConfigurationService (internal BF014 unit)

Status: IMPLEMENTED AND VERIFIED / CLOSED; documentation closure 2026-09-16

- Added Service-owned configuration allocation, blank/current-ACTIVE clone DRAFTs, DRAFT-only rule mutations and deletion, activation revalidation, ACTIVE-to-HISTORICAL transition, and Unit Type serialization locking.
- Real MariaDB 10.4.32 checks passed for rollback and four independent-process concurrency cases: creation/creation, mutation/activation, deletion/mutation, and activation/activation.
- BF014 repository/schema, Property Catalog Service, BF013 database, authorization, HTTP, and Property/Ownership regression checks passed. BF014 remains IN PROGRESS; BF014.3D-BF014.3F and BF014.4-BF014.8 remain NOT STARTED.

### BF014.3D - GeographicLocationService and DevelopmentCatalogService (internal BF014 unit)

Status: IMPLEMENTED AND VERIFIED / CLOSED; documentation closure 2026-09-17

- D1/D1.1 implemented Geography hierarchy/lifecycle with iterative validation, canonical Country/root-first locking and post-lock state revalidation. D2 implemented Developer/Project/Phase lifecycle and optional Project references with Developer-before-Project and Project-before-Phase serialization.
- D3 verified all eight race orderings using independent PHP workers/connections on MariaDB 10.4.32, deterministic trigger/named-lock coordination and observed InnoDB wait edges. Domain errors, committed invariants and cleanup passed; no D3 production defect was found.
- D4 passed all 19 requested D/BF014/BF013 scripts. Contract review reproduced a Geography pagination defect with an active 51st child; the iterative scan now visits every child page. Enhanced D1 and both D3a orderings passed after the targeted correction. PHP syntax and whitespace checks passed.
- Canonical evidence: [BF014.3D closure record](../sprints/BF014.3-catalog-and-configuration-services.md#bf0143d-implementation-and-closure-evidence). BF014.3A-BF014.3D are CLOSED; BF014.3E is NEXT / NOT STARTED; BF014.3F and BF014.4-BF014.8 are NOT STARTED. BF014.3 and BF014 remain IN PROGRESS. No later sprint is selected.

### BF014.3E - Real MariaDB Service Acceptance and Regressions (internal BF014 unit)

Status: IMPLEMENTED AND VERIFIED / CLOSED; documentation closure 2026-09-18

- E1, E2 and E3 integrated real MariaDB acceptance passed for Catalog/Configuration, Geography/Development and cross-service boundaries. Evidence covered persisted state, lifecycle/reference/safe-delete rules, transaction rollback and aggregate-family isolation.
- E3.1 corrected only the E1 historical assertion: version-specific configuration and rule rows are immutable, while shared canonical Definition/Option metadata remains mutable under its catalog rules. No production defect or production change occurred.
- E4 passed all E acceptance tests, Property Catalog, C4/C5a-C5d, D1/D2/D3a-D3d, BF014 repository/schema and BF013 database/authorization/HTTP/Service/repository regression scripts. Concurrency checks used real MariaDB independent workers; syntax and whitespace checks passed.
- Canonical evidence: [BF014.3E closure record](../sprints/BF014.3-catalog-and-configuration-services.md#bf0143e-implementation-and-closure-evidence). BF014.3A-BF014.3E are CLOSED; BF014.3F is NEXT / NOT STARTED. BF014.3 and BF014 remain IN PROGRESS. No later sprint is selected.

### BF014.3F - Final Documentation Closure (internal BF014 unit)

- Status: **IMPLEMENTED AND VERIFIED / CLOSED** on 2026-09-19.
- Synchronized the canonical records for BF014.3 completion. This unit changed documentation only; it added no production, test, schema or migration artifact.
- Confirms the A-E service, real MariaDB acceptance, concurrency, rollback and regression evidence. Historical configuration structure remains immutable; shared canonical Definition and Option metadata remains mutable under catalog rules.
- Canonical evidence: [BF014.3F closure record](../sprints/BF014.3-catalog-and-configuration-services.md#bf0143f-documentation-closure-evidence). BF014.3 is CLOSED. BF014 remains IN PROGRESS; BF014.4-BF014.8 are NOT STARTED, no next internal unit is selected, and BF015 remains NOT SELECTED.

For future authorized implementation, completed database contracts produce the applicable artifacts; this documentation closure creates none:


- SQL Migration
- Seeder
- Repository
- Validation Rules
- API Contract
