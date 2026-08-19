# BF011 - Dynamic Positions

## Status

**APPROVED DEVELOPMENT SPRINT**

Milestone: **Milestone 2 - Core Business**

Depends on:

* BF006 - Organization Management
* BF007 - Franchise Management
* BF008 - Partner Agency Management
* BF009 - User Management
* BF010 - Authentication Foundation

---

## Objective

Implement Dynamic Positions for internal platform Users.

A Position represents a business/job position inside one Organization.

Examples may include:

* Franchise Admin
* Manager
* Team Leader
* Sales
* Partner Agency Admin

These are examples only.

BF011 must NOT hard-code these position names as system roles.

Organizations must be able to define their own Positions dynamically.

BF011 establishes Position management and User-to-Position assignment only.

Permissions and Authorization are NOT implemented in this sprint.

---

## Background

The Core module is responsible for:

* Organizations
* Users
* Positions
* Permissions
* Settings
* Events
* Activity Logs

The implemented platform currently provides:

* Organization hierarchy
* Franchise Management
* Partner Agency Management
* User Management
* Authentication
* Protected business APIs

The documented Core architecture defines:

```text
One Organization
        ↓
Many Positions
```

BF011 implements that relationship.

---

## Architecture Decision

For the staged BF011 implementation:

* A Position belongs to exactly one Organization.
* An Organization may have many Positions.
* A User may be assigned to one Position.
* A Position may be assigned to many Users.
* The User and Position must belong to the same Organization.
* Position names are dynamic business data.
* Position names are NOT permission definitions.
* Position hierarchy is NOT implemented.
* Team hierarchy is NOT implemented.
* Permissions are NOT implemented.

This staged design prepares the platform for BF012 Authorization / Permissions.

---

## Scope

Implement:

* Position persistence
* Position CRUD
* Position validation
* Organization ownership validation
* Position status lifecycle
* User Position assignment
* User Position reassignment
* Same-Organization assignment enforcement
* Authentication protection using BF010

Suggested structure:

```text
app/
└── Modules/
    └── Position/
        ├── Controllers/
        ├── Services/
        ├── Repositories/
        ├── Models/
        └── Validators/
```

Only create required classes.

Follow BF006-BF010 architecture patterns.

---

## Position Entity

A Position represents a configurable job position belonging to one Organization.

Examples:

```text
Franchise Admin
Manager
Team Leader
Sales
Partner Agency Admin
```

Do not seed or hard-code these values unless explicitly required for testing.

The system must support future custom Position names without schema changes.

---

## Database

Create:

```text
positions
```

using a new BF011 migration.

Do not modify previous migrations.

### Staged Position Schema

The initial Position entity contains only:

* id
* organization_id
* name
* code
* status
* created_at
* updated_at

### Suggested Types

```text
id
BIGINT UNSIGNED
PRIMARY KEY
AUTO_INCREMENT
```

```text
organization_id
BIGINT UNSIGNED
NOT NULL
```

```text
name
VARCHAR(255)
NOT NULL
```

```text
code
VARCHAR(100)
NOT NULL
```

```text
status
VARCHAR(50)
NOT NULL
```

Use existing timestamp conventions for:

```text
created_at
updated_at
```

---

## Position Organization Relationship

Create:

```text
positions.organization_id
    → organizations.id
```

with a database Foreign Key.

Index:

```text
organization_id
```

A Position cannot exist without an Organization.

---

## Position Code Uniqueness

Position code uniqueness is Organization-scoped.

The same Position code may exist in different Organizations.

Example:

```text
Franchise A:
sales

Franchise B:
sales
```

is valid.

Within the same Organization:

```text
sales
sales
```

is invalid.

Create a composite unique constraint equivalent to:

```text
UNIQUE(organization_id, code)
```

Do not create global Position-code uniqueness.

---

## User Position Assignment

BF011 adds a Position relationship to:

```text
users
```

Add:

```text
position_id
```

through a separate BF011 migration.

Relationship:

```text
users.position_id
    → positions.id
```

Index:

```text
position_id
```

---

## Position Assignment Staging

`position_id` must initially be nullable.

Reason:

* Existing BF009/BF010 Users already exist without Positions.
* BF011 must not break existing User records.
* Permission enforcement is not implemented until the future Authorization milestone.

Therefore:

```text
position_id = NULL
```

is allowed during BF011.

BF012 may introduce stronger requirements after Authorization architecture is approved.

Do not make that decision inside BF011.

---

## Same-Organization Rule

A User may only be assigned to a Position belonging to the same Organization.

Required invariant:

```text
user.organization_id
=
position.organization_id
```

Examples:

```text
Franchise A User
→ Franchise A Position
VALID
```

```text
Franchise A User
→ Franchise B Position
INVALID
```

```text
Partner Agency A User
→ Parent Franchise Position
INVALID
```

```text
Partner Agency A User
→ Partner Agency A Position
VALID
```

Organization hierarchy does NOT automatically grant Position inheritance.

---

## Position Validation Rules

Validate at minimum:

* name is required
* code is required
* organization_id is required
* organization_id must be valid
* Organization must exist
* Organization must be active
* Organization must be a supported Organization type
* status is required
* status must be valid
* code must be unique within the Organization

Supported Organization types remain:

```text
system
franchise
partner_agency
```

---

## Position Status

BF011 supports:

```text
active
inactive
```

Do not use MySQL ENUM.

Do not introduce additional states without an approved requirement.

---

## Position Business Rules

### Ownership

Every Position belongs to exactly one Organization.

### Organization Immutability

A Position's:

```text
organization_id
```

must NOT be changeable through ordinary Position update.

Moving a Position between Organizations is not supported.

If the client attempts to change `organization_id`, reject the request.

Do not silently ignore it.

### Dynamic Names

Position names and codes are configurable.

Do not hard-code business roles in PHP.

### Permissions Boundary

A Position does not contain permission rules in BF011.

Do NOT add fields such as:

```text
permissions
can_create_listing
can_approve
is_admin
role
access_level
```

Permissions belong to BF012.

---

## Position API Endpoints

Implement:

```text
GET    /positions
GET    /positions/{id}
POST   /positions
PUT    /positions/{id}
DELETE /positions/{id}
```

All Position endpoints require BF010 authentication.

Authorization is not yet implemented.

---

## Position List

```text
GET /positions
```

returns active Positions.

Do not introduce advanced filtering/search infrastructure unless required by existing architecture.

If Organization filtering is needed for testing or current basic administration, support only the minimum clean mechanism compatible with current API conventions.

Do not implement permission-aware visibility.

---

## Position Show

```text
GET /positions/{id}
```

returns an active Position.

Nonexistent or inactive Positions follow existing not-found conventions.

---

## Position Create

```text
POST /positions
```

must:

1. Validate input.
2. Validate Organization.
3. Require active Organization.
4. Validate Organization type.
5. Validate Organization-scoped code uniqueness.
6. Create Position.

---

## Position Update

```text
PUT /positions/{id}
```

may update:

* name
* code
* status

It must NOT permit changing:

```text
organization_id
```

Organization reassignment attempts must be rejected.

---

## Delete / Deactivate

The platform-wide rule remains:

**NO HARD DELETE**

Therefore:

```text
DELETE /positions/{id}
```

must NOT physically delete the row.

DELETE means:

```text
status = inactive
```

The Position row remains in the database.

---

## Inactive Position Assignment

An inactive Position:

* must not be assignable to a User
* must not appear in normal active Position endpoints

Users already assigned to a Position that later becomes inactive must retain their `position_id`.

BF011 must NOT silently remove historical Position relationships.

BF012 will decide how inactive Positions affect Authorization.

---

## User API Integration

Extend the existing User module only as required for Position assignment.

BF011 may add support for:

```text
position_id
```

to User create/update.

Do not redesign User Management.

---

## User Creation

For BF011:

```text
position_id
```

remains optional.

If provided:

* Position must exist
* Position must be active
* Position must belong to the same Organization as the User

If omitted:

```text
position_id = NULL
```

is valid.

---

## User Update

Allow:

```text
position_id
```

to change during ordinary User update.

This is Position reassignment, NOT Organization transfer.

Valid behavior:

```text
Sales
→ Team Leader
```

within the same Organization.

Invalid behavior:

```text
Partner Agency A Position
→ Franchise Position
```

when the User belongs to Partner Agency A.

---

## Removing User Position

BF011 may allow:

```text
position_id = NULL
```

through User update.

This supports the staged pre-Authorization period.

Do not physically alter or delete the Position record.

---

## Organization Transfer Protection

BF009's existing rule remains unchanged:

```text
users.organization_id
```

cannot be changed through normal User update.

Position reassignment must NOT provide a way to bypass that rule.

---

## Authentication

All BF011 business endpoints require authentication through BF010.

Do not modify Authentication behavior except where integration requires the minimum existing protected-route registration.

---

## Authorization Boundary

BF011 does NOT decide:

* who may create Positions
* who may edit Positions
* who may assign Positions
* who may manage Users
* who may access another Organization's Positions

These are Authorization rules and belong to BF012.

BF011 establishes data integrity only.

---

## Repository

Create:

```text
PositionRepository
```

extending:

```text
BaseRepository
```

Responsibilities:

* active Position retrieval
* Position lookup
* Position creation
* Position updates
* Position deactivation
* Organization-scoped code lookup
* persistence operations

No business rules belong in Repository.

---

## Service

Create:

```text
PositionService
```

Responsibilities:

* Position validation
* Organization validation
* Organization ownership enforcement
* Position CRUD orchestration
* Organization immutability
* deactivation behavior

Business rules belong in Service.

---

## Controller

Create:

```text
PositionController
```

Responsibilities:

* receive HTTP requests
* call PositionService
* return standardized JSON responses

Keep Controller thin.

---

## Validator

Create:

```text
PositionValidator
```

following existing module conventions.

Reusable field validation belongs here.

Business orchestration remains in Service.

---

## Model

Create:

```text
Position
```

following existing model patterns.

Do not put Permissions or Authorization behavior in the Position model.

---

## User Module Changes

Modify User module only as necessary to support:

```text
position_id
```

including:

* persistence
* validation
* same-Organization Position validation
* active Position validation
* response representation if consistent with existing models

Do not redesign User architecture.

---

## No Position Hierarchy

BF011 does NOT implement:

```text
parent_position_id
position_level
manager_position_id
reports_to_position_id
```

Team and management hierarchy is a separate business concern.

Do not infer Team Leader relationships from Position names.

---

## No Hard-Coded Role Logic

Never write logic such as:

```text
if position == manager
if position == team_leader
if position == sales
```

inside BF011 business behavior.

Position names are business data.

Future Authorization must be permission-driven.

---

## Staged Database Policy

The broader Core architecture still includes deferred:

* ULIDs
* extended audit columns
* user_profiles
* permissions
* user_permissions
* settings
* events
* activity logs

BF011 must not expand into those concepts.

---

## Coding Standards

* PHP 8+
* `declare(strict_types=1);`
* PSR-12
* SOLID Principles
* Typed Properties
* Typed Parameters
* Typed Return Types

Preserve BF006-BF010 architecture patterns.

---

## Non-Goals

BF011 must NOT implement:

* Permissions
* Authorization
* Roles
* Position Permissions
* User Permissions
* Position hierarchy
* Team hierarchy
* Team Leaders
* Manager reporting structure
* User Profiles
* Organization Transfers
* User Transfers
* CRM
* Leads
* Properties
* Listings
* Deals
* Commissions
* Notifications
* AI
* Admin UI
* Full Core schema
* ULID migration
* Extended audit infrastructure

---

## Acceptance Criteria

### Database

* `positions` table exists
* Position belongs to Organization
* Organization FK exists
* Organization index exists
* `(organization_id, code)` is unique
* `users.position_id` exists
* Position FK exists
* Position index exists
* existing Users remain valid with `position_id = NULL`
* no Permission schema introduced

### Position Create

PASS when:

* valid System Position created
* valid Franchise Position created
* valid Partner Agency Position created
* same code used in different Organizations

Reject:

* missing name
* missing code
* missing organization
* invalid Organization ID
* nonexistent Organization
* inactive Organization
* invalid status
* duplicate code inside same Organization

### Position Read

* active Position list works
* active Position show works
* inactive Position hidden
* nonexistent Position handled correctly

### Position Update

* name update works
* code update works
* status update works
* duplicate Organization-scoped code rejected
* organization_id change rejected

### Position Delete

* DELETE deactivates Position
* row remains in database
* status becomes inactive
* inactive Position hidden from active endpoints
* repeated deactivate follows existing conventions

### User Assignment

* User can be created without Position
* valid same-Organization Position assignment works
* valid same-Organization Position reassignment works
* Position may be cleared to NULL
* nonexistent Position rejected
* inactive Position rejected
* different-Organization Position rejected
* Organization transfer remains rejected

### Historical Relationship

* deactivating Position does not automatically clear existing User `position_id`
* Position row remains available in database

### Authentication

* Position APIs reject missing authentication
* Position APIs accept valid authentication

### Regression

Verify authenticated:

* Organization
* Franchise
* Partner Agency
* Users
* Authentication login/logout/me
* health endpoint
* Router behavior
* standardized JSON response behavior

### Scope

Verify no implementation of:

* Authorization
* Permissions
* Roles
* Position hierarchy
* User Permissions
* Team hierarchy
* future modules

---

## Testing

Run database-backed BF011 acceptance testing.

Use temporary:

* Organizations
* Users
* Positions
* Authentication credentials/tokens

Clean all temporary test data after verification.

If no automated test framework exists:

* do not introduce one only for BF011
* use current API/database verification
* report automated coverage as unavailable

---

## Documentation

After successful implementation and verification, update only BF011-required project documentation.

Where applicable update:

* `AI_CONTEXT.md`
* `PROJECT_STATUS.md`
* `README.md`
* `CHANGELOG.md`
* `docs/MASTER_INDEX.md`
* `docs/database/DATABASE_CHANGELOG.md`
* `docs/database/schema/core.md`
* `docs/development/CURRENT_SYSTEM_STATE.md`
* `docs/development/IMPLEMENTATION_PLAN.md`
* `docs/development/SPRINT_LOG.md`

Record:

* BF011 completion
* Dynamic Positions
* Organization ownership
* User Position assignment
* staged nullable `position_id`
* same-Organization rule
* Position no-hard-delete behavior
* Permissions/Authorization deferral
* verification results

Do not select or design BF012 during implementation.

---

## Deliverables

BF011 deliverables are limited to:

* Position module
* `positions` migration
* `users.position_id` migration
* Position API
* Position validation
* User Position assignment integration
* Position deactivation
* Authentication integration
* BF011 acceptance verification
* required documentation updates

---

## Stop Condition

Stop immediately after BF011 implementation and verification.

Do not implement:

* Permissions
* Authorization
* Roles
* Position hierarchy
* User Permissions
* CRM
* Admin UI
* future modules

Do not select BF012.