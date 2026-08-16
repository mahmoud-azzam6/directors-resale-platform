# BF008 - Partner Agency Management

## Status

**APPROVED DEVELOPMENT SPRINT**

Milestone: **Milestone 2 - Core Business**

Depends on:

* BF006 - Organization Management
* BF007 - Franchise Management

---

## Objective

Implement the Partner Agency level of the Directors Resale Platform business hierarchy.

BF008 extends the Organization hierarchy established by BF006 and BF007:

```text
System Organization
        ↓
    Franchise
        ↓
 Partner Agency
```

A Partner Agency must use the existing `organizations` entity.

No separate `partner_agencies` table must be introduced.

The purpose of BF008 is to complete the currently approved Organization hierarchy before introducing Users or later business modules.

---

## Background

The following development work is already completed:

* BF001-BF005 - Platform Foundation
* BF006 - Organization Management
* BF007 - Franchise Management

The current implemented hierarchy supports:

```text
System Organization
        ↓
    Franchise
```

BF007 introduced:

```text
parent_organization_id
```

on the existing:

```text
organizations
```

table.

BF008 uses that existing relationship to introduce Partner Agency management.

The approved terminology is:

**Partner Agency**

Do not use `Partner Company` as an alternative term.

---

## Scope

Implement complete Partner Agency management using the existing `organizations` entity.

A Partner Agency is represented by:

```text
organization_type = partner_agency
```

and belongs directly to exactly one Franchise through:

```text
parent_organization_id
```

Do NOT create a separate Partner Agency persistence structure.

Suggested module structure:

```text
app/
└── Modules/
    └── PartnerAgency/
        ├── Controllers/
        ├── Services/
        ├── Repositories/
        ├── Models/
        └── Validators/
```

Only create classes required by the existing application architecture.

Avoid unnecessary abstractions.

Follow the patterns established by BF006 and BF007.

---

## Partner Agency

A Partner Agency represents an external agency operating under one Franchise within the Directors Resale Platform.

A Partner Agency is an Organization record where:

```text
organization_type = partner_agency
```

Its parent must be:

```text
organization_type = franchise
```

The relationship is:

```text
System Organization
        ↓
    Franchise
        ↓
 Partner Agency
```

BF008 implements only Partner Agency management.

Users belonging to Partner Agencies are outside the scope of this sprint.

---

## Organization Hierarchy

The approved hierarchy remains:

```text
System Organization
        |
        v
    Franchise
        |
        v
 Partner Agency
```

BF008 must preserve this hierarchy.

Rules:

* A Partner Agency must belong directly to one Franchise.
* A Partner Agency cannot belong directly to the System Organization.
* A Partner Agency cannot belong to another Partner Agency.
* A Partner Agency cannot be its own parent.
* The parent Franchise must exist.
* The parent Organization must have `organization_type = franchise`.
* Cross-organization ownership is prohibited.

BF008 must not introduce additional hierarchy levels.

---

## Database

Continue using:

```text
organizations
```

Do NOT create:

```text
partner_agencies
```

BF008 should use the existing BF007 field:

```text
parent_organization_id
```

which references:

```text
organizations.id
```

No new database column is expected for the core Partner Agency implementation.

Before creating any migration, inspect the current database schema.

If the current BF007 schema already provides everything required by BF008, **do not create an empty or unnecessary migration**.

Do not modify previous migrations.

---

## Current Staged Organization Schema

The current staged Organization model contains:

* id
* name
* code
* organization_type
* status
* created_at
* updated_at
* parent_organization_id

BF008 must work within this staged schema.

---

## Staged Database Policy

The broader DB101 architecture contains future database requirements that are not part of BF008.

The following remain deferred:

* ULID migration
* Extended audit fields
* Full organization lifecycle model
* Broader DB101 schema
* Users and organization membership structures
* Permissions and authorization structures

Do not expand the database merely to align the staged implementation with the complete future architecture.

BF008 should introduce database changes only if technically required for Partner Agency management.

---

## Validation Rules

Validate at minimum:

* name is required
* code is required
* code must be unique
* organization_type must be `partner_agency`
* parent_organization_id is required
* parent_organization_id must be valid
* parent organization must exist
* parent organization must be a Franchise
* System Organization cannot be used as the direct parent
* Partner Agency cannot be used as parent
* Partner Agency cannot be its own parent

Validation must remain reusable and follow existing BF006/BF007 conventions.

---

## Business Rules

BF008 must enforce the following:

### Partner Agency Type

Every Partner Agency record must use:

```text
organization_type = partner_agency
```

The client must not be able to create another Organization type through Partner Agency endpoints.

### Parent Ownership

Every Partner Agency belongs to exactly one Franchise.

The Franchise is identified using:

```text
parent_organization_id
```

### Valid Parent

The parent must:

* exist
* be a Franchise
* be a valid Organization record

The following are invalid parents:

* System Organization
* Partner Agency
* nonexistent Organization
* invalid Organization ID
* the Partner Agency itself

### Franchise Ownership

A Franchise may own multiple Partner Agencies.

BF008 does not introduce a fixed limit on the number of Partner Agencies belonging to a Franchise.

### Cross-Organization Ownership

A Partner Agency belongs to its parent Franchise hierarchy.

BF008 must not permit a Partner Agency to bypass the Franchise level and attach directly to another hierarchy level.

Detailed future data-access authorization remains outside BF008.

### Unique Code

Partner Agency codes must remain unique according to the existing Organization code uniqueness rules.

Do not introduce a separate Partner Agency code namespace.

---

## Status

Use the existing Organization:

```text
status
```

field.

Do not introduce Partner Agency-specific status fields.

Follow existing staged Organization status conventions.

---

## API Endpoints

Implement:

```text
GET    /partner-agencies
GET    /partner-agencies/{id}
POST   /partner-agencies
PUT    /partner-agencies/{id}
DELETE /partner-agencies/{id}
```

All Partner Agency endpoints operate only on records where:

```text
organization_type = partner_agency
```

Partner Agency endpoints must not expose:

* System Organizations
* Franchises
* other future Organization types

Return standardized JSON responses using the existing application infrastructure.

---

## List Behavior

```text
GET /partner-agencies
```

must return Partner Agency records only.

Follow the active/inactive behavior established by BF007 unless the existing shared architecture explicitly requires otherwise.

Do not introduce pagination, search, sorting, or filtering infrastructure unless already supported by the existing architecture and required by current conventions.

---

## Show Behavior

```text
GET /partner-agencies/{id}
```

must return the Partner Agency only when the requested record is valid for the Partner Agency endpoint.

A request for:

* System Organization
* Franchise
* nonexistent Organization
* unavailable Partner Agency according to existing status behavior

must not expose that record as a Partner Agency.

Use existing API error conventions.

---

## Create Behavior

```text
POST /partner-agencies
```

must:

1. Validate required input.
2. Force/ensure `organization_type = partner_agency`.
3. Validate the parent Organization.
4. Ensure the parent is a Franchise.
5. Validate code uniqueness.
6. Create the Partner Agency through the existing architecture.

The endpoint must not permit creation of:

* System Organization
* Franchise

through the Partner Agency module.

---

## Update Behavior

```text
PUT /partner-agencies/{id}
```

must support updates according to existing Organization conventions while preserving Partner Agency business rules.

Updating a Partner Agency must not allow:

* changing it into another Organization type
* assigning System Organization as parent
* assigning another Partner Agency as parent
* assigning a nonexistent parent
* assigning itself as parent
* creating a duplicate Organization code

Changing the parent Franchise is allowed only when the new parent is another valid Franchise and all Partner Agency hierarchy rules remain satisfied.

No transfer workflow is introduced in BF008.

---

## Delete / Archive Behavior

The project-wide policy remains:

**NO HARD DELETE**

Therefore:

```text
DELETE /partner-agencies/{id}
```

must NOT physically delete the Organization record.

DELETE means:

```text
archive / deactivate
```

using the existing:

```text
status
```

mechanism established for Franchise management.

Do not introduce a new lifecycle or archive framework.

The database row must remain after deactivation.

---

## Repository

Create:

```text
PartnerAgencyRepository
```

The repository must extend:

```text
BaseRepository
```

Responsibilities:

* retrieve Partner Agency organizations
* find Partner Agency by ID
* create Partner Agency Organization records
* update Partner Agency Organization records
* archive/deactivate Partner Agency records
* restrict Partner Agency operations to `organization_type = partner_agency`

Do not put business rules in the repository.

Avoid custom SQL unless technically required by the existing infrastructure.

---

## Service

Create:

```text
PartnerAgencyService
```

Responsibilities:

* Partner Agency business validation
* parent Franchise validation
* Partner Agency CRUD orchestration
* hierarchy enforcement
* archive/deactivate behavior
* repository coordination

Business rules belong in the Service layer.

Do not place HTTP or infrastructure logic inside the Service.

---

## Controller

Create:

```text
PartnerAgencyController
```

Responsibilities:

* receive HTTP requests
* obtain request input using existing conventions
* call PartnerAgencyService
* return standardized JSON responses

Controllers must remain thin.

No Partner Agency business logic should be implemented directly in the Controller.

---

## Validator

Create:

```text
PartnerAgencyValidator
```

if consistent with the BF006/BF007 implementation pattern.

Responsibilities should remain limited to reusable validation behavior.

Do not duplicate business orchestration from PartnerAgencyService.

---

## Model

Create:

```text
PartnerAgency
```

only if consistent with the existing Organization/Franchise implementation pattern.

The model must represent an Organization-backed Partner Agency.

Do not create separate Partner Agency persistence.

---

## Dependency Injection

Register BF008 dependencies through the existing application service-container conventions.

Use:

* Dependency Injection
* Constructor Injection

Do not introduce service locators or manual global dependency creation.

Do not redesign the container.

---

## Routing

Register Partner Agency routes using the existing Router.

Do not modify Router architecture.

Do not rewrite BF006 or BF007 routing.

Preserve existing:

* Organization routes
* Franchise routes
* health route
* Request injection behavior

---

## Standardized Responses

Use the existing standardized JSON response infrastructure.

Do not introduce a Partner Agency-specific response envelope.

Follow existing conventions for:

* success
* validation errors
* not found
* internal errors

---

## Security Dependency

Authentication and authorization infrastructure are not currently implemented.

BF008 must NOT implement:

* Authentication
* Authorization
* Roles
* Permissions
* access-control middleware
* Partner Agency login
* Partner Agency user accounts

Partner Agency endpoints are intended to require authentication and authorization when the security infrastructure is implemented.

Until then, document this as an existing project dependency/limitation.

Do not create a parallel or temporary security architecture.

---

## No Hard Delete Policy

BF008 must comply with the project-wide No Hard Delete rule.

Partner Agency deletion must use staged status-based deactivation.

Do not copy the existing BF006 physical Organization deletion behavior into BF008.

The BF006 Organization hard-delete discrepancy remains outside BF008 scope.

---

## Design Principles

The module must:

* reuse the existing Organization entity
* reuse `parent_organization_id`
* use Dependency Injection
* use Constructor Injection
* extend BaseRepository
* use the Service Layer
* keep Controllers thin
* keep business logic inside Services
* keep persistence logic inside Repositories
* preserve the existing Router
* preserve standardized responses
* avoid duplicated persistence
* avoid unnecessary abstractions
* remain framework-independent
* preserve BF006/BF007 behavior

---

## Coding Standards

* PHP 8+
* `declare(strict_types=1);`
* PSR-12
* SOLID Principles
* Typed Properties
* Typed Parameters
* Typed Return Types

Follow the established project conventions and the implementation patterns already verified in BF006 and BF007.

---

## Non-Goals

BF008 must NOT implement:

* Users
* Authentication
* Authorization
* Roles
* Permissions
* Team Leaders
* Managers
* Sales users
* Partner Agency user accounts
* Branches
* CRM
* People
* Properties
* Listings
* Requirements
* Matching
* Deals
* Commissions
* Transfers
* Reporting
* Dashboards
* Notifications
* File Uploads
* WhatsApp integration
* Email integration
* AI
* Analytics
* Subscription/Billing
* Partner Agency commission rules
* Partner Agency financial rules
* Partner Agency transfer workflows
* Full Organization lifecycle framework
* Full DB101 migration
* ULID migration
* Extended audit-field migration
* Separate `partner_agencies` table

Do not implement future modules while preparing BF008.

---

## Acceptance Criteria

BF008 is complete only when all applicable criteria below pass.

### Architecture

* Partner Agency uses the existing `organizations` table.
* No `partner_agencies` table exists.
* Partner Agency uses `organization_type = partner_agency`.
* Existing `parent_organization_id` is reused.
* No unnecessary BF008 migration is created.

### Hierarchy

* Partner Agency requires a parent.
* Parent must exist.
* Parent must be a Franchise.
* System Organization parent is rejected.
* Partner Agency parent is rejected.
* Self-parenting is rejected.
* Changing to another valid Franchise parent works.

### CRUD

* Partner Agency creation works.
* Partner Agency listing works.
* Partner Agency retrieval works.
* Partner Agency update works.
* Partner Agency archive/deactivation works.

### Isolation

* Partner Agency endpoints return only Partner Agency records.
* Franchise records are not exposed through Partner Agency endpoints.
* System Organization records are not exposed through Partner Agency endpoints.
* Partner Agency endpoints cannot create other Organization types.

### Delete

* DELETE does not physically delete the row.
* DELETE changes the Partner Agency to the existing inactive/deactivated status.
* Archived Partner Agency behavior follows BF007 conventions.

### Architecture Regression

* BF006 Organization functionality continues to work.
* BF007 Franchise functionality continues to work.
* Existing Router behavior continues to work.
* Existing health endpoint continues to work.
* Existing standardized JSON responses remain unchanged.

### Scope

* No Users implementation is introduced.
* No Authentication implementation is introduced.
* No Authorization implementation is introduced.
* No Partner Agency-specific persistence table is introduced.
* No future business module is introduced.
* No full DB101 schema expansion occurs.

---

## Testing

Verify all applicable BF008 behavior.

### Create Tests

Verify:

* valid Partner Agency under valid Franchise
* missing name
* missing code
* missing parent
* invalid parent ID
* nonexistent parent
* System Organization as parent
* Partner Agency as parent
* duplicate code
* attempted non-Partner Agency organization type through Partner Agency endpoint

### Read Tests

Verify:

* list Partner Agencies
* retrieve Partner Agency by ID
* request Franchise ID through Partner Agency endpoint
* request System Organization ID through Partner Agency endpoint
* request nonexistent Partner Agency
* archived/deactivated Partner Agency behavior

### Update Tests

Verify:

* valid Partner Agency update
* change to another valid Franchise parent
* invalid parent
* nonexistent parent
* System Organization parent
* Partner Agency parent
* self-parent
* duplicate code
* attempted Organization type change

### Delete / Archive Tests

Verify:

* archive/deactivate valid Partner Agency
* database row remains
* status becomes inactive/deactivated according to existing convention
* archived Partner Agency behavior matches BF007 behavior

### Regression Tests

Verify at minimum:

```text
GET /api/v1/health
```

Existing Organization behavior.

Existing Franchise behavior, including:

```text
GET    /franchises
GET    /franchises/{id}
POST   /franchises
PUT    /franchises/{id}
DELETE /franchises/{id}
```

Existing Router behavior.

Temporary test data must be removed after verification.

---

## Automated Testing

If an automated test framework exists, add BF008 tests following existing conventions.

If no automated test framework exists:

* do not introduce an unrelated testing framework solely for BF008
* perform the available API/database verification
* report automated tests as unavailable
* document the testing limitation accurately

Do not claim automated coverage when none exists.

---

## Documentation

After successful implementation and verification, update only documentation required by the established project workflow.

Where applicable update:

* `AI_CONTEXT.md`
* `PROJECT_STATUS.md`
* `README.md`
* `CHANGELOG.md`
* `docs/MASTER_INDEX.md`
* `docs/development/CURRENT_SYSTEM_STATE.md`
* `docs/development/IMPLEMENTATION_PLAN.md`
* `docs/development/SPRINT_LOG.md`
* relevant database documentation
* relevant API/module registries

Record:

* BF008 completion
* Partner Agency implementation
* reuse of the `organizations` table
* reuse of `parent_organization_id`
* absence of a separate Partner Agency table
* archive/deactivate semantics
* staged database status
* deferred ULID/audit requirements
* authentication/authorization dependency
* verification performed

Do not rewrite unrelated documentation.

Do not select or invent BF009 during BF008 implementation.

The next milestone after BF008 requires a separate project decision.

---

## Deliverables

BF008 deliverables are limited to:

* Partner Agency module
* Partner Agency API endpoints
* Partner Agency validation
* Franchise-parent hierarchy enforcement
* status-based Partner Agency archive/deactivation
* dependency registration
* route registration
* applicable tests/verification
* required documentation updates

A database migration should be delivered only if inspection proves that BF008 requires a genuine schema change.

Do not create an empty migration merely to represent the sprint.

---

## Stop Condition

Stop immediately when BF008 acceptance criteria and required verification are complete.

Do not begin:

* Users
* Authentication
* Authorization
* CRM
* Property
* Listings
* Deals
* Commissions
* any future sprint

Do not select BF009.

Return the BF008 implementation and verification results only.
