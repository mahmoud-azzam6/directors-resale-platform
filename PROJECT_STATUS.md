# Project Status

Status: Active

Current Project Phase: No current sprint selected — AF004 Property Administration UI IMPLEMENTED AND VERIFIED / CLOSED

Current Milestone: Milestone 2 - Core Business

Completed Sprints: BF001-BF015, AF001-AF004

Current Sprint: None — AF004 IMPLEMENTED AND VERIFIED / CLOSED


Next Internal Unit: None selected

Latest Stable Commit: AF004 closure pending documentation commit on codex-review

Last Updated: 2026-09-24

---

# Summary

BF001 through BF005 established the reusable backend foundation and HTTP infrastructure. BF006-BF008 implemented the approved Organization, Franchise, and Partner Agency hierarchy using Organization-backed records.

The application now has:

- Backend bootstrap and application runtime
- Service container with constructor injection, interface bindings, and singleton support
- Service provider infrastructure
- Contracts layer for core services and repositories
- Lazy PDO database manager and database abstractions
- Parameterized query builder and reusable base repository
- Router, standardized JSON responses, exception handling, and health endpoint
- HTTP Kernel with framework-independent request, input, header, cookie, server, JSON, and upload abstractions
- Organization model, validator, repository, service, controller, and CRUD API routes
- BF006 routing fix committed as `a017c94`
- Restored and verified `organizations` table using the intentional BF006 seven-field schema
- Franchise module with CRUD API routes, System Organization parent validation, and status-based archival
- BF007 migration adding indexed, self-referencing `parent_organization_id`
- Partner Agency module with CRUD API routes, Franchise parent validation, endpoint isolation, and status-based archival
- User module with staged CRUD API routes, active Organization ownership validation, globally unique email validation, immutable Organization ownership, and status-based deactivation
- `users` table with a restricted Organization foreign key and indexed `organization_id`
- AF001 Next.js Admin UI foundation under `frontend/` with secure HttpOnly auth bridge, typed API/context layer, protected App Router layout, and permission-aware navigation

Organization, Franchise, Partner Agency, staged User Management, Authentication Foundation, Dynamic Positions, Permissions & Authorization, BF013 Property & Ownership Foundation, AF001-AF004, BF014 Canonical Property Catalog Foundation, and BF015 Property Profile Persistence Bridge are implemented and verified. AF004 delivers the `/admin/properties` administration flow through existing BF013 Property/Ownership, BF014 projections/catalogs, and BF015 progressive Profile persistence. Rich Property Profile beyond the bridge, completeness truth, media and private-document persistence, Listing, Marketplace, Requests, Deals, Commissions, transfers, and Listing readiness remain unimplemented.

Approved architecture now defines one authenticated Admin Application whose experience is determined by
Organization, Position, Permissions, and applicable resource scope. It also separates future Global
Marketplace Visibility from Administrative Scope: authenticated Users may browse marketplace-eligible
available Listings platform-wide, while management, reporting, commission, User, and operational access
remain scope-controlled. `docs/architecture/LISTING_DOMAIN_ARCHITECTURE.md` defines approved Listing behavior, and `docs/architecture/LISTING_PHYSICAL_DATA_MODEL.md` defines the approved conceptual physical model without committing SQL schema. Listings, Requests, Reports, Commissions, Teams, and transfer workflows remain unimplemented.

## Selected Backend Sprint

BF013, BF014, BF015, AF001–AF003, and AF004 are **IMPLEMENTED AND VERIFIED / CLOSED**. AF004.1 through AF004.6 are closed. No current sprint or next internal unit is selected. Rich Property Profile persistence beyond BF015, media/private-document persistence, frontend-owned completeness, Listing, Marketplace, Requests, Deals, Commissions, and transfers remain unimplemented; Property setup review is not Listing Ready.

## Architecture Overview

The current foundation follows this direction:

public/index.php

↓

HTTP Kernel / Request

↓

Router

↓

Organization Controller

↓

Organization Service

↓

Organization Repository

↓

Query Builder / PDO

↓

MySQL

The Service Container is the composition root for infrastructure services.

## Known Discrepancies

- The global no-hard-delete policy conflicts with the current Organization physical DELETE behavior.
- The broader canonical Organization schema and ULID/audit standards exceed the intentional BF006-BF007 staged implementation.
- Standalone acceptance/regression scripts exist; no unified project-wide test runner exists.
- Authentication and authorization remain PHP-authoritative; AF001 does not duplicate security calculations.
- Franchise DELETE archives by setting `status` to `inactive`; the existing Organization DELETE behavior remains a separate known discrepancy.
