# BF013 - Property & Ownership Foundation

## Status

**IMPLEMENTED AND VERIFIED / CLOSED**

Milestone: **Milestone 2 - Core Business**

Sprint Family: **BF - Backend / Domain / Foundation**

Depends on:

* BF006 - Organization Management
* BF007 - Franchise Management
* BF008 - Partner Agency Management
* BF009 - User Management
* BF010 - Authentication Foundation
* BF011 - Dynamic Positions
* BF012 - Permissions & Authorization
* Approved Listing Domain Architecture
* Approved Listing Physical Data Model Architecture

---

## Objective

Implement the backend/domain foundation for:

* Organization Property
* Organization-scoped Owner
* Ownership and co-ownership through Ownership Parties
* historical Authorized Acting Owner designations
* System-only Global Physical Property Identity
* historical Global Identity relationships

BF013 establishes persistent operational and historical foundations. It does not implement Listings or rich Property Profile behavior.

---

## Architecture Context

The approved architecture distinguishes:

```text
System-only Global Physical Property Identity
        |
        +-- independent Organization Property records
                |
                +-- Organization-scoped Ownership history
                        |
                        +-- Ownership Parties
                                |
                                +-- Organization Owners
                                +-- Acting Owner designation history
```

Global identity is analytical/reconciliation infrastructure. It never merges, synchronizes, transfers, or discloses Organization operational records.

Organization Property is the persistent Organization-owned operational root. BF013 creates only its foundation shell; rich profile, catalog, location, attribute, media, document, and Listing concerns remain deferred.

---

## Scope

### Organization Property

BF013 establishes:

* Organization ownership
* stable internal identity/reference according to project conventions
* human-readable operational label
* `ACTIVE` and `ARCHIVED` lifecycle
* creator attribution
* relationships required by Ownership and Global Identity history

Required operations:

* create
* get
* scoped list
* update permitted foundation fields
* archive
* reactivate

### Owner

Owner is an Organization-scoped external party, not a Platform User.

Supported party types:

* `INDIVIDUAL`
* `LEGAL_ENTITY`

The foundation supports display/legal name, optional mobile and email, optional preferred contact method, lightweight legal-entity contact-person information where appropriate, and status.

Required operations:

* create
* get
* Organization-scoped list/search
* update
* deactivate
* reactivate

### Ownership

Ownership is an Organization-scoped historical state belonging to one Organization Property. Co-ownership uses multiple Ownership Parties within one Ownership.

Required domain operations:

* record current Ownership
* retrieve current Ownership
* retrieve Ownership history
* maintain parties and shares on a `CURRENT` Ownership
* close current Ownership

These are domain operations, not a requirement for generic CRUD endpoints.

### Authorized Acting Owner

Authorized Acting Owner uses a historical designation referencing a specific Ownership Party.

Required operations:

* designate
* change by ending the previous designation and creating a new designation
* clear/end
* retrieve designation history

### Global Physical Property Identity

Global identity operations are System-only and capability-controlled.

Required operations:

* create
* get/list
* link an Organization Property
* unlink
* relink
* inspect link history

Matching and candidate-generation algorithms are not part of BF013.

---

## Locked Business Rules

### Authorized Acting Owner History

* Acting Owner is not a simple Owner foreign key on Ownership.
* A designation references an Ownership Party, not Owner directly.
* The designated party must belong to the same Ownership.
* A designation records basis/source, recording actor/time, optional notes, and start/end/current semantics.
* At most one current designation exists for an Ownership.
* Changing the Acting Owner ends the prior designation and creates another.
* A designation cannot remain active beyond its Ownership.

### Current Ownership

* Each Organization Property has zero or one `CURRENT` Ownership.
* Multiple co-owners are Ownership Parties in that single current Ownership.
* Historical Ownership states are retained.

### Ownership Parties and Shares

* Ownership Party is a first-class Ownership-to-Owner relationship.
* An Owner appears at most once within one Ownership.
* Share percentage is optional and uses decimal representation when known.
* A known share is greater than zero and no greater than 100.
* If every party share is known, the total must be 100%.
* If any share is unknown, incomplete shares do not block Ownership recording.

### Ownership Lifecycle

```text
CURRENT -> CLOSED
```

* An Organization Property has at most one current Ownership.
* Closed Ownership and its parties are historical and immutable through normal operations.
* Ownership cannot be casually reopened.
* Ownership is never hard-deleted.
* Closing does not create a buyer or replacement Ownership, mark a Listing sold, or perform transfer.
* Ownership Transfer Confirmation remains deferred to the Sale/Transfer domain.

### Global Identity Relationships

* Global identity is linked through a historical relationship, not only a mutable field on Organization Property.
* An Organization Property has zero or one active confirmed Global Identity relationship.
* One Global Physical Property Identity may relate to many Organization Properties.
* Relink ends the old link and creates a new one.
* Link history records link actor/time/method and unlink actor/time/reason.
* Unlink reason is required.
* Global linkage is optional for Organization Property creation and operation.
* Linking never merges Properties, Owners, Ownerships, or data; copies or synchronizes Organization records; transfers records; or exposes another Organization's data.

### Owner Identity

* Owner belongs to exactly one Organization.
* Mobile and email are optional matching signals, not blind or global unique identifiers.
* Duplicate assistance and search are Organization-scoped.
* Owners are not globally reconciled.
* Separate full Person and Company subsystems are not part of BF013.

### Minimum Creation Requirements

Organization Property requires:

* Organization
* creator
* human-readable operational label

Owner requires:

* Organization
* party type
* display/legal name

Ownership requires:

* an existing Organization Property
* at least one Owner in the same Organization context

System privilege never bypasses Organization consistency. Shares and Acting Owner are optional. Property, Owner, Ownership, and Acting Owner remain separate domain operations rather than one required monolithic transaction.

### Organization Property Lifecycle

```text
ACTIVE <-> ARCHIVED
```

* Property lifecycle is separate from Listing lifecycle.
* Listing states never become Organization Property states.
* Selling or withdrawing a future Listing does not archive its Property.
* Archive is reversible, audited, and never a hard delete.
* Ownership, Global Identity link history, and future Listing history remain intact.
* Future Listing integration must prevent or explicitly resolve archiving a Property with an active Listing; that integration is outside BF013.

---

## Authorization and Privacy

Every operation requires:

```text
explicit backend capability
+
valid Organization/resource scope
```

* Owner and Ownership information is private Organization-scoped operational data.
* Marketplace visibility grants no Owner or Ownership access.
* Parent Franchise supervision does not automatically expose child Partner Agency Owner PII or private Ownership information.
* Assignment alone grants no authority.
* Position names never determine authorization.
* System-wide operations require explicit System capability.
* Normal Owner search and duplicate assistance remain Organization-scoped.
* Global identity operations and data are System-only.
* Normal Organization-facing Property responses do not expose Global Identity or another Organization's record.

Implemented BF013 capabilities follow BF012 conventions:

* `properties.view`
* `properties.manage`
* `owners.view`
* `owners.manage`
* `ownerships.view`
* `ownerships.manage`
* `global_physical_identities.view`
* `global_physical_identities.manage`

Organization Property uses hierarchy scope. Owner and Ownership use `private_organization` scope. Global Physical Identity uses `system_only` scope. Pre-authorization Organization candidates remain untrusted and are promoted to `auth.target.organization_id` only after successful authorization. Unsupported generic resource target types fail explicitly.

---

## Domain Invariants

1. Organization Property belongs to exactly one Organization.
2. Owner belongs to exactly one Organization.
3. Ownership belongs to exactly one Organization Property.
4. An Organization Property has at most one `CURRENT` Ownership.
5. Ownership has at least one Ownership Party.
6. Every Ownership Party Owner matches the Property's Organization context.
7. One Owner appears at most once within an Ownership.
8. Each known share is greater than zero and at most 100.
9. Fully known shares total 100%; incomplete shares are allowed when one or more are unknown.
10. Acting Owner designation references a party in the same Ownership.
11. At most one Acting Owner designation is current per Ownership.
12. No designation remains active after its Ownership closes.
13. Closed Ownership and parties are immutable under normal operations.
14. An Organization Property has at most one active confirmed Global Identity link.
15. Relink preserves the ended link and creates a new link.
16. Unlink requires a reason.
17. Global identity operations never merge or disclose Organization operational records.
18. Property, Owner, Ownership, designation, and Global Identity history are not hard-deleted.

---

## Implemented Layers

BF013 implemented only the layers necessary for its approved scope:

* database persistence and migrations
* models/domain representations
* validators
* repositories with persistence-only responsibilities
* services enforcing business rules and transactions
* controllers and routes
* BF012-integrated authorization and Organization scope
* permission catalog additions corresponding to implemented capabilities
* acceptance, validation, authorization, persistence, and regression tests
* implementation documentation synchronization

The implementation includes migrations 009-017, reusable application-generated ULIDs through `UlidGeneratorInterface` backed by Symfony UID 5.4, QueryBuilder `FOR UPDATE` row locking, persistence repositories, transactional domain services, BF012-integrated authorization, controllers, REST routes, and focused acceptance/regression tests.

---

## Explicit Non-Goals

BF013 does not implement:

* Listing, Listing Versions, or Listing lifecycle
* Internal or Owner Listing Approval
* Marketplace, Requests, or Hold workflows
* Sale Closing or Ownership Transfer Confirmation
* Commission or Transfer Engines
* rich Property Profile
* Developer, Project/Compound, Phase, Location, Property Category, or Unit Type catalogs
* typed Property Attributes or Catalog Proposals
* Media or Private Documents
* Global Property matching algorithms, confidence scoring, or candidate matching
* cross-Organization Owner matching
* WhatsApp approval
* frontend Property/Ownership UI

---

## Acceptance Criteria

BF013 implementation was accepted against these criteria:

1. Organization Property operations enforce scope, minimum fields, lifecycle, audit, and no-hard-delete rules.
2. Owner operations enforce Organization scope, party types, minimum fields, lifecycle, privacy, and non-unique optional contacts.
3. Current Ownership can be recorded only with same-Organization Owners and at least one party.
4. A Property cannot have multiple current Ownerships.
5. Ownership Party uniqueness and known/incomplete share rules are enforced atomically.
6. Current Ownership parties may be maintained; closed Ownership and parties reject ordinary mutation.
7. Closing Ownership preserves history and ends any current Acting Owner designation without creating sale, buyer, transfer, or replacement Ownership side effects.
8. Acting Owner designation references a same-Ownership party, permits at most one current designation, and preserves change/clear history.
9. Global identities and link history are accessible only through explicit System capabilities.
10. Link, unlink, and relink enforce one active link per Property, required unlink reason, and historical preservation.
11. Organization-facing Property operations reveal no Global Identity or cross-Organization duplicate information.
12. Archive/reactivation preserves Ownership and link history and remains independent of Listing states.
13. Unauthorized and out-of-scope access is rejected without weakening BF012.
14. Existing BF006-BF012 and AF001-AF003 behavior remains unchanged.
15. No out-of-scope Listing, profile, catalog, media, document, Sale, Transfer, or frontend behavior is introduced.

---

## Testing Expectations

Implementation verification covered:

* successful and invalid creation for each BF013 resource
* required-field and party-type validation
* Organization scope and cross-Organization rejection
* Parent Franchise privacy boundaries for Partner Agency Owner/Ownership data
* System capability requirements for Global Identity operations
* zero-or-one current Ownership behavior
* same-Owner party duplication rejection
* known, incomplete, invalid, and total share scenarios
* Acting Owner designate/change/clear/history and invalid-party rejection
* Ownership close immutability and absence of transfer/sale side effects
* Property and Owner archive/deactivate plus reactivation
* Global Identity link/unlink/relink/history and required unlink reason
* absence of Global Identity data in normal Organization-facing responses
* transaction rollback for multi-record operations
* BF006-BF012 backend regression, health endpoint, PHP syntax, and database cleanup

---

## Deferred Work

All explicit non-goals remain deferred. No name or number is assigned here to a future backend or Admin Frontend sprint.

---

## Implementation Completion

Completed execution units:

* 2A - Database Schema & Migrations
* 2B - QueryBuilder `FOR UPDATE`
* 2C - Repositories
* 2D.0 - ULID Foundation
* 2D.1 - Property and Owner Services
* 2D.2 - Ownership Aggregate Service
* 2D.3 - Global Physical Identity Service
* 2E.1 - Permission Catalog and Authorization Scope Foundation
* 2E.2 - HTTP Integration
* 2E.3 - Database-backed Acceptance and Regression

Implemented domain persistence comprises `organization_properties`, `owners`, `ownerships`, `ownership_parties`, `authorized_acting_owner_designations`, `global_physical_property_identities`, `global_physical_identity_links`, and `property_owner_lifecycle_history`. REST endpoints cover Property, Owner, Ownership, Ownership Party, Acting Owner, Global Physical Identity, and Global Identity link operations.

Verification passed migrations 001-017 on MariaDB 10.4.32, PDO multi-statement migration execution, generated nullable uniqueness guards, foreign keys, `CHECK` constraints, current-record enforcement, Party/share invariants, lifecycle and historical persistence, rollback after post-write failure, HTTP `200`/`201`/`401`/`403`/`404`/`422` contracts, authorization scopes, trusted target semantics, privacy boundaries, legacy backend compatibility smoke, and BF013 regressions. Isolated cleanup reported `remaining_bf013_test_databases=0`.

---

## Definition of Done

BF013 is complete because its implementation:

* satisfies every locked rule and acceptance criterion in this contract
* implements the required backend/domain layers using existing architecture
* integrates authorization through BF012 without Position-name logic
* includes applicable migrations, persistence, APIs, permissions, and tests
* passes BF013 acceptance and BF006-BF012 regression verification
* validates PHP syntax and repository diff hygiene
* cleans all temporary test data
* updates canonical implementation and database documentation accurately
* introduces no out-of-scope Listing or frontend implementation

BF013 is **IMPLEMENTED AND VERIFIED / CLOSED**. No subsequent sprint is selected.
