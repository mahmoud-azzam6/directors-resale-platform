# Implementation Plan - Current Status

## Completed

| Milestone | Status | Verified State |
|---|---|---|
| BF001 | Completed | Backend bootstrap, container, router, response, exception handling, health endpoint |
| BF002 / BF002.1 / BF002.2 | Completed | Contracts and reusable database foundation |
| BF003 | Completed | Generic BaseRepository |
| BF004 | Completed | Service-container functionality present; no separate Git commit |
| BF005 | Completed | HTTP Kernel and Request abstraction |
| BF006 | Completed | Organization CRUD/API, staged `organizations` table, and routing fix `a017c94` |
| BF007 | Completed | Organization-backed Franchise CRUD/API, `parent_organization_id`, System-parent validation, and status-based archival |
| BF008 | Implemented | Organization-backed Partner Agency CRUD/API, Franchise-parent validation, isolation, and status-based archival |
| BF009 | Implemented | Staged User CRUD/API, active Organization ownership, global unique email, immutable ownership, and status-based deactivation |
| BF010 | Implemented | Secure password credentials, hashed expiring bearer tokens, login/logout/me, reusable authentication protection, and protected business routes |
| BF011 | Implemented | Dynamic Organization-owned Positions, scoped code uniqueness, nullable same-Organization User assignment, and status-based deactivation |
| BF012 | Implemented | Controlled Permission catalog, Position-Permission inheritance, reusable authorization, Organization scope enforcement, and isolated lists |
| BF013 | Completed | Property & Ownership backend foundation implemented, verified, and closed |
| AF001 | Implemented | Next.js Admin UI foundation, secure HttpOnly auth bridge, context, protected layout, permission-aware navigation, dashboard, and placeholders |
| AF002 | Implemented | Franchise network list/detail, Partner Agency overview, status-based deactivation, and atomic initial administrator onboarding |
| AF003 | Completed | Organization-scoped User and Position administration with safe Position ownership and capability-subset delegation; browser QA passed and branch committed/pushed |

BF006 smoke tests and BF007 manual acceptance/regression checks passed at their closure. BF014.1 verification also passed the existing standalone regression scripts and real MariaDB acceptance; no unified project-wide test runner exists.

## Current Planning State

BF014 is the current Backend sprint: **IN PROGRESS**. The parent contract remains authoritative for overall scope, baseline matrices, non-goals, and Definition of Done. [BF014.1 implementation record](../sprints/BF014.1-database-schema-and-integrity-constraints.md) records commit `4008a76` and **IMPLEMENTED AND VERIFIED / CLOSED** status. [BF014.2 - Repositories + Domain Read Models](../sprints/BF014.2-repositories-and-domain-read-models.md) is **IMPLEMENTED AND VERIFIED / CLOSED** (commits `ae607cf`, `17bef9b`, `7151acd`, `2669b12`). BF014.3 - Catalog / Configuration Services is **NEXT / NOT STARTED**; BF014.4-BF014.8 are **NOT STARTED**. These are internal BF014 units, not separate sprints. BF013 remains **IMPLEMENTED AND VERIFIED / CLOSED**. Property Catalog architecture is **APPROVED**, partially implemented through BF014.1 schema and BF014.2 repositories/read models. Rich Property Profile and Listing remain **APPROVED / NOT IMPLEMENTED**. No BF015 or later sprint is selected.

The contract records BF014.1 schema/integrity, BF014.2 repositories/read models, BF014.3 services, BF014.4 authorization/HTTP, BF014.5 seed runner/baseline, BF014.6 dynamic form projection, BF014.7 MariaDB acceptance/regressions, and BF014.8 documentation closure. BF014.1 is IMPLEMENTED AND VERIFIED / CLOSED. BF014.2 is IMPLEMENTED AND VERIFIED / CLOSED. BF014.3 is NEXT / NOT STARTED; BF014.4-BF014.8 are NOT STARTED.

## Approved Future Execution Order

BF014 is the selected Backend catalog foundation sprint. Subsequent entries remain conceptual sequencing only, with no later sprint numbers or names selected:

1. Architecture closure - complete
2. BF014 parent sprint contract - recorded and authoritative
3. BF014 - Canonical Property Catalog Foundation - IN PROGRESS; BF014.1 and BF014.2 closed; BF014.3 next, not started
4. Backend Rich Property Profile
5. System Admin Property Data Management frontend
6. Initial canonical data verification and approved business additions
7. Admin Properties frontend: Active, Drafts, Add Unit, and Property Details
8. Listing implementation
9. Requests, Deals, Commissions, Notifications, and Reports

## Constraints for Future Planning

- Preserve the BF006-BF007 staged `organizations` schema unless an approved milestone changes it.
- Resolve the documented no-hard-delete versus Organization physical DELETE discrepancy before
  relying on deletion behavior for future business modules.
- Franchise DELETE is scoped to status-based archival (`inactive`); it does not create a general lifecycle framework.
- Authentication and authorization are implemented through BF010-BF012 and remain backend-authoritative.
- Preserve the approved System Organization -> Franchise -> Partner Agency hierarchy. Users belong to an
  Organization in this hierarchy; do not treat Users as another hierarchy level.
- Preserve the approved distinction between future Global Marketplace Visibility and Administrative
  Scope. Do not use Organization management scope to isolate marketplace-eligible available Listings.
- Use `docs/architecture/LISTING_DOMAIN_ARCHITECTURE.md` as the canonical source for approved Listing boundaries and behavior.
- Use `docs/architecture/LISTING_PHYSICAL_DATA_MODEL.md` as the canonical source for the approved conceptual physical model; do not treat it as a committed SQL schema.
- Use `docs/architecture/PROPERTY_PROFILE_ARCHITECTURE.md` for approved Rich Property Profile behavior and `docs/architecture/PROPERTY_CATALOG_ARCHITECTURE.md` for approved Catalog/Data Governance behavior; Rich Property Profile remains unimplemented, while Property Catalog is partially implemented through BF014.1 schema and BF014.2 repositories/read models.
- Use `docs/database/SEED_DATA.md` as the approved seed strategy. Schema migrations and seed execution remain separate, and no Property catalog seed scripts currently exist.
- Use `docs/sprints/BF013-property-ownership-foundation.md` as the authoritative closed BF013 implementation contract.
- Keep future Listing SQL schema, APIs, Listing Permission codes, Team scope, complete Request/Deal/Commission/Transfer workflows, publication field lists, and subsequent sprint scope deferred until separately approved.
- Do not infer implementation from broader planned architecture documents.
- Preserve Organization Property versus Listing separation, Organization-private Owner/Ownership/Proposal data, backend-authoritative completeness, and the Arabic-first/RTL-first frontend direction.
