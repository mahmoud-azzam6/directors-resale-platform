# Changelog

All notable changes to Directors Resale Platform will be documented in this file.

---

# 2026-08-19

## BF011 - Dynamic Positions

- Added dynamic Organization-owned Position CRUD under `/positions` with Organization-scoped code uniqueness.
- Added nullable `users.position_id` with same-Organization and active-Position assignment validation.
- Allowed Position reassignment and clearing while preserving BF009 Organization ownership immutability.
- Implemented Position DELETE as deactivation without clearing historical User relationships.
- Protected Position endpoints with BF010 authentication without adding Authorization, Roles, Permissions, or hierarchy behavior.
- Verified BF011 database-backed acceptance and authenticated BF006-BF010 regression checks; no automated test suite exists.

# 2026-08-19

## BF010 - Authentication Foundation

- Added secure `password_hash` credentials and a dedicated `auth_tokens` table with hashed tokens, expiry, revocation, and User ownership.
- Added `POST /auth/login`, `POST /auth/logout`, and `GET /auth/me`.
- Added reusable bearer authentication middleware and protected Organizations, Franchises, Partner Agencies, and Users routes.
- Added a narrow CLI-only initial password setup mechanism; no public registration, reset, refresh tokens, sessions, or raw credential persistence.
- Verified login, token lifecycle, logout, inactive-user enforcement, public health/login, route protection, and authenticated BF006-BF009 regression checks.
- Authorization, Roles, Permissions, Positions, User Profiles, and Transfers remain deferred; no future module was introduced.

# 2026-08-19

## BF009 - User Management

- Added the staged `User` module and CRUD API routes under `/users`.
- Added `users` with Organization ownership, a foreign key and index on `organization_id`, global unique email, and only the BF009 fields.
- Enforced active System, Franchise, or Partner Agency Organization ownership and immutable `organization_id` through normal User updates.
- Implemented DELETE as `status = inactive` while preserving the database row and hiding inactive Users from active endpoints.
- Deferred Authentication, Authorization, Positions, Permissions, User Profiles, and Transfers; no future module was introduced.
- Verified BF009 database-backed acceptance and BF006-BF008 regression checks; no automated test suite exists.

# 2026-08-16

## BF008 - Partner Agency Management

- Added Organization-backed Partner Agency CRUD API routes under `/partner-agencies`.
- Enforced Franchise parents, hierarchy isolation, global Organization code uniqueness, and Partner Agency-only endpoint access.
- Reused `organizations.parent_organization_id`; no Partner Agency table or BF008 migration was required.
- Implemented status-based Partner Agency archival (`inactive`) without physical row removal.
- Deferred authentication, authorization, ULID, and extended audit work; no automated test suite exists.

# 2026-08-12

## BF007 - Franchise Management

- Added Organization-backed Franchise management with CRUD API routes under `/franchises`.
- Added `parent_organization_id` through the BF007 migration, including its index and self-referencing foreign key.
- Enforced System Organization parents, unique codes, and Franchise-only endpoint filtering.
- Implemented status-based Franchise archival (`inactive`) for DELETE without physical row removal.
- Verified manual BF007 acceptance and Organization regression checks; no automated test suite exists.

# 2026-08-09

## BF006 - Organization Module

- Implemented the Organization model, validator, repository, service, controller, and CRUD API routes.
- Added the `organizations` database migration and restored/verified the intentional BF006 seven-field table.
- Fixed Organization route Request injection in commit `a017c94`.
- Verified health and Organization CRUD smoke tests; no automated test suite exists.

## BF001 - Backend Foundation

- Added the backend bootstrap and application runtime.
- Added the dependency injection container and service provider infrastructure.
- Added the lazy PDO database manager.
- Added the basic HTTP router, standardized JSON response object, exception handler, and `/api/v1/health` endpoint.

## BF002 - Infrastructure Foundation

- Added reusable infrastructure contracts for cache, logging, transactions, validation, and CRUD repositories.

## BF002.1 - Contracts Layer

- Added the contracts layer used by the database and repository foundation.

## BF002.2 - Database Foundation

- Added database connection and query-builder interfaces.
- Added the parameterized `BaseQueryBuilder` and database value objects.

## BF003 - Base Repository

- Added the reusable `BaseRepository` with generic CRUD operations and constructor-injected database dependencies.

## BF004 - Service Container & Dependency Injection

- Registered infrastructure services through the service container.
- Added interface-to-implementation bindings, singleton registrations, and automatic nested constructor resolution.
- Moved logger construction out of bootstrap and into the application service provider.

## BF005 - HTTP Kernel

- Added the HTTP Kernel and framework-independent Request abstraction.
- Added input, header, cookie, server, JSON-body, and uploaded-file accessors.
- Removed direct request superglobal access from the public entry point.
- Registered Request and Kernel through the service container.

## Status

- Milestone 1 - Backend Foundation is complete.
- Next planned sprint: BF006 - Organization Module.

This status entry records the state at BF005 completion and is retained as history. The current state
includes BF008 Partner Agency Management; no later milestone has been selected.
