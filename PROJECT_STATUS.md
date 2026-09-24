# Project Status

Status: Active

Current Project Phase: AF004.1 ARCHITECTURE APPROVED / NOT IMPLEMENTED — Property Administration UI Architecture & Route Contract

Current Milestone: Milestone 2 - Core Business

Completed Sprints: BF001-BF015, AF001, AF002, AF003

Current Sprint: AF004 — Property Administration UI; AF004.1 Architecture & Route Contract


Next Internal Unit: AF004.2 — Property Data Step / NOT STARTED

Latest Stable Commit: d25487a - finalize BF015 current development target wording

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

Organization, Franchise, Partner Agency, staged User Management, Authentication Foundation, Dynamic Positions, Permissions & Authorization, BF013 Property & Ownership Foundation, AF001-AF003, BF014 Canonical Property Catalog Foundation, and BF015 Property Profile Persistence Bridge are implemented and verified. AF004.1 approves the Property Administration UI information architecture and existing `/admin` route contract only; AF004 implementation has not started. It reuses BF013 Property/Ownership, BF014 projections/catalogs, and BF015 progressive Profile persistence without implementing Rich Property Profile beyond the bridge, completeness truth, media, private documents, Listing, Marketplace, Requests, Deals, Commissions, or frontend Property behavior.

Approved architecture now defines one authenticated Admin Application whose experience is determined by
Organization, Position, Permissions, and applicable resource scope. It also separates future Global
Marketplace Visibility from Administrative Scope: authenticated Users may browse marketplace-eligible
available Listings platform-wide, while management, reporting, commission, User, and operational access
remain scope-controlled. `docs/architecture/LISTING_DOMAIN_ARCHITECTURE.md` defines approved Listing behavior, and `docs/architecture/LISTING_PHYSICAL_DATA_MODEL.md` defines the approved conceptual physical model without committing SQL schema. Listings, Requests, Reports, Commissions, Teams, and transfer workflows remain unimplemented.

## Selected Backend Sprint

BF013, BF014, and BF015 are **IMPLEMENTED AND VERIFIED / CLOSED**. AF004 — Property Administration UI — is **ARCHITECTURE APPROVED / NOT IMPLEMENTED**. AF004.1 is the approved architecture and route contract; AF004.2–AF004.6 are not started. Rich Property Profile persistence beyond BF015, Listing, and frontend Property implementation remain unimplemented.

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
