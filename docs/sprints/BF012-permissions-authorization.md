# BF012 - Permissions & Authorization

## Status

**APPROVED DEVELOPMENT SPRINT**

Milestone: **Milestone 2 - Core Business**

Depends on:

* BF006 - Organization Management
* BF007 - Franchise Management
* BF008 - Partner Agency Management
* BF009 - User Management
* BF010 - Authentication Foundation
* BF011 - Dynamic Positions

---

## Objective

Implement the first reusable Authorization layer of the Directors Resale Platform.

BF010 answered:

```text
Who is the current User?
```

BF012 answers:

```text
What is this User allowed to do?
```

and:

```text
Within which Organization scope may the User do it?
```

Authorization must be based on:

```text
Authenticated User
        ↓
Assigned Position
        ↓
Position Permissions
        ↓
Organization Scope
        ↓
Business Action
```

BF012 must not hard-code business roles such as:

* Super Admin
* Franchise Admin
* Manager
* Team Leader
* Sales
* Partner Agency Admin

Those names may exist as dynamic Positions, but backend authorization must depend on permission codes and Organization scope.

---

## Background

The platform currently provides:

* Organization hierarchy
* Franchise Management
* Partner Agency Management
* User Management
* Authentication
* Dynamic Positions
* User-to-Position assignment

The implemented hierarchy is:

```text
System Organization
        ↓
    Franchise
        ↓
 Partner Agency
```

Users belong to one Organization.

Positions belong to one Organization.

Users may be assigned to one Position from the same Organization.

BF012 introduces reusable permission-based authorization over this foundation.

---

## Core Authorization Principle

Authorization requires BOTH:

```text
Permission
+
Organization Scope
```

Having a permission alone does not allow cross-organization access.

Being inside an Organization hierarchy alone does not grant an action.

Example:

```text
User has users.update
+
Target User is inside allowed Organization scope
=
AUTHORIZED
```

Example:

```text
User has users.update
+
Target User belongs to unrelated Franchise
=
FORBIDDEN
```

Example:

```text
Target belongs to same Organization
+
User does not have users.update
=
FORBIDDEN
```

---

## Architecture Decision

BF012 uses:

```text
Permission Catalog
        ↓
Position Permissions
        ↓
Users inherit permissions through Position
```

The staged model is:

```text
users
  ↓ position_id
positions
  ↓
position_permissions
  ↓
permissions
```

BF012 does NOT implement direct User permission overrides.

Do not create special-case role logic.

---

## Permission Catalog

Permissions represent technical business capabilities implemented by the backend.

Examples:

```text
organizations.view
organizations.create
organizations.update
organizations.archive

franchises.view
franchises.create
franchises.update
franchises.archive

partner_agencies.view
partner_agencies.create
partner_agencies.update
partner_agencies.archive

users.view
users.create
users.update
users.archive

positions.view
positions.create
positions.update
positions.archive

permissions.view
permissions.assign
```

Permission codes are controlled platform capabilities.

They are NOT arbitrary user-created labels.

Do not implement dynamic creation of unknown permission codes through the API.

Adding a new permission code must correspond to an actual backend capability.

---

## Positions vs Permissions

Positions remain dynamic business data.

Examples:

```text
Manager
Sales
Team Leader
Franchise Admin
```

Permissions define capabilities.

Example:

```text
Position: Manager

Permissions:
users.view
users.update
positions.view
```

Do NOT implement:

```php
if ($position->name === 'Manager') {
    ...
}
```

Do NOT infer permissions from Position name or code.

---

## Database

BF012 introduces the minimum persistence required for Permission assignment.

### Table: permissions

Create:

```text
permissions
```

Suggested staged columns:

* id
* code
* name
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
code
VARCHAR(150)
NOT NULL
UNIQUE
```

```text
name
VARCHAR(255)
NOT NULL
```

```text
status
VARCHAR(50)
NOT NULL
```

Use existing timestamp conventions.

---

## Permission Status

Supported statuses:

```text
active
inactive
```

Do not use MySQL ENUM.

Inactive Permissions must not authorize actions.

---

## Table: position_permissions

Create:

```text
position_permissions
```

The table represents the many-to-many relationship:

```text
Position
    ↕
Permission
```

At minimum:

* id
* position_id
* permission_id
* created_at

Foreign Keys:

```text
position_id
→ positions.id
```

```text
permission_id
→ permissions.id
```

Indexes must support permission resolution efficiently.

Create a unique constraint equivalent to:

```text
UNIQUE(position_id, permission_id)
```

Duplicate assignments must not exist.

---

## No Direct User Permissions

BF012 does NOT create:

```text
user_permissions
```

or equivalent direct User overrides.

Reason:

The first authorization model should remain predictable:

```text
User
→ Position
→ Permissions
```

Direct allow/deny overrides add precedence complexity and should only be introduced through a future explicit architecture decision if a real business requirement appears.

Do not silently implement them.

---

## Permission Seed Strategy

Permission codes correspond to backend capabilities.

BF012 must establish the approved permission catalog for the currently implemented business modules.

Use the existing project seed/migration conventions after inspecting the repository.

Do not depend on manual database inserts for normal project operation.

Do not create runtime CRUD for arbitrary permission-code creation.

At minimum seed active permission codes for:

### Organizations

```text
organizations.view
organizations.create
organizations.update
organizations.archive
```

### Franchises

```text
franchises.view
franchises.create
franchises.update
franchises.archive
```

### Partner Agencies

```text
partner_agencies.view
partner_agencies.create
partner_agencies.update
partner_agencies.archive
```

### Users

```text
users.view
users.create
users.update
users.archive
```

### Positions

```text
positions.view
positions.create
positions.update
positions.archive
```

### Permissions Administration

```text
permissions.view
permissions.assign
```

Permission codes must remain globally unique.

---

## Permission Lifecycle

Permissions represent backend capabilities and should not normally be deleted.

The project-wide:

**NO HARD DELETE**

principle applies.

If Permission lifecycle operations are needed internally, use status.

BF012 does not need public Permission deletion.

---

## Permission API

Implement:

```text
GET /permissions
```

The endpoint returns the active Permission catalog.

Requires:

```text
permissions.view
```

Do NOT implement:

```text
POST /permissions
PUT /permissions/{id}
DELETE /permissions/{id}
```

in BF012.

Permission definitions are platform-controlled.

---

## Position Permission API

Implement:

```text
GET /positions/{id}/permissions
PUT /positions/{id}/permissions
```

### GET

Returns permissions assigned to the Position.

Requires:

```text
permissions.view
```

and valid Organization scope for the Position.

### PUT

Replaces/synchronizes the Position's Permission assignments.

Requires:

```text
permissions.assign
```

and valid Organization scope for the Position.

Request should use Permission identifiers or codes according to the cleanest existing API convention.

Do not allow unknown or inactive Permissions.

Do not allow duplicate assignments.

---

## Assignment Semantics

Updating Position Permissions should be atomic.

The final database state must match the requested valid Permission set.

Do not leave partial assignments if validation fails.

Use the existing transaction infrastructure if available.

---

## Bootstrap Problem

At the beginning of BF012 no Position has Permission assignments.

Therefore normal API authorization cannot bootstrap itself.

Provide a narrow development/administrative bootstrap mechanism consistent with the BF010 credential bootstrap philosophy.

The bootstrap mechanism may assign the required initial Permissions to an existing Position.

Requirements:

* CLI/internal only
* not public HTTP
* explicit operator action
* no default password behavior
* no hidden hard-coded Position name
* no automatic super-admin Position creation

Example conceptual usage:

```text
assign all current permissions to Position ID X
```

or an equivalent explicit command.

The exact CLI interface may follow existing project conventions.

After bootstrap, normal Permission assignment must use the authorized API.

---

## Authorization Service

Create a reusable Authorization Service.

Responsibilities:

* resolve authenticated User
* resolve assigned active Position
* resolve active Position Permissions
* check permission code
* evaluate Organization scope
* return authorization decision

Do not duplicate permission logic across controllers.

---

## Authorization Middleware

Introduce reusable Authorization middleware/integration over BF010 Authentication.

Conceptual flow:

```text
Request
  ↓
Authentication
  ↓
Authorization Permission Check
  ↓
Organization Scope Check
  ↓
Controller
```

Do not redesign Router architecture.

Reuse the existing middleware/wrapper approach introduced by BF010 where practical.

---

## HTTP Semantics

Use:

```text
401 Unauthorized
```

when authentication is missing or invalid.

Use:

```text
403 Forbidden
```

when the User is authenticated but lacks:

* required Permission
* valid Organization scope

Do not return 401 for permission failures.

Use standardized JSON responses.

---

## User Authorization Eligibility

A User may authorize business actions only when:

* User is active
* User has an active Position
* Position belongs to the User's Organization
* Position is active
* required Permission is active
* Position has the required Permission
* requested resource is within Organization scope

If:

```text
position_id = NULL
```

the User has no business permissions.

Authentication may still succeed.

`/auth/me` remains available to an authenticated User.

---

## Inactive Position

If a User's assigned Position becomes inactive:

* Authentication remains valid if User itself is active.
* Business authorization fails.
* User does not inherit permissions from inactive Position.

Do not automatically clear `users.position_id`.

Historical relationship remains preserved.

---

## Organization Scope

Authorization scope follows the approved Organization hierarchy.

### System Organization User

A User belonging to the System Organization may operate across:

```text
System Organization
Franchises
Partner Agencies
```

BUT only for actions covered by assigned Permissions.

System membership does not automatically grant all Permissions.

---

## Franchise User Scope

A User belonging to a Franchise may operate within:

```text
Their own Franchise
+
Partner Agencies directly belonging to that Franchise
```

subject to assigned Permissions.

They must NOT access:

* System Organization data
* another Franchise
* Partner Agencies belonging to another Franchise

---

## Partner Agency User Scope

A User belonging to a Partner Agency may operate only within:

```text
Their own Partner Agency
```

subject to assigned Permissions.

They must NOT access:

* System Organization data
* parent Franchise business data
* sibling Partner Agencies
* another Franchise
* another Partner Agency

unless a future explicit architecture decision introduces broader rules.

---

## Organization Scope Is Not Permission Inheritance

Example:

A Franchise User with:

```text
partner_agencies.view
```

may view Partner Agencies belonging to their Franchise.

A Franchise User without:

```text
partner_agencies.view
```

may not view them.

Hierarchy determines scope.

Permission determines capability.

Both are required.

---

## Scope Resolution

Implement reusable Organization scope resolution.

Do not duplicate hierarchy checks independently inside every module.

The authorization layer should be able to determine whether:

```text
actor_organization_id
```

may act on:

```text
target_organization_id
```

according to the approved hierarchy.

Reuse the existing `organizations.parent_organization_id`.

Do not create additional hierarchy columns.

---

## List Endpoint Scope

Authorization must prevent information leakage in list endpoints.

Permission middleware alone is not enough.

Examples:

### System User

```text
GET /users
```

with `users.view` may return Users across authorized platform scope.

### Franchise User

```text
GET /users
```

with `users.view` must return only Users belonging to:

* the Franchise
* its Partner Agencies

### Partner Agency User

```text
GET /users
```

with `users.view` must return only Users belonging to that Partner Agency.

Equivalent scope filtering applies where relevant to:

* Organizations
* Franchises
* Partner Agencies
* Positions
* Users

Do not fetch all records and expose unauthorized data.

---

## Resource Endpoint Scope

For:

```text
GET /resource/{id}
PUT /resource/{id}
DELETE /resource/{id}
```

authorization must verify:

1. required Permission
2. target resource Organization
3. target Organization lies within actor scope

An authenticated User with the correct Permission must still receive:

```text
403 Forbidden
```

for an out-of-scope resource.

Follow established not-found behavior where necessary to avoid leaking sensitive existence information, but keep the authorization behavior consistent and documented.

---

## Create Endpoint Scope

For create operations:

* validate required Permission
* inspect target `organization_id` or hierarchy context
* confirm target Organization lies inside actor's scope
* only then allow business creation

Example:

Franchise User with:

```text
users.create
```

may create User under:

* own Franchise
* child Partner Agency

They may NOT create User under another Franchise.

---

## Organization Endpoint Authorization

Protect existing Organization routes with:

```text
organizations.view
organizations.create
organizations.update
organizations.archive
```

according to HTTP action.

Scope rules must still apply.

Do not redesign Organization business logic.

---

## Franchise Endpoint Authorization

Protect:

```text
GET    /franchises
GET    /franchises/{id}
POST   /franchises
PUT    /franchises/{id}
DELETE /franchises/{id}
```

using:

```text
franchises.view
franchises.create
franchises.update
franchises.archive
```

respectively.

Because Franchise entities are direct children of the System Organization:

* System-scoped authorized Users may manage applicable Franchises.
* Franchise-scoped Users may view/manage their own Franchise only where the permission and operation make architectural sense.
* Franchise Users must never act on another Franchise.
* Partner Agency Users must not gain Franchise management scope.

Do not hard-code Position names.

---

## Partner Agency Endpoint Authorization

Protect using:

```text
partner_agencies.view
partner_agencies.create
partner_agencies.update
partner_agencies.archive
```

System Users with permission may operate platform-wide.

Franchise Users with permission may operate on child Partner Agencies.

Partner Agency Users with permission may operate only on their own Partner Agency where the endpoint action is applicable.

---

## User Endpoint Authorization

Protect using:

```text
users.view
users.create
users.update
users.archive
```

Scope must follow Organization hierarchy.

A User must never use authorization to bypass BF009's Organization-transfer protection.

Changing:

```text
users.organization_id
```

remains prohibited by normal User update.

---

## Position Endpoint Authorization

Protect using:

```text
positions.view
positions.create
positions.update
positions.archive
```

Position Organization scope must match the actor's allowed hierarchy.

Existing BF011 same-Organization User/Position assignment integrity remains unchanged.

---

## Authentication Endpoints

These remain governed by BF010:

```text
POST /auth/login
POST /auth/logout
GET  /auth/me
```

### Login

Public.

### Logout

Authentication required.

No additional business Permission required.

### Me

Authentication required.

No additional business Permission required.

The authenticated User must be able to inspect their own basic identity even if no Position or Permission is assigned.

---

## Health Endpoint

Remain public:

```text
GET /api/v1/health
```

Do not add Authentication or Authorization.

---

## Authorization Does Not Change Business Validation

Authorization decides whether the actor may attempt an action.

Existing Services continue to enforce business validity.

Flow:

```text
Authentication
↓
Authorization
↓
Business Validation
↓
Persistence
```

Do not move existing business rules into Authorization middleware.

---

## Permission Assignment Scope

A User with:

```text
permissions.assign
```

may modify Position Permission assignments only for Positions inside their allowed Organization scope.

System authorized User:

* any Position in platform scope

Franchise authorized User:

* own Franchise Positions
* child Partner Agency Positions

Partner Agency authorized User:

* own Partner Agency Positions

subject to permission.

---

## Self-Escalation Protection

Authorization administration must not become an accidental privilege escalation path.

BF012 must prevent unauthorized Users from granting permissions outside their scope.

Permission assignment requires:

```text
permissions.assign
```

and Organization scope.

Do not infer that merely owning a Position allows editing its Permissions.

---

## Permission Catalog Visibility

`GET /permissions` exposes technical capability metadata only to Users with:

```text
permissions.view
```

Do not expose internal implementation details beyond:

* id/code
* display name
* status as appropriate

---

## Authorization Caching

Do not introduce a complex distributed Permission cache in BF012.

Correctness is more important than premature caching.

The architecture should remain cacheable later.

---

## Repository Layer

Create only repositories necessary for:

* Permission catalog access
* Position Permission persistence
* efficient permission resolution

Repositories remain persistence-only.

Do not put authorization decisions in repositories.

---

## Service Layer

Create appropriate services for:

* Permission catalog access
* Position Permission synchronization
* Authorization decisions
* Organization scope resolution

Keep responsibilities separated without unnecessary abstraction.

---

## Controller Layer

Create only controllers required for Permission catalog and Position Permission administration.

Controllers remain thin.

Authorization enforcement must be reusable and not manually duplicated in controllers.

---

## Dependency Injection

Register Authorization dependencies through the existing Service Container.

Use constructor injection.

Do not introduce globals or static permission state.

---

## Existing Authentication Context

Reuse BF010 authenticated User context.

Do not parse Bearer tokens again inside Authorization.

BF010 Authentication remains the source of authenticated identity.

---

## Staged Audit Limitation

Permission assignment is a sensitive operation.

The broader architecture requires auditability, but the full Activity Log/Event infrastructure is not implemented yet.

BF012 must:

* preserve permission assignment data
* avoid hard deletion where history matters
* document the missing full audit trail as a staged dependency

Do not build a partial generic Activity Log engine inside BF012 unless already required by current infrastructure.

A dedicated Core audit milestone may complete this later.

---

## No Hard Delete

Do not hard-delete:

* Permission definitions as lifecycle behavior
* business Position records

Position Permission synchronization may remove relationship rows because those rows represent the current assignment set, not the historical Position or Permission business records.

Full Permission assignment history is deferred to the audit/event architecture.

Document this staged limitation.

---

## Coding Standards

* PHP 8+
* `declare(strict_types=1);`
* PSR-12
* SOLID Principles
* Typed Properties
* Typed Parameters
* Typed Return Types

Preserve BF001-BF011 patterns.

---

## Non-Goals

BF012 must NOT implement:

* hard-coded Roles
* direct User Permission overrides
* `user_permissions`
* Team hierarchy
* Team Leader reporting
* Manager reporting hierarchy
* Position hierarchy
* Field-level permissions
* Approval workflows
* Activity Log engine
* Event engine
* User transfers
* CRM
* Properties
* Listings
* Deals
* Commissions
* Notifications
* AI
* Admin UI
* Frontend
* API keys
* OAuth
* MFA

---

## Acceptance Criteria

### Database

* `permissions` exists
* Permission code is globally unique
* `position_permissions` exists
* Position FK exists
* Permission FK exists
* duplicate Position/Permission assignment prevented
* no direct User permission table introduced
* existing Position/User schema remains valid

### Permission Catalog

* current approved Permission codes are seeded
* active Permission list works
* unknown Permission assignment rejected
* inactive Permission assignment rejected
* arbitrary Permission creation API does not exist

### Position Permission Assignment

* valid Permission assignment works
* multiple Permissions work
* replacing/synchronizing set works
* duplicate input handled safely
* invalid Permission rejected
* inactive Permission rejected
* out-of-scope Position rejected
* API requires `permissions.assign`

### Bootstrap

* explicit internal/CLI bootstrap works
* no Position name is hard-coded
* no automatic super-admin account/Position created
* bootstrap does not expose public HTTP escalation path

### Permission Enforcement

Authenticated User without required Permission:

```text
403
```

Authenticated User with required Permission and valid scope:

```text
allowed
```

Missing/invalid Authentication:

```text
401
```

### Position Eligibility

* User without Position has no business authorization
* inactive Position grants no permissions
* active Position grants assigned active Permissions
* inactive Permission grants no capability

### Scope - System User

With appropriate Permission:

* System Organization scope works
* Franchise scope works
* Partner Agency scope works

Without Permission:

* action rejected

### Scope - Franchise User

With appropriate Permission:

* own Franchise accessible
* child Partner Agencies accessible
* Users/Positions in own Franchise scope accessible
* Users/Positions in child Partner Agencies accessible

Rejected:

* System Organization
* other Franchise
* other Franchise's Partner Agency
* unrelated Users/Positions

### Scope - Partner Agency User

With appropriate Permission:

* own Partner Agency accessible
* own Users accessible
* own Positions accessible

Rejected:

* parent Franchise
* sibling Partner Agency
* other Franchise
* unrelated Users/Positions

### List Isolation

Verify scoped results for:

* Organizations
* Franchises
* Partner Agencies
* Users
* Positions

No unauthorized records may leak through lists.

### Create Scope

Verify Organization-scoped create restrictions for applicable existing modules.

### Update/Delete Scope

Verify out-of-scope resource mutation is rejected.

### Authentication Regression

* login works
* logout works
* me works
* invalid token rejected
* health remains public

### Business Regression

Verify authorized BF006-BF011 behavior:

* Organizations
* Franchises
* Partner Agencies
* Users
* Positions

### Scope Control

Verify no implementation of:

* direct User permission overrides
* hard-coded Roles
* Team hierarchy
* Position hierarchy
* future modules

---

## Testing

Run database-backed BF012 acceptance testing.

Create temporary:

* System Organization
* multiple Franchises
* multiple Partner Agencies
* Users
* Positions
* Permission assignments
* Authentication credentials/tokens

The test topology must include at least two independent Franchise branches so cross-organization isolation can be proven.

Example:

```text
System
├── Franchise A
│   ├── Partner Agency A1
│   └── Partner Agency A2
└── Franchise B
    └── Partner Agency B1
```

Test Users at:

* System
* Franchise A
* Franchise B
* Partner Agency A1
* Partner Agency B1

Verify both:

```text
permission allowed
```

and:

```text
scope denied
```

cases.

Clean all temporary data after verification.

If no automated test framework exists:

* do not introduce one solely for BF012
* run current API/database verification
* report automated suite as unavailable

---

## Regression

Run authenticated and authorized regression checks for BF006-BF011.

Do not weaken earlier business validation merely to make Authorization tests pass.

---

## Documentation

After successful implementation and verification, update only BF012-required documentation.

Where applicable:

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
* relevant security/API documentation

Record:

* BF012 completion
* Permission catalog
* Position Permission model
* Authorization architecture
* Organization scope rules
* 401 vs 403 semantics
* list isolation
* bootstrap mechanism
* no direct User permissions
* no hard-coded Roles
* staged audit limitation
* verification results

Do not select or design another Backend business sprint during BF012 implementation.

---

## Deliverables

BF012 deliverables are limited to:

* Permission catalog persistence
* Position Permission persistence
* Permission seed/bootstrap
* Permission catalog endpoint
* Position Permission administration endpoints
* reusable Authorization Service
* reusable Organization Scope resolver
* reusable authorization middleware/integration
* authorization of existing BF006-BF011 routes
* scoped list/resource/create behavior
* BF012 acceptance testing
* regression verification
* required documentation updates

---

## Stop Condition

Stop immediately after BF012 is implemented and verified.

Do not implement:

* Admin UI
* Frontend
* CRM
* Properties
* Team hierarchy
* User Permission overrides
* any future business module

Do not select the next Backend sprint.

BF012 completion should leave the backend ready for the separately approved Admin UI Foundation planning phase.