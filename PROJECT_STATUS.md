# Project Status

Status: Active

Current Project Phase: BF014 IN PROGRESS; BF014.1, BF014.2, and BF014.3 IMPLEMENTED AND VERIFIED / CLOSED

Current Milestone: Milestone 2 - Core Business

Completed Sprints: BF001, BF002, BF002.1, BF002.2, BF003, BF004, BF005, BF006, BF007, BF008, BF009, BF010, BF011, BF012, BF013, AF001, AF002, AF003

Current Sprint: BF014 - Canonical Property Catalog Foundation (IN PROGRESS)

Next Internal Unit: BF014.5 ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â Catalog HTTP reads and mutations (NEXT / NOT STARTED)

Next Selected Sprint: None after BF014; BF015 NOT SELECTED

Latest Stable Commit: 2669b12 - test: add BF014 repository database acceptance

Last Updated: 2026-09-19

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

Organization, Franchise, Partner Agency, staged User Management, Authentication Foundation, Dynamic Positions, Permissions & Authorization, BF013 Property & Ownership Foundation, and AF001-AF003 are implemented. BF013 remains closed. Rich Property Profile architecture is APPROVED / NOT IMPLEMENTED; Property Catalog/Data Governance architecture is APPROVED and partially implemented through BF014.1 schema, BF014.2 repositories/read models, and BF014.3 services; their canonical sources are `docs/architecture/PROPERTY_PROFILE_ARCHITECTURE.md` and `docs/architecture/PROPERTY_CATALOG_ARCHITECTURE.md`. The broader Listing Domain and Add Unit/frontend Property workflows remain unimplemented, and BF014 remains IN PROGRESS.

Approved architecture now defines one authenticated Admin Application whose experience is determined by
Organization, Position, Permissions, and applicable resource scope. It also separates future Global
Marketplace Visibility from Administrative Scope: authenticated Users may browse marketplace-eligible
available Listings platform-wide, while management, reporting, commission, User, and operational access
remain scope-controlled. `docs/architecture/LISTING_DOMAIN_ARCHITECTURE.md` defines approved Listing behavior, and `docs/architecture/LISTING_PHYSICAL_DATA_MODEL.md` defines the approved conceptual physical model without committing SQL schema. Listings, Requests, Reports, Commissions, Teams, and transfer workflows remain unimplemented.

## Selected Backend Sprint

BF014 is the current Backend sprint: **IN PROGRESS**. The parent contract remains authoritative for overall scope, baseline matrices, non-goals, and Definition of Done. BF014.1 through BF014.4 are **IMPLEMENTED AND VERIFIED / CLOSED**. BF014.5 is **NEXT / NOT STARTED**; BF014.6-BF014.8 are **NOT STARTED** and BF015 is not selected. See the [BF014.4 contract](docs/sprints/BF014.4-authorization-and-http.md). These are internal BF014 units, not separate sprints. BF013 remains **IMPLEMENTED AND VERIFIED / CLOSED**. Property Catalog architecture is partially implemented through schema, repositories, PropertyCatalogService, UnitTypeConfigurationService, GeographicLocationService, and DevelopmentCatalogService. Rich Property Profile and Listing remain **APPROVED / NOT IMPLEMENTED**.

## Architecture Overview

The current foundation follows this direction:

public/index.php

ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œ

HTTP Kernel / Request

ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œ

Router

ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œ

Organization Controller

ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œ

Organization Service

ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œ

Organization Repository

ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œ

Query Builder / PDO

ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œ

MySQL

The Service Container is the composition root for infrastructure services.

## Known Discrepancies

- The global no-hard-delete policy conflicts with the current Organization physical DELETE behavior.
- The broader canonical Organization schema and ULID/audit standards exceed the intentional BF006-BF007 staged implementation.
- Standalone acceptance/regression scripts exist; no unified project-wide test runner exists.
- Authentication and authorization remain PHP-authoritative; AF001 does not duplicate security calculations.
- Franchise DELETE archives by setting `status` to `inactive`; the existing Organization DELETE behavior remains a separate known discrepancy.
