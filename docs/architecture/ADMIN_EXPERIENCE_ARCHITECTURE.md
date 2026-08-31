# Directors Resale Platform
# Admin Experience Architecture

**Document Type:** Product / Application Architecture  
**Status:** Approved Architecture  
**Applies To:** Admin Application, Organization Management, Users, Listings, Requests, Reports, Permissions and future operational modules

---

# 1. Purpose

This document defines how different authenticated users experience and operate the Directors Resale Platform.

The platform must provide one coherent Admin Application while adapting:

- navigation
- available actions
- management capabilities
- organization visibility
- reporting scope
- operational scope

according to the authenticated User's:

1. Organization
2. Position
3. Permissions
4. applicable resource scope

This document also establishes a critical distinction between:

- global marketplace visibility
- administrative and management scope

These concepts must never be treated as the same thing.

---

# 2. Core Product Principle

Directors Resale Platform is a shared resale network.

It is NOT a collection of isolated Franchise CRMs.

The value of the platform depends on authenticated users across the network being able to discover available resale inventory uploaded by other participants in the network.

Therefore:

> All authenticated platform Users may browse all Listings that are currently eligible for marketplace visibility, regardless of which Franchise, Partner Agency, or authorized platform participant created them.

Subject to the applicable Request workflow, authenticated Users may submit Requests against those available Listings.

Organization boundaries restrict management authority.

They do not isolate the shared available resale marketplace.

---

# 3. One Admin Application

The platform shall use one primary authenticated application:

`/admin`

Separate applications must NOT be created for:

- System Admin
- Franchise Admin
- Partner Agency Admin
- Manager
- Team Leader
- Sales

The experience is dynamically determined by authorization context.

Conceptually:

User
→ Organization
→ Position
→ Permissions
→ Resource Scope
→ Available Navigation / Actions / Data

The frontend must not determine authority using hard-coded Position names.

Incorrect:

`if position === "Manager"`

Correct:

`Does the authenticated User have the required Permission?`

combined with:

`Is the requested resource/action within the User's permitted scope?`

The backend remains authoritative.

Frontend permission handling exists for UX only.

---

# 4. Authorization Model

The platform separates:

## 4.1 Capability

Capability answers:

> What may this User do?

Capability is controlled through Permissions.

Examples:

- view
- create
- update
- approve
- archive
- manage users
- view reports
- configure commissions

## 4.2 Scope

Scope answers:

> On whose data may this User perform that action?

Possible scope concepts include:

- own
- team
- organization
- child organization
- organization network
- platform

The exact scope implementation may differ by domain module.

A Permission alone must not automatically imply platform-wide management authority.

---

# 5. Global Marketplace Visibility vs Administrative Scope

This distinction is mandatory throughout the platform.

## 5.1 Global Marketplace Visibility

All authenticated Users may browse Listings that are currently available and eligible for marketplace visibility across the entire Directors Resale network.

This includes available Listings originating from:

- System Organization
- Franchises
- Partner Agencies
- authorized Users within those Organizations

Example:

Franchise A uploads Listing A.

Franchise B uploads Listing B.

Partner Agency A1 uploads Listing C.

A Sales User belonging to Franchise C may browse:

- Listing A
- Listing B
- Listing C

provided those Listings are currently marketplace-visible.

Organization ownership must not hide available marketplace inventory from authenticated network participants.

## 5.2 Request Capability

Authenticated Users may submit Requests against marketplace-visible Listings subject to the future Request workflow and applicable Permissions/business rules.

The Request may originate from a User whose Organization is different from the Organization that owns or uploaded the Listing.

This cross-Organization interaction is an intentional core feature of the platform.

## 5.3 Administrative Scope

Global visibility does NOT grant management authority.

Example:

A Sales User in Franchise A may be able to:

- view a Listing belonging to Franchise B
- inspect its marketplace information
- submit an allowed Request against it

but must NOT automatically be able to:

- edit it
- archive it
- approve it
- modify its ownership
- modify its commission configuration
- mark it SOLD
- manage the User who uploaded it

unless separately authorized by the applicable business rules and Permissions.

Therefore:

Marketplace Visibility != Management Authority

This rule must be preserved in Listing, Request, Matching, Deal, Commission, and Reporting architecture.

---

# 6. Organization Hierarchy

The approved Organization hierarchy remains:

System Organization
→ Franchise
→ Partner Agency

A Franchise belongs beneath the System Organization.

A Partner Agency belongs beneath a Franchise.

This hierarchy defines organizational ownership and administrative relationships.

It does not define marketplace visibility.

---

# 7. Account Provisioning Hierarchy

Account provisioning follows organizational authority.

## 7.1 Franchise Provisioning

A System-authorized administrator creates a Franchise.

The Franchise onboarding flow must support creation or invitation of an initial Franchise administrator.

Conceptually:

System Admin
→ Create Franchise
→ Create / Invite Initial Franchise Admin
→ Assign approved Position
→ Activate Franchise access

The exact activation mechanism may be implemented in a later authentication/onboarding sprint.

## 7.2 Franchise Users

A sufficiently authorized Franchise User may create Users within that Franchise.

The User may assign only Positions and capabilities that the platform permits that administrator to manage.

A Franchise administrator must not gain unrestricted platform authority merely because they manage their Franchise.

## 7.3 Partner Agency Provisioning

Subject to approved Permissions, a Franchise administrator may create/manage Partner Agencies beneath that Franchise.

Partner Agency onboarding must support an initial Partner Agency administrator.

Conceptually:

Franchise Admin
→ Create Partner Agency
→ Create / Invite Initial Partner Agency Admin
→ Assign approved Position
→ Activate Agency access

## 7.4 Partner Agency Users

A sufficiently authorized Partner Agency administrator may create Users belonging to that Partner Agency.

Their management scope remains within their permitted Agency context.

---

# 8. Permission Governance

Permissions represent platform capabilities.

The System controls the Permission catalog.

Franchise and Partner Agency administrators must NOT create arbitrary new system capabilities.

Organization administrators may configure or assign approved Positions/Permissions only within the authority granted to them.

Principle:

System controls capabilities.

Organizations configure their people within permitted boundaries.

This prevents privilege escalation through organization-level administration.

---

# 9. System Administration Experience

A sufficiently authorized System Organization User may have platform-wide administrative capabilities.

Potential navigation may eventually include:

Dashboard

Network
- Franchises
- Partner Agencies
- Users

Inventory
- All Listings
- Pending Listings
- Sold Listings
- Archived Listings

Requirements
- Buyer Requests
- Broker Requests
- Matching

Deals
- Active Deals
- Pending SOLD
- Completed Deals

Finance
- Commissions
- Commission Rules

Reports
- Platform Overview
- Franchise Performance
- Sales Performance
- Inventory
- Commissions

Administration
- Positions
- Permissions
- Settings

This navigation is conceptual.

Individual items must appear only when supported by implemented modules and effective Permissions.

---

# 10. Franchise Administration Experience

A sufficiently authorized Franchise User may manage the Franchise and permitted child Organization resources.

Potential future navigation may include:

Dashboard

My Franchise
- Overview
- Partner Agencies

Team
- Users
- Positions

Inventory
- All Available Listings
- My Listings
- Team Listings
- Franchise Listings
- Partner Agency Listings
- Pending Approval
- Sold Listings

Requirements
- Requests
- Matches

Deals
- Active
- Pending
- Completed

Reports
- Franchise Overview
- Users
- Listings
- Deals
- Commissions

The Franchise administration experience must remain scoped to that Franchise and its permitted child resources for management operations.

However:

`All Available Listings`

represents the shared platform marketplace and is not limited to the Franchise's own inventory.

---

# 11. Partner Agency Administration Experience

A sufficiently authorized Partner Agency User may manage permitted Agency resources.

Potential future navigation may include:

Dashboard

My Agency

Team
- Users
- Positions

Inventory
- All Available Listings
- My Listings
- Agency Listings

Requirements
- Requests
- Matches

Deals

Reports
- Agency Overview
- Users
- Listings
- Deals
- Commissions

Management actions remain scoped to the Partner Agency unless explicitly authorized otherwise.

Marketplace browsing remains platform-wide.

---

# 12. Operational User Experience

Users such as:

- Sales
- Team Leaders
- Managers

are represented through dynamic Positions and Permissions rather than permanently hard-coded application roles.

Example capability concepts may eventually include:

- listings.create
- listings.update_own
- listings.view_team
- listings.manage_organization
- listings.approve
- requests.create
- requests.view_team
- reports.view_team

These names are examples only and must not be treated as approved Permission codes until the relevant domain architecture is designed.

Position titles do not define authority.

Permissions and applicable scope define authority.

---

# 13. Listing Visibility Architecture

Domain-specific Listing behavior is approved in `docs/architecture/LISTING_DOMAIN_ARCHITECTURE.md`. This document remains authoritative for the shared Admin experience and the separation between global marketplace visibility and administrative authority.

Future Listing architecture must explicitly support two different concerns:

## Marketplace Query

Answers:

> Which Listings are available to authenticated marketplace participants?

This query is primarily determined by Listing marketplace status and future publication/business rules.

It is NOT primarily restricted by the viewer's Organization.

## Management Query

Answers:

> Which Listings may this User administer?

This query is determined by:

- Permissions
- ownership
- Organization
- team relationships where applicable
- resource scope
- Listing state
- business workflow

These query concepts must not be collapsed into one authorization rule.

---

# 14. Future Listing Scope

The Listing domain may require concepts such as:

- own
- team
- organization
- child organizations
- platform

Example:

Sales User:
- marketplace: all available Listings
- management: own Listings

Team Leader:
- marketplace: all available Listings
- management: permitted team Listings

Franchise Admin:
- marketplace: all available Listings
- management: Franchise and permitted child Organization Listings

Partner Agency Admin:
- marketplace: all available Listings
- management: Agency Listings

System Admin:
- marketplace: all available Listings
- management: platform scope when authorized

The Listing domain architecture is approved. Exact Listing Permission codes and future Team scope remain deferred; examples in this document are not final codes.

---

# 15. Request Architecture Principle

Requests are a cross-network business capability.

A User may discover a Listing belonging to another Organization and initiate an allowed Request.

Therefore the future Request domain must preserve:

Requester
→ Requester's Organization

and separately:

Listing
→ Listing Owner / Uploading Organization

These may be different Organizations.

The system must retain enough ownership information to correctly handle:

- routing
- matching
- approvals
- Deals
- commissions
- reporting
- audit history

---

# 16. Reporting Architecture

The platform should avoid building completely separate reporting engines for each Organization type.

Reports should conceptually use:

Report
+
Authorization Context
+
Reporting Scope

## System Reporting

Authorized System Users may receive platform-level reporting.

Potential scope:

Entire Platform

## Franchise Reporting

Authorized Franchise Users may receive reporting limited to their permitted Franchise scope and applicable child Organizations.

## Partner Agency Reporting

Authorized Partner Agency Users may receive reporting limited to their Agency scope.

## Team Reporting

Where Team structures are implemented, authorized Team Leaders or Managers may receive team-scoped reporting.

Marketplace visibility does not imply reporting visibility.

A User being able to browse another Organization's available Listing does NOT automatically grant access to that Organization's internal performance, users, commissions, or operational reports.

---

# 17. Commission Visibility

Marketplace Listing visibility must not automatically expose internal commission structures.

Future Listing APIs must distinguish between:

- marketplace-visible Listing information
- internal operational information
- commission information
- ownership/internal workflow information

Commission visibility must be separately authorized.

---

# 18. Frontend Architecture

The Admin frontend remains one application.

The UI adapts based on backend-provided context.

Current foundation:

AF001 Admin UI Foundation

provides:

- authentication bridge
- authenticated layout
- User context
- Organization context
- Position context
- effective Permission codes
- permission-aware navigation
- reusable design foundation

Future frontend modules must extend this architecture rather than create isolated admin applications.

---

# 19. Navigation Rules

Navigation visibility must be determined by capabilities.

Do not use hard-coded Position-name conditions.

Navigation hiding is not security.

Example:

A hidden `Users` navigation item does not authorize or deny the API.

The backend must independently enforce the relevant Permission and scope.

---

# 20. Designer / Figma Compatibility

The business experience architecture is independent from the visual design.

A future Designer may redesign:

- navigation
- dashboard
- tables
- forms
- Listing cards
- page composition
- typography
- colors
- spacing
- responsive behavior

without changing:

- Organization hierarchy
- provisioning authority
- authentication
- authorization
- Permission semantics
- resource scope
- marketplace visibility
- Request ownership
- reporting boundaries

Product behavior must not be encoded into visual components.

---

# 21. AF002 Direction

AF002 must not be treated as a generic collection of Organization CRUD screens.

The recommended next frontend milestone is:

## AF002 - Network Administration UI

Primary initial perspective:

System Administration

The sprint should begin exposing the Organization network operationally.

Expected design scope should be evaluated around:

- Franchise list
- Franchise creation
- Franchise details
- initial Franchise Admin provisioning
- Partner Agency visibility
- Organization hierarchy/context
- Organization status management

Exact AF002 scope must be approved in its sprint contract before implementation.

AF002 must not prematurely implement unrelated future modules.

---

# 22. Future Organization User Administration

A later frontend milestone should provide Organization-scoped User administration.

The same User-management architecture should adapt to:

- System scope
- Franchise scope
- Partner Agency scope

subject to effective Permissions.

This should not require separate User-management applications.

---

# 23. Security Principles

The following principles are mandatory:

1. Backend authorization is authoritative.
2. Frontend visibility is UX only.
3. Position names are not authorization rules.
4. Permission assignment must not enable privilege escalation.
5. Organization administrators may manage only permitted organizational scope.
6. Marketplace visibility does not grant administrative authority.
7. Marketplace visibility does not grant reporting authority.
8. Marketplace visibility does not grant commission visibility.
9. Cross-Organization Requests are valid platform behavior.
10. Sensitive internal Organization information remains scope-protected.

---

# 24. Architectural Summary

Directors Resale Platform operates as:

ONE PLATFORM
+
ONE ADMIN APPLICATION
+
SHARED MARKETPLACE INVENTORY
+
SCOPED ADMINISTRATION
+
DYNAMIC PERMISSIONS
+
ORGANIZATION-AWARE REPORTING

The fundamental distinction is:

Available Marketplace Inventory
→ shared across authenticated network participants

Administrative Authority
→ restricted by Permission and scope

This distinction is central to the purpose of Directors Resale Platform and must be preserved throughout future architecture and implementation.

---

# 25. Current Decision Status

Approved:

- One Admin Application
- System → Franchise → Partner Agency Organization hierarchy
- Permission-driven UI capability
- scope-controlled administration
- System-controlled Permission catalog
- Organization-level User administration within granted authority
- System provisioning of Franchises
- Franchise provisioning of permitted Partner Agencies
- initial Organization administrator concept
- global authenticated visibility of marketplace-eligible available Listings
- cross-Organization Listing Requests
- scoped reporting
- separation between marketplace visibility and administrative authority

Approved in `docs/architecture/LISTING_DOMAIN_ARCHITECTURE.md`, but not implemented:

- Listing publication and conceptual lifecycle
- internal, Owner, hold, sold, withdrawal, and archive behavior
- provenance, assignment, Owner privacy, marketplace eligibility, and integration boundaries

Still deferred for later domain/implementation decisions:

- exact Listing Permission codes
- Team scope implementation
- Request workflow details
- Deal workflow
- commission visibility rules
- commission calculations
- reporting metrics
- account invitation/activation mechanism
