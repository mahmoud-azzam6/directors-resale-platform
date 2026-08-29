# Project Status

Status: Active

Current Project Phase: Organization Administration

Current Milestone: Milestone 2 - Core Business

Completed Sprints: BF001, BF002, BF002.1, BF002.2, BF003, BF004, BF005, BF006, BF007, BF008, BF009, BF010, BF011, BF012

Current Sprint: AF003 - Organization User Administration (Implemented; browser QA pending)

Next Selected Milestone: Not selected

Latest Stable Commit: a017c94 - fix(BF006): inject request into organization routes

Last Updated: 2026-08-23

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

Organization, Franchise, Partner Agency, staged User Management, Authentication Foundation, Dynamic Positions, Permissions & Authorization, and AF001-AF003 are implemented. The frontend uses Next.js App Router, TypeScript, Tailwind, TanStack Query, React Hook Form, Zod, Lucide, centralized tokens, and source-owned UI primitives. Authentication remains PHP-authoritative through a server-side HttpOnly cookie bridge; browser storage never receives bearer tokens. AF003 manual browser QA remains pending before closure. No automated test suite exists; PHP, frontend typecheck/lint/build, database scope, Position integrity, delegation, lifecycle, and cleanup checks passed. No next milestone has been selected.

Approved architecture now defines one authenticated Admin Application whose experience is determined by
Organization, Position, Permissions, and applicable resource scope. It also separates future Global
Marketplace Visibility from Administrative Scope: authenticated Users may browse marketplace-eligible
available Listings platform-wide, while management, reporting, commission, User, and operational access
remain scope-controlled. Listings, Requests, Reports, Commissions, Teams, and provisioning workflows are
not implemented. AF002's approved direction is Network Administration UI; its exact sprint contract is
not approved and it is not selected for implementation.

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
- No automated test suite exists.
- Authentication and authorization remain PHP-authoritative; AF001 does not duplicate security calculations.
- Franchise DELETE archives by setting `status` to `inactive`; the existing Organization DELETE behavior remains a separate known discrepancy.
