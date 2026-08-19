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

Every completed database contract will immediately produce:

- SQL Migration
- Seeder
- Repository
- Validation Rules
- API Contract
