# Changelog

All notable changes to Directors Resale Platform will be documented in this file.

---

# 2026-08-09

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
