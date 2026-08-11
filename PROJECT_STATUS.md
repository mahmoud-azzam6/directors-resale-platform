# Project Status

Status: Active

Current Project Phase: Core Business Development

Current Milestone: Milestone 2 - Core Business

Completed Sprints: BF001, BF002, BF002.1, BF002.2, BF003, BF004, BF005

Current Sprint: BF007 - Franchise Management (Completed)

Next Sprint: Next development milestone requires explicit project decision

Latest Stable Commit: a017c94 - fix(BF006): inject request into organization routes

Last Updated: 2026-08-12

---

# Summary

BF001 through BF005 established the reusable backend foundation and HTTP infrastructure. BF006 implemented the Organization Module. BF007 implemented Franchise Management using Organization-backed records.

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

Organization and Franchise Management are the implemented business scope. There is no Users, Partner Agency, authentication, or authorization module. No automated test suite exists; BF006 and BF007 manual smoke/acceptance checks passed.

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
- Authentication and authorization remain unavailable integration dependencies; BF007 does not add a parallel security system.
- Franchise DELETE archives by setting `status` to `inactive`; the existing Organization DELETE behavior remains a separate known discrepancy.
