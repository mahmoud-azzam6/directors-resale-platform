# BF007 - Franchise Management

## Objective

Implement the Franchise level of the Directors Resale Platform business hierarchy.

This sprint builds on the completed Organization module from BF006.

The Franchise is represented using the existing `organizations` entity. No separate `franchises` table is introduced.

The purpose of this sprint is to establish Franchise management while preserving the existing BF006 architecture and the approved Organization hierarchy.

---

## Background

BF001–BF005 have been completed as the platform foundation.

BF006 has been completed and verified as the first business module.

The platform currently provides:

- Application Bootstrap
- Service Container
- Dependency Injection
- Database Layer
- Query Builder
- Base Repository
- HTTP Kernel
- Organization module
- Organization CRUD
- Organization validation
- Organization persistence
- Organization API
- Standardized JSON responses
- Verified request routing

The current BF006 Organization schema is intentionally staged and contains only:

- id
- name
- code
- organization_type
- status
- created_at
- updated_at

BF007 extends this staged Organization model with the minimum hierarchy relationship required for Franchise management.

---

## Scope

Implement Franchise management using the existing `organizations` entity.

Do NOT create a separate `franchises` table.

Suggested structure:

app/

    Modules/

        Franchise/

            Controllers/

            Services/

            Repositories/

            Models/

            Validators/

Only create classes required by the implementation.

Avoid unnecessary abstractions.

Preserve the architecture established by BF006.

---

## Franchise

A Franchise represents a franchise organization within the platform.

A Franchise is stored as an Organization record with:

```text
organization_type = franchise
```

The approved organization hierarchy is:

System Organization

↓

Franchise

↓

Partner Agency

Partner Agency management is outside the scope of BF007.

The approved terminology is **Partner Agency**. Do not use Partner Company as an alternative term in this module.

---

## Organization Hierarchy

The approved hierarchy is:

```text
System Organization
        |
        v
    Franchise
        |
        v
 Partner Agency
```

For BF007:

- A Franchise must belong directly to the System Organization.
- A Franchise cannot have another Franchise as its parent.
- A Franchise cannot have a Partner Agency as its parent.
- A Franchise cannot be its own parent.
- The parent relationship is represented by `parent_organization_id`.
- `parent_organization_id` references `organizations.id`.
- Cross-organization ownership is prohibited.

Partner Agency behavior is not implemented in BF007.

---

## Database

Continue using:

```text
organizations
```

Do NOT create:

```text
franchises
```

Create a new BF007 migration.

Do not modify or replace the BF006 migration.

Add only the minimum field required for the approved hierarchy:

- parent_organization_id

The field must reference:

```text
organizations.id
```

The existing BF006 fields remain unchanged:

- id
- name
- code
- organization_type
- status
- created_at
- updated_at

### Staged Database Policy

The broader DB101 architecture contains additional requirements such as ULID and extended audit fields.

Those requirements are intentionally deferred.

BF007 must NOT expand the Organization schema to the full DB101 definition.

The BF007 database change is limited to the hierarchy relationship required by this sprint.

---

## Franchise Validation Rules

Validate at minimum:

- name is required
- code is required
- code must be unique
- organization_type must be `franchise`
- parent_organization_id is required
- parent organization must exist
- parent organization must be a System Organization
- a Franchise cannot be its own parent
- a Franchise cannot use another Franchise as its parent
- a Franchise cannot use a Partner Agency as its parent

Validation should remain reusable.

---

## Business Rules

The approved business hierarchy is:

```text
System Organization
    ↓
Franchise
    ↓
Partner Agency
```

BF007 implements only the Franchise level.

Rules for BF007:

- Every Franchise belongs to exactly one System Organization.
- A System Organization may have multiple Franchises.
- A Franchise cannot belong to another Franchise.
- A Franchise cannot belong to a Partner Agency.
- A Franchise cannot belong to itself.
- Cross-organization ownership is prohibited.
- Franchise codes remain unique.
- Franchise records are Organization records, not separate Franchise records.

Partner Agency implementation is deferred to a future milestone.

---

## API Endpoints

Implement:

```text
GET    /franchises
GET    /franchises/{id}
POST   /franchises
PUT    /franchises/{id}
DELETE /franchises/{id}
```

The endpoints operate only on Organization records where:

```text
organization_type = franchise
```

They must not expose non-Franchise Organization records through Franchise endpoints.

Return standardized JSON responses using the existing infrastructure.

### Delete Semantics

The project-wide rule is:

**No Hard Delete.**

Therefore:

```text
DELETE /franchises/{id}
```

must NOT physically delete the database row.

For BF007, DELETE means:

```text
archive / deactivate
```

using the existing `status` field and the minimum staged behavior required by this sprint.

Do not introduce a separate archive/lifecycle system.

---

## Security Dependency

The project security principles require authenticated requests and authorized business actions.

Authentication and authorization infrastructure are not implemented yet.

BF007 must NOT build a new authentication or authorization system.

Instead:

- Franchise endpoints are intended to require authentication.
- Franchise business actions are intended to require authorization.
- The existing application architecture must be used as the integration point.
- No parallel authentication system may be introduced.
- No new roles or permission system may be implemented in BF007.

If the current infrastructure cannot enforce authentication/authorization yet, document the limitation clearly in the implementation and tests rather than silently introducing a replacement mechanism.

---

## Repository

Create:

```text
FranchiseRepository
```

The repository must extend `BaseRepository`.

Responsibilities:

- Retrieve Franchise organizations
- Find a Franchise by ID
- Create Franchise organization records
- Update Franchise organization records
- Archive/deactivate Franchise records
- Filter operations to `organization_type = franchise`

No custom SQL unless absolutely required.

Do not put business rules in the repository.

---

## Service

Create:

```text
FranchiseService
```

Responsibilities:

- Franchise validation
- Parent Organization validation
- Franchise CRUD orchestration
- Archive/deactivate behavior
- Repository coordination
- Enforcement of Franchise-specific business rules

No infrastructure logic.

Business logic must remain inside the Service.

---

## Controller

Create:

```text
FranchiseController
```

Responsibilities:

- Receive HTTP requests
- Validate/parse request input through the existing application conventions
- Call FranchiseService
- Return standardized JSON responses

Controllers must remain thin.

No business logic inside controllers.

---

## Routing

Register Franchise routes using the existing Router.

Do not redesign Router architecture.

Use the Request injection behavior established and verified during BF006.

Do not modify unrelated existing routes.

---

## Model

Create a Franchise model only if required by the existing module conventions.

The model represents an Organization-backed Franchise.

Do not duplicate the Organization entity.

Do not create a separate persistence model/table for Franchise unless the existing architecture requires it.

---

## Design Principles

The module must:

- Reuse the existing Organization entity
- Use Dependency Injection
- Use Constructor Injection
- Extend BaseRepository
- Use the Service Layer
- Keep Controllers thin
- Keep Business Logic inside Services
- Keep infrastructure logic outside business services
- Preserve the existing BF006 architecture
- Avoid duplicated business data
- Avoid unnecessary abstractions
- Remain framework-independent

---

## Coding Standards

- PHP 8+
- `declare(strict_types=1);`
- PSR-12
- SOLID Principles
- Typed Properties
- Typed Parameters
- Typed Return Types

Follow the coding conventions already established by BF006.

---

## Non-Goals

This sprint must NOT implement:

- Partner Agencies
- Users
- Authentication
- Authorization
- Roles
- Permissions
- Branches
- Properties
- Listings
- Deals
- Commissions
- Reporting
- Dashboards
- Notifications
- File Uploads
- Subscription/Billing
- Franchise financial logic
- Full organization lifecycle/archiving framework
- Full DB101 schema migration
- ULID migration
- Extended audit-field migration
- A separate `franchises` table

---

## Acceptance Criteria

- BF007 uses the existing `organizations` table.
- No `franchises` table is created.
- Franchise records use `organization_type = franchise`.
- `parent_organization_id` is added through a new BF007 migration.
- The parent relationship references `organizations.id`.
- A Franchise must have a valid System Organization parent.
- Self-parenting is rejected.
- Franchise-as-parent is rejected.
- Partner Agency-as-parent is rejected.
- Franchise codes remain unique.
- Franchise CRUD works.
- Franchise endpoints return only Franchise records.
- DELETE does not physically remove the Franchise record.
- DELETE archives/deactivates the Franchise using the staged `status` mechanism.
- Repository extends `BaseRepository`.
- Service contains business logic.
- Controller contains no business logic.
- Routes are registered using the existing Router.
- Standardized JSON responses are returned.
- Existing Organization functionality continues to work.
- Existing BF006 routes continue to work.
- No architectural redesign is introduced.
- Deferred ULID and extended audit requirements remain deferred.
- No Partner Agency implementation is introduced.
- No authentication or authorization system is implemented inside BF007.

---

## Testing

Verify at minimum:

### Create

- Create a valid Franchise under a valid System Organization
- Missing name
- Missing code
- Duplicate code
- Missing parent
- Invalid parent ID
- Non-existent parent
- Franchise as parent
- Partner Agency as parent
- Self-parent

### Read

- List Franchise records
- Retrieve Franchise by ID
- Request a non-Franchise Organization through Franchise endpoint
- Request a non-existent Franchise

### Update

- Update a valid Franchise
- Attempt invalid parent
- Attempt duplicate code
- Attempt self-parent
- Attempt Franchise parent

### Delete / Archive

- Archive/deactivate a valid Franchise
- Verify the database record is not physically deleted
- Verify archived/deactivated Franchise is handled according to API rules
- Request archived/deactivated Franchise

### Regression

Verify:

- `GET /api/v1/health`
- Existing Organization CRUD
- Existing Organization validation
- Existing Organization routes
- Existing Router behavior

### Security Dependency

Verify and document whether the current infrastructure can enforce:

- authentication
- authorization

Do not create authentication or authorization infrastructure as part of BF007.

Temporary test data must be cleaned up after testing.

---

## Documentation

Update only the documentation required by the existing project workflow.

Where applicable update:

- `MASTER_INDEX.md`
- `PROJECT_STATUS.md`
- `CURRENT_SYSTEM_STATE.md`
- `IMPLEMENTATION_PLAN.md`
- `DATABASE_CHANGELOG.md`
- API documentation
- Sprint Log

Record:

- BF007 completion status
- Franchise module implementation
- `parent_organization_id` schema change
- staged database scope
- deferred ULID/audit requirements
- delete/archive semantics
- security dependency/limitation
- tests performed

Do not rewrite unrelated architecture documentation.

---

## Deliverables

Implement only the Franchise module.

Expected deliverables:

- Franchise module classes required by the architecture
- BF007 database migration
- Franchise API endpoints
- Franchise validation
- Archive/deactivate behavior
- Tests or verification required by the existing project testing approach
- Required documentation updates

Do not implement any other business module.

Stop immediately after BF007.
