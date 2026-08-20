# AF002 — Network Administration UI

**Milestone:** Admin Frontend / Network Administration  
**Status:** Approved for Implementation  
**Depends On:** BF006, BF007, BF008, BF009, BF010, BF011, BF012, AF001  
**Primary Actor:** Authorized System Organization User  
**Frontend:** Existing `frontend/` Next.js Admin Application

---

# 1. Objective

AF002 introduces the first real network-management experience inside the Directors Resale Platform Admin Application.

The sprint allows an appropriately authorized System Organization User to manage the Franchise network through the existing Admin UI.

AF002 must build on the architecture established by:

- Organization Management
- Franchise Management
- User Management
- Authentication
- Dynamic Positions
- Permissions / Authorization
- AF001 Admin UI Foundation
- ADMIN_EXPERIENCE_ARCHITECTURE.md

AF002 is NOT a generic CRUD implementation.

Its purpose is to provide a coherent System-level Franchise administration workflow.

---

# 2. Primary User Experience

The primary AF002 perspective is:

System Administration

The System User must be able to:

1. View Franchises.
2. Search/filter the Franchise network where supported by the approved API design.
3. Create a Franchise.
4. Create/provision its initial Franchise administrator.
5. View Franchise details.
6. View Partner Agencies belonging to a Franchise.
7. Activate/deactivate or archive a Franchise according to existing backend lifecycle rules.
8. Understand Organization hierarchy/context from the UI.

All operations remain permission-controlled.

Frontend visibility is UX only.

Backend authorization remains authoritative.

---

# 3. Approved Organization Hierarchy

AF002 must preserve:

System Organization
→ Franchise
→ Partner Agency

Users belong to Organizations.

Users are NOT another Organization hierarchy level.

---

# 4. One Admin Application

AF002 extends:

`/admin`

Do NOT create a separate System Admin application.

The existing AF001 authenticated layout, Sidebar, Header, Query infrastructure, API client, design system, authentication bridge, and permission context must be reused.

---

# 5. Franchise List

Route:

`/admin/franchises`

Replace the AF001 placeholder with a real Franchise management screen.

The page should support an appropriate management presentation including:

- Franchise name
- Franchise code
- status
- parent/System context where useful
- available management actions
- clear empty state
- loading state
- error state

Use the existing backend Franchise API wherever sufficient.

Do not duplicate Franchise business rules in the frontend.

---

# 6. Franchise Creation

Provide a System-authorized Franchise creation workflow.

The UI should collect the Franchise information required by the existing Franchise domain.

At minimum, respect existing backend requirements for:

- name
- code
- parent Organization
- status

The parent must follow the approved hierarchy.

Do not allow the frontend to create an invalid Organization hierarchy.

Backend validation remains authoritative.

---

# 7. Initial Franchise Administrator

A newly provisioned Franchise should support creation of its initial administrator as part of the onboarding experience.

From the User's perspective, Franchise onboarding should feel like one coherent workflow.

Conceptually:

Create Franchise
→ Initial Franchise Administrator
→ Position assignment
→ Account readiness

The implementation may use multiple backend requests internally if required.

---

# 8. Initial Administrator Data

The UI should collect only information supported by the existing User architecture.

Expected information includes, where supported:

- full name
- email
- phone if applicable
- Franchise association
- Position

Do not invent unrelated profile fields.

---

# 9. Initial Administrator Position

AF002 must not hard-code application authorization based on the string:

`Franchise Admin`

The initial administrator must be assigned an appropriate Position belonging to the Franchise.

The Position must ultimately derive authority from BF011/BF012.

If an appropriate Franchise administration Position does not already exist, AF002 may introduce the smallest approved provisioning support required to establish one safely.

Any such backend enhancement must:

- use the existing Position architecture
- use the existing Permission catalog
- prevent privilege escalation
- remain Organization-scoped
- avoid introducing hard-coded runtime role checks

Do not create arbitrary new Permission capabilities without explicit architectural necessity.

---

# 10. Credential / Activation Boundary

AF002 must NOT invent a production email-delivery system.

The architecture should support future:

Create User
→ Invite / Activate
→ Set Password

If the current backend does not yet provide an invitation/activation mechanism, AF002 may leave production invitation delivery deferred.

The Franchise and initial administrator records must not require insecure password exposure merely to satisfy the UI.

Do NOT:

- display password hashes
- expose token hashes
- store raw passwords in frontend storage
- create insecure default production passwords

Any temporary development-only mechanism must remain clearly development-only and must not become production behavior.

---

# 11. Transaction / Failure Safety

Franchise onboarding consists of logically related operations.

The implementation must avoid silently presenting onboarding as successful when:

- Franchise creation succeeds
- but required initial administrator provisioning fails

The UI must clearly distinguish:

- complete onboarding
- partial/incomplete onboarding
- failed onboarding

Do not silently create duplicate Franchise records during retry.

If backend transaction support is required for safe provisioning, inspect the existing architecture first and implement only the smallest justified solution.

---

# 12. Franchise Details

Provide a real Franchise detail experience.

Suggested route:

`/admin/franchises/[id]`

The page should expose currently implemented, authorized information such as:

- Franchise identity
- code
- status
- Organization relationship
- initial/current administrative context where safely available
- Partner Agencies belonging to the Franchise

Do not display sensitive authentication information.

---

# 13. Partner Agency Overview

AF002 does NOT implement the full Franchise Admin Partner Agency provisioning experience.

However, System-authorized Franchise details should be able to expose Partner Agencies belonging to that Franchise using existing BF008 data where authorized.

This is primarily:

- network visibility
- hierarchy understanding
- navigation/context

Do not turn AF002 into full Partner Agency administration from the Franchise Admin perspective.

That belongs to a later Organization Administration milestone.

---

# 14. Franchise Status Management

Use existing Franchise lifecycle behavior.

Current architecture uses status-based archival/deactivation rather than physical deletion for Franchise management.

The UI must communicate destructive/deactivation actions clearly.

Do not imply physical deletion when the backend performs deactivation/archive behavior.

Archived/inactive behavior must remain consistent with the existing Franchise domain.

---

# 15. Permissions

AF002 pages/actions must respect BF012.

Examples conceptually include existing Franchise/User/Position capabilities where applicable.

Do not invent authorization from Position names.

Examples of prohibited frontend logic:

`position.name === "System Admin"`

or:

`position.name === "Franchise Admin"`

Use effective backend Permission codes.

Backend endpoints must independently enforce authority.

---

# 16. Administrative Scope

AF002 is System network administration.

Management access to Franchise records must remain governed by existing authorization scope.

This sprint must not weaken BF012 merely to make the frontend work.

---

# 17. Global Marketplace Architecture

AF002 must preserve the approved distinction:

Global Marketplace Visibility
!=
Administrative Scope

AF002 does NOT implement Listings.

However, nothing introduced by AF002 may imply that Franchise boundaries create isolated future marketplaces.

All authenticated platform Users will later be able to browse marketplace-eligible available Listings across the network.

This remains separate from Franchise management authority.

---

# 18. User Transfer Architecture

User transfer between Organizations is NOT part of AF002.

AF002 must not implement Organization transfer as an ordinary User update.

Approved future behavior requires a dedicated auditable transfer workflow.

When a User later moves between Organizations:

- the existing User identity should be transferred rather than duplicated
- old/new Organization rules and approvals apply
- Listing transfer/retention decisions must be explicit
- ownership history must be preserved

Do not add a generic `change organization` control to User forms in AF002.

---

# 19. Listing Transfer Architecture

Listings are not implemented in AF002.

Future Listing ownership transfer behavior must remain deferred.

AF002 must not invent Listing tables, fields, ownership logic, or transfer workflows.

---

# 20. Reports

Reports are out of scope.

Do not implement:

- System reports
- Franchise reports
- Partner Agency reports
- KPIs
- analytics charts

AF002 UI must not display fabricated business statistics.

---

# 21. Frontend Architecture

Reuse AF001.

Expected separation:

`frontend/app`
→ routes/layout composition

`frontend/components`
→ reusable visual/application components

`frontend/lib/api`
→ API communication

`frontend/types`
→ domain/API types

TanStack Query
→ server-state handling

React Hook Form + Zod
→ forms where appropriate

Do not scatter repeated direct API calls across page components.

---

# 22. Design Requirements

AF002 extends the approved AF001 design.

Use:

- existing Directors branding
- existing design tokens
- existing spacing/typography
- existing cards/buttons/forms
- reusable table/list patterns
- appropriate confirmation UX
- loading states
- error states
- empty states

The result must remain suitable for management presentation.

Do not introduce a second visual system.

---

# 23. Future Designer Compatibility

AF002 business logic must remain independent from the current visual implementation.

A future Figma redesign must be able to replace:

- tables
- forms
- page layouts
- cards
- navigation presentation
- responsive behavior

without rewriting:

- authentication
- authorization
- API integration
- domain rules
- TanStack Query logic
- provisioning behavior

---

# 24. Backend Enhancement Policy

Prefer existing BF006-BF012 APIs.

Backend changes are permitted only when required to safely support the approved AF002 workflow.

Any backend enhancement must:

1. be minimal
2. reuse existing modules/services
3. preserve authorization
4. preserve Organization scope
5. avoid duplicate business logic
6. avoid unrelated refactoring
7. avoid future-domain implementation

---

# 25. Explicitly Out of Scope

AF002 must NOT implement:

- general Organization User administration
- Partner Agency Admin experience
- Partner Agency User management
- full Position management UI
- full Permission assignment UI
- User transfer between Organizations
- Team hierarchy
- Listings
- Requests
- Matching
- Deals
- SOLD workflow
- Commissions
- Reports
- Analytics
- Notifications
- public website
- production email infrastructure
- arbitrary new Permission capabilities
- AF003

---

# 26. Acceptance Criteria

AF002 is complete only when applicable verification confirms:

## Franchise Network

- Franchise list works.
- empty state works.
- loading state works.
- API failure state works.
- Franchise details work.
- Partner Agency hierarchy/overview is correctly represented.

## Franchise Creation

- authorized System User can create a valid Franchise.
- required validation works.
- invalid hierarchy is rejected.
- duplicate code behavior is handled.
- backend remains authoritative.

## Initial Franchise Admin

- initial administrator can be provisioned through the onboarding experience.
- administrator belongs to the new Franchise.
- appropriate Position assignment is established.
- authorization is based on Permissions, not Position-name checks.
- insecure credential handling is not introduced.

## Failure Handling

- partial provisioning does not appear as complete onboarding.
- retries do not silently create duplicate Franchises.
- meaningful API errors are shown safely.

## Status

- authorized status/deactivation behavior works.
- inactive/archive semantics match backend behavior.
- physical deletion is not implied.

## Security

- unauthenticated access is rejected.
- unauthorized access is rejected.
- restricted System User cannot perform unavailable actions.
- frontend hiding is not relied upon as security.

## Regression

- AF001 login works.
- AF001 logout works.
- `/auth/context` works.
- permission-aware navigation works.
- BF007 Franchise behavior remains valid.
- BF009 User behavior remains valid.
- BF011 Position behavior remains valid.
- BF012 authorization remains valid.

## Frontend Quality

- TypeScript passes.
- lint passes if configured.
- production build passes.
- no Bootstrap introduced.
- no raw token storage introduced.
- design remains consistent with AF001.

---

# 27. Documentation

After verified implementation, update only the established current-state documentation required to record AF002.

Clearly distinguish:

- implemented functionality
- approved future architecture
- deferred workflows

Do not mark AF003 or future domains implemented.

---

# 28. Completion Rule

AF002 is not complete merely because pages render.

It is complete when the System-level Franchise onboarding and network administration workflow operates safely against the real backend and passes applicable authorization/regression verification.

No commit or push is part of implementation execution unless explicitly requested separately.