# BF009 - User Management

## Status

**APPROVED DEVELOPMENT SPRINT**

Milestone: **Milestone 2 - Core Business**

Depends on:

* BF006 - Organization Management
* BF007 - Franchise Management
* BF008 - Partner Agency Management

---

## Objective

Implement the first User Management layer of the Directors Resale Platform.

BF009 introduces internal platform users and connects every user to exactly one Organization.

The purpose of this sprint is to establish the Core User entity without prematurely implementing Authentication, Positions, Roles, Permissions, User Profiles, or Transfer workflows.

The sprint must preserve the existing Organization hierarchy:

```text
System Organization
        ↓
    Franchise
        ↓
 Partner Agency
        ↓
      Users
```

Users may belong directly to an appropriate System, Franchise, or Partner Agency Organization.

---

## Background

The platform currently provides:

* Application Bootstrap
* Dependency Injection
* Database Layer
* Query Builder
* Base Repository
* HTTP Kernel
* Router
* Standardized JSON Responses
* Organization Management
* Franchise Management
* Partner Agency Management

The Organization hierarchy is already implemented using the existing:

```text
organizations
```

table.

BF009 introduces the first standalone Core business table after the staged Organization hierarchy.

The Core architecture owns:

* Organizations
* Users
* Positions
* Permissions
* Settings
* Events
* Audit

BF009 implements only the **Users** portion of that Core scope.

---

## Scope

Implement complete staged User Management.

Suggested structure:

```text
app/
└── Modules/
    └── User/
        ├── Controllers/
        ├── Services/
        ├── Repositories/
        ├── Models/
        └── Validators/
```

Only create classes required by the current architecture.

Avoid unnecessary abstractions.

Follow the patterns established by BF006-BF008.

---

## User

A User represents an internal platform user who operates within one Organization.

Examples of future users may include:

* System staff
* Franchise staff
* Partner Agency staff
* Sales users
* Managers
* Team Leaders

BF009 does NOT implement those job roles.

Job position, role, permission, hierarchy, and team behavior are deferred.

BF009 establishes only the shared User identity and Organization ownership.

---

## Organization Ownership

Every User must belong to exactly one Organization.

The relationship is represented by:

```text
organization_id
```

referencing:

```text
organizations.id
```

A valid User Organization may be:

```text
system
franchise
partner_agency
```

The Organization must exist.

The Organization must be active according to the current staged Organization status rules.

Users may not exist without an Organization.

---

## Organization Transfer Rule

Changing a User from one Organization to another is NOT ordinary User editing.

The architecture includes a future Transfer Engine responsible for User transfers and preserving transfer history.

Therefore:

```text
organization_id
```

must be immutable through the normal BF009 User update endpoint.

Once a User is created under an Organization, ordinary:

```text
PUT /users/{id}
```

must not permit changing `organization_id`.

Future movement between Organizations must be implemented by the dedicated Transfer workflow.

BF009 must not implement that Transfer workflow.

---

## Database

Create a new:

```text
users
```

table.

Use a new BF009 migration.

Do not modify previous Organization migrations.

### Staged User Schema

The initial BF009 User entity should contain only:

* id
* organization_id
* full_name
* email
* phone
* status
* created_at
* updated_at

No additional User fields should be introduced without a requirement in this sprint.

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
full_name
VARCHAR(255)
NOT NULL
```

```text
email
VARCHAR(255)
NOT NULL
```

```text
phone
VARCHAR(50)
NULL
```

```text
status
VARCHAR(50)
NOT NULL
```

```text
created_at
TIMESTAMP
```

```text
updated_at
TIMESTAMP
```

Follow existing project timestamp conventions.

---

## Foreign Keys

Create:

```text
users.organization_id
    → organizations.id
```

The relationship must use a database Foreign Key.

Index:

```text
organization_id
```

---

## Unique Constraints

User email must be globally unique in BF009.

Create:

```text
UNIQUE(email)
```

Do not introduce Organization-scoped duplicate email identities.

Phone uniqueness is NOT required by BF009.

---

## Staged Database Policy

The broader Core architecture includes future concepts such as:

* ULID/public identifiers
* User Profiles
* Positions
* Permissions
* Audit-user relationships
* extended lifecycle fields

These remain deferred.

BF009 must NOT implement:

* user_profiles
* positions
* permissions
* user_permissions
* authentication tokens
* password credentials
* login sessions

Do not expand BF009 into the complete future Core schema.

The staged schema must be clearly documented as an incremental implementation.

---

## Audit Bootstrap Limitation

The global database architecture expects audit relationships to Users.

BF009 is the milestone that first creates the `users` table itself.

Do not introduce circular or incomplete audit infrastructure merely to satisfy the future target architecture.

The existing staged-schema exception remains in effect.

Full audit relationships will be introduced through an explicitly approved future architecture milestone.

---

## User Status

Use the existing shared status strategy.

BF009 supports at minimum:

```text
active
inactive
```

Do not use MySQL ENUM.

Status validation belongs in the backend.

Additional future states such as suspended may be introduced only through an approved later requirement.

---

## Validation Rules

Validate at minimum:

* full_name is required
* email is required
* email must be valid
* email must be unique
* organization_id is required
* organization_id must be a valid integer identifier
* Organization must exist
* Organization must be active
* Organization must be a supported Organization type
* status is required
* status must be allowed by BF009
* phone is optional
* organization_id cannot be changed through ordinary update

Validation should remain reusable.

---

## Business Rules

### Organization Ownership

Every User belongs to exactly one Organization.

A User cannot exist without an Organization.

### Supported Organizations

Users may belong to:

* System Organization
* Franchise
* Partner Agency

The User module must not duplicate Organization hierarchy logic.

It should validate ownership using the existing Organization data.

### Email Identity

Email is the unique User identity field for BF009.

Authentication is NOT implemented yet.

The existence of an email does not imply that login infrastructure exists.

### Organization Transfer

Ordinary User update must not change:

```text
organization_id
```

Moving a User between Organizations belongs to the future Transfer Engine.

### No Hard Delete

Users are business records.

Users must never be physically deleted.

DELETE means staged deactivation.

---

## API Endpoints

Implement:

```text
GET    /users
GET    /users/{id}
POST   /users
PUT    /users/{id}
DELETE /users/{id}
```

Return standardized JSON responses using the existing infrastructure.

---

## List Behavior

```text
GET /users
```

must return active Users according to the current staged lifecycle convention.

Inactive Users should not appear in the normal active User list.

Do not introduce pagination, advanced searching, team filtering, Organization reporting, or permission-aware filtering unless already required by the current infrastructure.

---

## Show Behavior

```text
GET /users/{id}
```

must return an active User.

Requests for:

* nonexistent User
* inactive User

must follow the existing not-found API convention.

---

## Create Behavior

```text
POST /users
```

must:

1. Validate input.
2. Validate Organization ownership.
3. Validate Organization existence.
4. Validate Organization status.
5. Validate supported Organization type.
6. Validate global email uniqueness.
7. Create the User.

The API must not create:

* Position
* Permission
* Role
* User Profile
* Authentication credentials

as side effects.

---

## Update Behavior

```text
PUT /users/{id}
```

may update:

* full_name
* email
* phone
* status

subject to validation.

It must NOT allow:

```text
organization_id
```

to change.

If the payload attempts to change the Organization, reject the request with the existing validation/error convention.

Do not silently ignore the attempted transfer.

---

## Delete / Archive Behavior

The platform-wide rule is:

**NO HARD DELETE**

Therefore:

```text
DELETE /users/{id}
```

must not physically remove the User row.

DELETE means:

```text
deactivate
```

by setting:

```text
status = inactive
```

The database row must remain.

Inactive Users must follow the same staged visibility behavior used by BF007/BF008.

---

## Repository

Create:

```text
UserRepository
```

The repository must extend:

```text
BaseRepository
```

Responsibilities:

* retrieve active Users
* find active User by ID
* create Users
* update Users
* deactivate Users
* query email uniqueness
* perform database access required by the User module

Do not place business rules in the Repository.

Avoid custom SQL unless technically required by the current database infrastructure.

---

## Service

Create:

```text
UserService
```

Responsibilities:

* User business validation
* Organization ownership validation
* User CRUD orchestration
* email uniqueness enforcement
* Organization immutability enforcement
* deactivation behavior
* Repository coordination

Business logic must remain in the Service.

No HTTP or routing logic belongs in the Service.

---

## Controller

Create:

```text
UserController
```

Responsibilities:

* receive HTTP requests
* obtain request input using existing conventions
* call UserService
* return standardized JSON responses

Controllers must remain thin.

No User business rules inside the Controller.

---

## Validator

Create:

```text
UserValidator
```

if consistent with the established module pattern.

Responsibilities should remain limited to reusable input validation.

Business orchestration belongs in UserService.

---

## Model

Create:

```text
User
```

following the existing module model conventions.

The model represents the staged User entity only.

Do not add Authentication, Role, Permission, Position, or Profile behavior.

---

## Dependency Injection

Register User module dependencies using the existing Service Container.

Use:

* Dependency Injection
* Constructor Injection

Do not redesign container behavior.

---

## Routing

Register User routes using the existing Router.

Do not redesign Router architecture.

Preserve:

* Organization routes
* Franchise routes
* Partner Agency routes
* health endpoint
* Request injection behavior

---

## Authentication Boundary

Authentication is NOT implemented in BF009.

Do not create:

* password_hash
* passwords
* login endpoints
* tokens
* sessions
* refresh tokens
* OTP
* password reset
* authentication middleware

Authentication will be introduced as a separate approved milestone.

The User module must remain compatible with future authentication.

---

## Authorization Boundary

BF009 does NOT implement:

* roles
* permissions
* access-control middleware
* Organization access policies
* Team Leader visibility
* Manager visibility
* Sales permissions
* Super Admin permissions

Those business concepts require their own approved architecture.

Do not infer permissions from Organization type.

---

## Positions Boundary

Dynamic Positions are a separate planned Core feature.

BF009 must not create:

```text
positions
position_id
```

unless a separately approved decision explicitly changes this sprint.

User identity must not be coupled prematurely to a Position implementation.

---

## User Profiles Boundary

Do not create:

```text
user_profiles
```

in BF009.

Profile expansion belongs to a future sprint.

The initial User entity contains only the BF009 fields.

---

## Transfer Boundary

The future Transfer Engine owns User Organization transfers.

BF009 must not implement:

* transfer requests
* transfer approval
* transfer history
* listing movement
* old/new Organization sharing
* asset transfer

The only BF009 requirement is to prevent ordinary User updates from bypassing the future Transfer workflow.

---

## Design Principles

The User module must:

* belong to Core
* use the existing Organization entity
* enforce one Organization per User
* preserve Organization ownership
* use Dependency Injection
* use Constructor Injection
* extend BaseRepository
* use the Service Layer
* keep Controllers thin
* keep business rules in Services
* keep persistence in Repositories
* use standardized responses
* preserve historical User records
* avoid premature Authentication coupling
* avoid premature Position/Permission coupling
* remain framework-independent

---

## Coding Standards

* PHP 8+
* `declare(strict_types=1);`
* PSR-12
* SOLID Principles
* Typed Properties
* Typed Parameters
* Typed Return Types

Follow the patterns established in BF006-BF008.

---

## Non-Goals

BF009 must NOT implement:

* Authentication
* Login
* Logout
* Passwords
* Tokens
* OTP
* Authorization
* Roles
* Permissions
* Dynamic Positions
* Team hierarchy
* Team Leaders
* Managers
* Sales visibility rules
* User Profiles
* User Transfers
* Transfer History
* CRM
* People
* Leads
* Requirements
* Properties
* Listings
* Deals
* Commissions
* Notifications
* Communication
* AI
* Reporting
* Dashboards
* Frontend
* Full Core schema
* Full audit infrastructure
* ULID migration

Do not implement any future module while completing BF009.

---

## Acceptance Criteria

### Database

* `users` table exists.
* `organization_id` references `organizations.id`.
* `organization_id` is indexed.
* `email` is unique.
* No password field exists.
* No position field exists.
* No permissions fields exist.
* No user_profiles table is introduced.
* No unnecessary future schema is introduced.

### Create

* valid User creation works for active System Organization
* valid User creation works for active Franchise
* valid User creation works for active Partner Agency
* missing full_name rejected
* missing email rejected
* invalid email rejected
* duplicate email rejected
* missing organization rejected
* invalid organization ID rejected
* nonexistent Organization rejected
* inactive Organization rejected
* invalid status rejected

### Read

* active User list works
* active User show works
* nonexistent User returns appropriate response
* inactive User is hidden from active endpoints

### Update

* valid full_name update works
* valid email update works
* duplicate email update rejected
* phone update works
* valid status update works
* organization_id change rejected
* invalid Organization transfer attempt does not change stored ownership

### Delete / Deactivate

* DELETE deactivates User
* User row remains in database
* status becomes inactive
* inactive User disappears from active list/show
* repeated deactivate follows existing API conventions

### Regression

* health endpoint works
* Organization functionality remains working
* Franchise functionality remains working
* Partner Agency functionality remains working
* Router behavior remains working
* existing standardized response format remains unchanged

### Scope

* no Authentication implemented
* no Authorization implemented
* no Roles implemented
* no Permissions implemented
* no Positions implemented
* no User Profiles implemented
* no Transfer workflow implemented
* no future module implemented

---

## Testing

Test all BF009 acceptance criteria.

Database-backed tests must verify actual persistence behavior.

Temporary test data must be cleaned after verification.

If no automated test framework exists:

* do not introduce one solely for BF009
* use the existing API/database verification approach
* clearly report automated tests as unavailable

Run full regression for BF006-BF008 before closing BF009.

---

## Documentation

After successful implementation and verification, update only the documentation required by the existing project workflow.

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
* relevant API/module/database registries

Record:

* BF009 completion
* User Management implementation
* `users` table
* Organization ownership
* unique email behavior
* no-hard-delete behavior
* immutable Organization ownership through normal update
* Authentication/Authorization deferral
* Positions/Permissions/User Profiles deferral
* Transfer dependency
* verification results

Do not select or design BF010 during BF009 implementation.

---

## Deliverables

BF009 deliverables are limited to:

* User module
* `users` migration
* User API endpoints
* Organization ownership validation
* email uniqueness validation
* User deactivation behavior
* Organization-transfer protection
* dependency registration
* route registration
* BF009 verification
* required documentation updates

---

## Stop Condition

Stop immediately after BF009 implementation and verification.

Do not start:

* Authentication
* Authorization
* Positions
* Permissions
* User Profiles
* Transfers
* CRM
* Property
* any future sprint

Do not select BF010.

Return BF009 implementation and verification results only.