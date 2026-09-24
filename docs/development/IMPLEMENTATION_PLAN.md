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

BF013, BF014, BF015, and AF001–AF003 are **IMPLEMENTED AND VERIFIED / CLOSED**. AF004 — Property Administration UI — is **ARCHITECTURE APPROVED / NOT IMPLEMENTED**; AF004.1 locks the route and orchestration contract. AF004.2–AF004.6 remain not started.

The closed BF014 contract records schema/integrity, repositories/read models, services, authorization/HTTP, seed runner/baseline, dynamic form projection, MariaDB acceptance/regressions, and documentation closure. BF015 records the bounded Property Profile persistence bridge.

## Approved Future Execution Order

AF004 is selected for architecture only. AF004.1 is approved; AF004.2 is the next internal unit and has not started. No later workstream is selected.

1. Architecture closure - complete
2. BF014 parent sprint contract - recorded and authoritative
3. BF014 - Canonical Property Catalog Foundation - IMPLEMENTED AND VERIFIED / CLOSED
4. BF015 - Property Profile Persistence Bridge - IMPLEMENTED AND VERIFIED / CLOSED
5. Rich Property Profile beyond the bridge
6. System Admin Property Data Management frontend
7. Initial canonical data verification and approved business additions
8. Admin Properties frontend: Active, Drafts, Add Unit, and Property Details
9. Listing implementation
10. Requests, Deals, Commissions, Notifications, and Reports

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
