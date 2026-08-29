# AF003 — Organization User Administration

**Milestone:** Admin Frontend / Organization Administration  
**Status:** Approved for Implementation  
**Depends On:** BF009, BF010, BF011, BF012, AF001, AF002  
**Primary Actors:** System Administration, Franchise Administration, Partner Agency Administration  
**Frontend:** Existing `frontend/` Next.js Admin Application

---

# 1. Objective

AF003 introduces real Organization-scoped User and Position administration inside the existing Directors Resale Platform Admin Application.

The sprint must allow authorized administrators to manage Users and their Position assignments according to Organization hierarchy, effective Permissions, and administrative scope.

AF003 must support three administrative contexts:

- System Administration
- Franchise Administration
- Partner Agency Administration

The application remains one Admin Application.

Do not create separate applications for different Organization types.

---

# 2. Core Administration Model

Administrative experience is determined by:

Authenticated User
+ Organization
+ Position
+ Effective Permissions
+ Resource Scope

Position names are not authorization rules.

Frontend visibility is UX only.

Backend authorization remains authoritative.

---

# 3. Organization Hierarchy

Preserve:

System Organization
→ Franchise
→ Partner Agency

Users belong to Organizations.

Users are not another hierarchy level.

---

# 4. System Support Authority

The System Administration context is the highest support authority of the platform.

Authorized System Administration must be capable of supporting and administering Organizations across the network.

System-level support authority must be able to manage, where the relevant module exists:

- Franchises
- Partner Agencies
- Users
- Positions
- Position Permission assignments
- operational records in future modules

AF003 specifically implements this authority for User and Position administration.

Do not implement support authority as:

- authentication bypass
- hidden backdoor
- direct database shortcut
- frontend-only exception

System support operations must remain authenticated, authorized, and compatible with future audit logging.

Business workflows that later require explicit approvals or historical records must not be silently bypassed merely because the actor is System Administration.

---

# 5. Administrative Scope

## System Administration

Authorized System Administration may administer:

- System Organization Users
- Franchise Users
- Partner Agency Users
- Positions across Organizations
- Position Permission assignments across Organizations

System scope is platform-wide for support purposes.

## Franchise Administration

Authorized Franchise Administration may administer:

- Users belonging to its own Franchise
- Positions belonging to its own Franchise
- permitted Partner Agency administrative resources beneath its Franchise where explicitly authorized

Franchise Administration must not administer:

- sibling Franchises
- unrelated Partner Agencies
- System Organization Users

## Partner Agency Administration

Authorized Partner Agency Administration may administer:

- Users belonging to its own Partner Agency
- Positions belonging to its own Partner Agency

It must not administer:

- Franchise Users
- sibling Partner Agencies
- System Organization Users
- unrelated Organizations

---

# 6. User Administration UI

Replace the AF001 User placeholder with real User administration.

Primary route:

`/admin/users`

The experience must adapt to the authenticated administrator's scope.

The page should support:

- User listing
- search where appropriate
- Organization context
- Position context
- User status
- User details
- User creation
- User editing where permitted
- activation/deactivation where supported
- Position assignment

Provide:

- loading states
- empty states
- API error states
- forbidden states

Do not fabricate unsupported User fields.

---

# 7. User List Scope

System Administration may see Users across the authorized platform scope.

Franchise Administration sees only Users within its permitted administrative scope.

Partner Agency Administration sees only Users within its own Agency scope.

The backend must enforce this scope.

Do not retrieve all Users and rely on frontend filtering for security.

---

# 8. User Creation

Authorized administrators may create Users only inside Organizations they are permitted to administer.

User creation must preserve BF009 validation and domain behavior.

Required Organization association must be explicit and valid.

System Administration may select an Organization within System scope.

Franchise Administration may create Users only within permitted Franchise/child scope.

Partner Agency Administration may create Users only within its Agency.

Do not permit arbitrary Organization IDs supplied by the browser to bypass scope.

---

# 9. User Identity

An existing platform User must not be duplicated merely because they later move to another Organization.

However:

USER ORGANIZATION TRANSFER IS OUT OF SCOPE FOR AF003.

If an email/identity already belongs to an existing User, AF003 must not silently create another identity to simulate a transfer.

Return or display an appropriate conflict/error according to existing backend behavior.

Future Organization transfer requires its own approved workflow.

---

# 10. User Editing

Authorized administrators may update supported User fields within scope.

Do not introduce ordinary Organization reassignment through User editing.

The Organization association must remain protected from generic update behavior.

Moving a User between Organizations is not CRUD.

---

# 11. User Status

Where supported by the current User domain, authorized administrators must be able to manage User lifecycle/status safely.

The UI must clearly distinguish deactivation from deletion.

Do not physically delete Users merely to represent employment departure or temporary suspension.

Historical identity must remain preservable for future Listings, Requests, Deals, commissions, transfers, and audit history.

---

# 12. Position Administration UI

Replace the AF001 Position placeholder with real Position administration.

Primary route:

`/admin/positions`

Support where authorized:

- Position list
- Position details/context
- create Position
- edit Position
- status management where supported
- view assigned Permission capabilities
- assign permitted capabilities

Positions remain Organization-owned.

---

# 13. Position Organization Rules

A Position belongs to one Organization.

A User may only receive a Position valid for that User's Organization according to the approved Position architecture.

Do not allow:

- cross-Franchise Position assignment
- Franchise Position assigned to Partner Agency User
- Partner Agency Position assigned to Franchise User
- sibling Agency Position assignment
- unrelated Organization Position assignment

System Administration may manage these records across Organizations for support, but must preserve correct ownership relationships.

---

# 14. Permission Catalog

The Permission capability catalog remains System-controlled.

AF003 does NOT allow Franchise or Partner Agency administrators to invent arbitrary Permission codes.

Organization administrators may assign only existing capabilities through Positions and only where authorized.

Do not introduce a second role/permission system.

---

# 15. Privilege Escalation Protection

AF003 must explicitly protect against privilege escalation.

An Organization administrator must not be able to create or modify a Position in a way that grants authority beyond what they are allowed to assign.

Permission assignment must satisfy all applicable rules:

1. Permission exists in the System-controlled catalog.
2. Target Position belongs to an Organization within the actor's administrative scope.
3. Actor is authorized to assign Permissions.
4. Permission is valid/assignable for the target administrative context.
5. Assignment does not create unauthorized privilege escalation.

Do not assume `permissions.assign` alone automatically means every capability in the platform can be delegated.

Inspect BF012 and implement the smallest safe delegation rule compatible with the existing architecture.

If a complete safe delegation model requires a new architectural decision beyond AF003, stop that specific enhancement and report the blocker rather than inventing a broad privilege model.

---

# 16. Position Names

Never authorize by Position name.

Prohibited examples:

`position.name === "System Admin"`

`position.name === "Franchise Admin"`

`position.name === "Partner Agency Admin"`

Names are labels.

Permissions and scope determine authority.

---

# 17. Initial Franchise Administrator Compatibility

AF003 must remain compatible with administrators provisioned by AF002.

The initial Franchise administrator created during Franchise onboarding must appear correctly in Organization User administration after activation/lifecycle requirements are satisfied.

Do not create a parallel administrator identity.

---

# 18. Partner Agency Administration Boundary

AF003 provides User/Position administration according to existing Organization scope.

Full Partner Agency provisioning remains outside AF003 unless already explicitly supported by approved prior architecture.

Do not expand AF003 into a complete Partner Agency onboarding sprint.

---

# 19. System Admin Support UX

System Administration needs a practical way to understand which Organization is being administered.

System-level User and Position screens should expose clear Organization context.

Where appropriate, provide Organization selection/filtering using authorized Organizations.

The interface must make it difficult for support personnel to accidentally modify the wrong Organization.

High-impact actions should clearly display target User/Organization context before confirmation.

---

# 20. Authentication / Credential Boundary

Reuse BF010 and the existing AF001 authentication bridge.

AF003 must not invent insecure credential behavior.

Do not:

- expose password hashes
- expose token hashes
- expose raw bearer tokens
- store bearer tokens in localStorage
- store bearer tokens in sessionStorage
- introduce production default passwords

Credential setup/invitation remains governed by implemented authentication capabilities and approved future activation architecture.

---

# 21. User Transfer — Explicitly Deferred

AF003 must NOT implement User transfer between Organizations.

Approved future architecture:

Existing User
→ Transfer Request
→ old Organization decision
→ new Organization acceptance where applicable
→ Organization transfer
→ Listing ownership decisions
→ auditable history

Do not add:

- Change Franchise dropdown to generic User Edit
- Change Agency dropdown to generic User Edit
- direct `organization_id` mutation
- duplicate User workaround

---

# 22. Future Listing Ownership Transfer

Listings are not part of AF003.

Future behavior is already approved conceptually:

When a User leaves an Organization, Listings must not lose ownership history.

If transfer is approved:

User/Listings may move according to the approved transfer workflow.

If Listing transfer is refused:

Listing remains with the previous Organization and management may be reassigned to the previous Organization administrator while preserving the original uploader/ownership history.

AF003 must not implement this workflow or invent Listing schema.

---

# 23. Global Marketplace Visibility

Preserve:

Global Marketplace Visibility
!=
Administrative Scope

Future marketplace-eligible available Listings are intended to be visible to authenticated platform Users across the network.

Organization User administration must not introduce an assumption of isolated Franchise marketplaces.

No Listing implementation belongs in AF003.

---

# 24. Frontend Architecture

Reuse AF001 and AF002 patterns.

Expected architecture includes:

- Next.js App Router
- TypeScript
- Tailwind CSS
- TanStack Query
- React Hook Form
- Zod
- Lucide
- source-owned UI components
- existing BFF/authentication pattern
- existing design tokens

Prefer feature-specific modules rather than oversized page components.

Do not scatter repeated API communication throughout visual components.

---

# 25. Design Requirements

Extend the existing Admin design system.

User and Position administration should be suitable for daily operational use.

Prioritize:

- clear tables/lists
- search/filter context
- status visibility
- Organization context
- Position context
- clear actions
- confirmation for impactful operations
- responsive behavior
- loading/error/empty/forbidden states

Do not introduce Bootstrap or another large UI framework.

---

# 26. Future Designer Compatibility

Keep domain/application logic separate from presentation.

A future Figma redesign must be able to replace:

- tables
- forms
- filters
- drawers/modals
- page layouts
- cards
- responsive presentation

without rewriting:

- authentication
- authorization
- API integration
- scope enforcement
- User business rules
- Position business rules
- Permission delegation rules

---

# 27. Backend Enhancement Policy

Prefer BF009-BF012 APIs and services.

Backend changes are permitted only where necessary for safe AF003 administration.

Any enhancement must:

- be minimal
- preserve existing domain modules
- preserve authentication
- preserve Organization scope
- preserve BF012 authorization
- avoid unrelated refactoring
- avoid future-domain implementation

Do not create frontend-specific security shortcuts.

---

# 28. Explicitly Out of Scope

AF003 must NOT implement:

- User Organization transfer
- Listing ownership transfer
- Listings
- Requests
- Matching
- Deals
- SOLD workflow
- Commissions
- Reports
- Analytics
- Teams
- public marketplace
- public website
- arbitrary Permission creation
- production email infrastructure
- complete Partner Agency onboarding
- future transfer engine
- future Listing schema
- AF004

---

# 29. Acceptance Criteria

AF003 is complete only when applicable verification confirms:

## User Administration

- authorized User list works
- scope-filtered User list works
- search/filter works where implemented
- User details/context works
- valid User creation works
- invalid Organization assignment is rejected
- User update works within scope
- User status management works where supported
- Position assignment works
- cross-Organization Position assignment is rejected
- Organization transfer is unavailable through normal editing

## System Administration

- System support can administer Users across Franchise/Partner Agency scope
- System support can administer Positions across Organization scope
- target Organization is clearly represented
- support authority does not bypass authentication
- support actions remain subject to backend authorization architecture

## Franchise Administration

- permitted Franchise administrator can manage own Franchise Users
- cannot manage sibling Franchise Users
- cannot manage System Organization Users
- child Organization access follows explicit authorized scope

## Partner Agency Administration

- permitted Partner Agency administrator can manage own Agency Users
- cannot manage Franchise Users
- cannot manage sibling Agency Users
- cannot manage unrelated Organizations

## Positions

- Position list works
- Position creation works
- Position editing works
- Position Organization ownership is preserved
- Permission assignments use existing catalog
- arbitrary Permission creation is unavailable
- cross-Organization assignment is rejected

## Privilege Escalation

- Organization administrators cannot grant unauthorized capabilities
- Permission assignment is backend-enforced
- frontend hiding is not treated as security

## Security

- unauthenticated access is rejected
- unauthorized actions return appropriate authorization failure
- sensitive authentication data does not reach browser
- no raw bearer-token browser storage
- no Position-name authorization
- no Organization reassignment shortcut

## Regression

- AF001 authentication/navigation remains functional
- AF002 Franchise administration remains functional
- BF009 User behavior remains valid
- BF010 authentication remains valid
- BF011 Position behavior remains valid
- BF012 authorization/scope remains valid

## Frontend Quality

- TypeScript passes
- ESLint passes
- production build passes
- no Bootstrap
- responsive management UI
- loading/error/empty/forbidden states present

---

# 30. Browser Verification

AF003 must receive manual browser verification before sprint closure.

At minimum inspect:

- System-level User list
- User creation/edit experience
- Position assignment
- Position administration
- Franchise-scoped User experience where test credentials permit
- Partner Agency-scoped experience where test credentials permit
- forbidden states
- responsive layout

Automated tests alone are not sufficient for final AF003 closure.

---

# 31. Documentation

After verified implementation, update only established current-state documentation necessary to record AF003.

Clearly distinguish:

IMPLEMENTED

APPROVED FUTURE ARCHITECTURE

DEFERRED

Do not mark User Transfer, Listings, Reports, Teams, or AF004 implemented.

---

# 32. Completion Rule

AF003 is not complete merely because User and Position pages render.

It is complete when Organization-scoped administration works safely against the real backend, System support authority is operational for the implemented domains, privilege escalation protections are verified, regressions pass, and manual browser QA is completed.

No commit or push is part of implementation execution unless explicitly requested separately.