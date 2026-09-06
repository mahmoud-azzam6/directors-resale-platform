# Directors Resale Platform
# Listing Domain Architecture

**Document Type:** Domain Architecture  
**Status:** APPROVED / NOT IMPLEMENTED
**MVP Scope:** Residential Resale  
**Applies To:** Property, Ownership, Listings, Owner Approval, Marketplace Publication, Holds, Sale Closing, and future Request/Deal/Transfer integrations

---

# 1. Purpose

This document is the canonical architecture source for the Listing domain. It defines approved domain boundaries, lifecycle, attribution, approval, privacy, marketplace, and future-integration rules without defining a physical database schema, API, frontend, or implementation sprint.

The approved physical data-model architecture is defined separately in `docs/architecture/LISTING_PHYSICAL_DATA_MODEL.md`. That document is also **APPROVED / NOT IMPLEMENTED** and remains conceptual rather than a committed Listing schema. BF013 implemented and verified only the approved Property & Ownership backend foundation; it excludes Listing workflows, rich Property Profile behavior, and frontend Property workflows.

The MVP serves Residential Resale while the core architecture remains ready for Commercial, Administrative, Medical, and future property categories.

# 2. Core Domain Boundaries

The platform keeps three independent concepts:

- **Global Physical Property Identity:** a System-only analytical identity for a persistent real-world asset.
- **Organization Property:** one Organization's independent operational representation of that asset.
- **Ownership:** the ownership state and history associated with that asset.
- **Listing:** a temporary market offering in a particular business and ownership context.

Listings reference Property and Ownership context. Future Deals reference Listings. Listing lifecycle, versions, attribution, assignment, approvals, holds, withdrawals, and sale outcomes must retain history. Listings and their business history are never hard-deleted.

Global reconciliation never merges or exposes Organization operational records. Each Organization Property, its Owners, Ownerships, Listings, documents, and history remain isolated. The detailed identity, reconciliation, ownership, versioning, media, Sale, and logical-constraint model is defined by the physical-model architecture.

The Listing domain does not own the future Request, Deal, Commission, Transfer, Team, Viewing, or Rental Management workflows.

# 3. Organization, Attribution, and Assignment

Each Listing distinguishes:

- **Managing Organization:** the Organization currently responsible for the Listing.
- **Created By:** the User who created the system record.
- **Originated By:** the User or Organization source that brought or sourced the Listing.
- **Assigned To:** the User currently handling the Listing operationally.

These facts are not interchangeable. Assignment and reassignment do not change origination, and reassignment history must be preserved. Transfer candidacy must never be derived from `Assigned To`.

Assignment remains inside the Managing Organization. A child Partner Agency is a separate Organization and is not automatically an eligible assignee for a Franchise Listing. Future Team assignment rules remain deferred.

# 4. Transfer Implications

User and Listing transfer workflows remain deferred to the future Transfer Engine. The Listing domain must preserve the provenance required to derive transfer candidacy later.

- A Listing originated by a Sales User may become a transfer candidate when that User moves Organizations.
- Transfer is not automatic and requires source Organization approval.
- If rejected, the Listing remains with the source Organization, responsibility is reassigned internally, and original attribution is preserved. The User identity may still move through its separate approved workflow.
- An Organization/Admin-originated Listing merely assigned to a departing Sales User is not a transfer candidate for that reason.

There is no arbitrary editable transfer-eligibility flag. Eligibility is derived from provenance rules by the future Transfer Engine.

# 5. Creation and Internal Approval

Authorized Users create and submit Listings according to effective capabilities, Organization scope, and resource relationship. Position names never grant Listing authority.

- A Franchise Listing receives internal approval from an authorized Franchise approver.
- A Partner Agency Listing receives internal approval from an authorized Partner Agency approver. Parent Franchise approval is not required for normal publication.
- Parent Franchises retain supervisory visibility over child Partner Agency activity, but this does not automatically expose Owner contacts, private documents, or modification authority.
- An Admin who creates or marks a Listing sold may also approve that action when holding the required capability. A mandatory four-eyes rule is not required; audit history must record each action.

# 6. Owner Approval

After internal approval, publication requires Owner approval where required by the workflow. The Owner is an external domain entity and need not be a platform User.

Approval is channel-agnostic. Phase 1 delivery uses email with a secure approval link; WhatsApp may be added later without changing the domain workflow.

Owner approval applies to a specific material Listing snapshot or version. Material changes invalidate approval and require appropriate internal review followed by new Owner approval. Material changes include asking/seller amount, seller-side commission terms, payment terms, and core unit identity/details.

Media, description, internal notes, and Assigned Sales changes are operational/non-material and do not automatically invalidate Owner approval.

Owner rejection records `OWNER_REJECTED` history and may include a reason. It never deletes the Listing. A revised Listing returns through appropriate internal review before another Owner approval request.

# 7. Conceptual Lifecycle

The approved conceptual states are:

- `DRAFT`
- `PENDING_INTERNAL_APPROVAL`
- `PENDING_OWNER_APPROVAL`
- `OWNER_REJECTED`
- `AVAILABLE`
- `HOLD`
- `SOLD_PENDING_APPROVAL`
- `SOLD`
- `WITHDRAWN`
- `ARCHIVED`

These are domain concepts, not an approved database enum or storage design.

Core publication flow:

`DRAFT -> PENDING_INTERNAL_APPROVAL -> PENDING_OWNER_APPROVAL -> AVAILABLE`

Rejection and revision:

`PENDING_OWNER_APPROVAL -> OWNER_REJECTED -> revision -> internal review -> new Owner approval request`

Operational flow permits `AVAILABLE <-> HOLD`, and `AVAILABLE` or `HOLD` may enter `SOLD_PENDING_APPROVAL`. Final `SOLD` requires Organization-authorized approval.

# 8. Hold

`HOLD` is explicit and is not automatically caused by the first Request.

- **Request-based Hold:** optionally linked to an existing Request.
- **Manual Hold:** may represent Owner instruction, external negotiation, documentation issue, price review, or another recorded reason.

A hold records conceptually the actor, time, reason, optional linked Request, optional notes, and optional expiry. MVP expiry does not require automatic release.

While held, the Listing remains marketplace-visible as temporarily unavailable, accepts no new Requests, and preserves existing Requests and history. Authorized assigned Users, Organization administrators, and System support may manage holds only within applicable capability and scope.

# 9. Sold and Sale Closing

Listings do not move directly from an operational state to final `SOLD`:

`AVAILABLE / HOLD -> SOLD_PENDING_APPROVAL -> authorized approval -> SOLD`

Partner Agency approval is sufficient for its Listing when performed by an authorized Partner Agency approver; Parent Franchise approval is not a mandatory second step.

Future closing records must support Listing, optional winning Request, buyer or buyer details, actual selling price and currency, sale date, seller-side commission snapshot, optional buyer-side commission snapshot, Managing Organization, marking User, and optional notes.

A sale may occur through a platform Request or externally. Winning Request is optional; an external sale must not create a fake Request. Other active Requests eventually close without deletion using an appropriate sold or externally-sold reason.

The broader platform defaults remain seller side 2.5% and buyer side 1.5%, subject to future Organization/Listing overrides. Closed Deal commission terms must eventually be snapshotted. Commission calculation remains outside this domain.

# 10. Withdrawal, Archive, and Reactivation

`WITHDRAWN` removes a Listing from active sale for an Owner/company decision or another recorded reason. Actor, time, reason, and optional notes are preserved. Future active Requests close with a withdrawn reason.

`ARCHIVED` is an administrative terminal/inactive action, not casual Sales deletion.

A returning withdrawn Listing reuses its identity and history rather than creating a duplicate. It cannot return directly to `AVAILABLE`; current internal and Owner approval requirements apply again.

# 11. Property Categories and Unit Types

Property Category and Unit Type are separate managed concepts.

MVP activates Residential. The architecture supports Commercial, Administrative, Medical, and future categories. Residential Unit Types may include Apartment, Villa, Townhouse, Twin House, Duplex, Penthouse, Chalet, and Studio.

Unit Types belong to a Property Category and support `Other / Not Listed`. A custom value does not automatically enter the global catalog; authorized catalog administration may later approve, normalize, map, merge, or retain it as custom.

The intended direction is hybrid: structured searchable core fields, organized type-specific extension attributes, and a configurable catalog/rule layer. A completely unstructured EAV-only architecture is not approved.

# 12. Development, Project, and Location Context

Listings may exist inside a Project/Compound or stand alone. Developer, Project/Compound, and Phase are optional; location is required.

Developer and Project/Compound are managed catalogs with `Not Listed` fallbacks. Proposed values must not block Listing creation and may later be approved, normalized, or merged. Phase remains flexible and optional for MVP.

Location follows a structured but flexible hierarchy:

`Country -> Governorate / Region -> City -> Area / District`

The catalog tolerates imperfect real-estate geography and proposed values. A Project may supply default context, but each Listing retains usable location context for standalone support. Precise address fields are private by default; marketplace output normally exposes approximate/searchable location.

# 13. Pricing, Payment, and Resale Context

A Listing's commercial instruction supports Currency, Seller Asking Amount, Price Negotiable, and payment status (`Fully Paid` or `Installments Remaining`). Current physical/profile facts belong to Organization Property and are preserved in immutable Listing material snapshots at approval/publication boundaries. Asking amount is not the same as total buyer financial exposure.

Where installments remain, the architecture supports original unit price, amount paid to developer, remaining developer balance, seller premium/overprice, total buyer commitment, next installment date and amount, frequency, and final installment date. Optional future obligations include maintenance balance, club fees, transfer fees, and other mandatory fees. A detailed installment schedule engine is not part of MVP architecture.

Residential resale context may include unit/delivery status, delivery date, finishing, furnishing, occupancy, developer transfer allowance, assignment fees, maintenance status, and key availability. Viewing workflows and Rental Management remain outside the Listing core.

# 14. Owner Model, Isolation, and Privacy

Owner is an external Organization-scoped individual or company/legal entity, not a Platform User. Conceptual data may include display/legal name, contact-person information, mobile, email, preferred contact method, notes, and status/context metadata. One Owner may participate in multiple Ownerships in the same Organization.

Matching contact information across different Organizations is not a business duplicate. Records must not be globally merged or disclosed to normal Users. Within one Organization, matching signals may suggest reuse but mobile/email are not blindly unique. Cross-Organization Owner matching is outside MVP.

Marketplace visibility never exposes Owner name, contacts, precise private address, internal notes, approval records, or private documents by default. Operational access requires capability, Organization scope, and resource relationship. Parent Franchise supervision does not automatically expose a Partner Agency's Owner contact database.

# 15. Media and Private Documents

Public/publishable media and private documents are separate.

Media may include photos, floor plans, and videos. Publishable media belongs to the reusable Organization Property media library; each Listing explicitly selects its published media. Upload does not make media marketplace-public.

Ownership documents, contracts, payment schedules, receipts, identification, and other legal/support files are private by default and never automatically become marketplace media. Missing publication materials do not block `DRAFT` creation; publication requirements are evaluated when moving toward `AVAILABLE`.

# 16. Authorization and Administrative Scope

Listing authorization follows BF012:

- authenticated actor
- effective capabilities
- Organization scope
- resource relationship

Conceptual capabilities will be required for viewing/managing Listings, creation, editing, submission, approval, assignment, holds, withdrawal, archive, marking/approving sold, Owner information, and private documents. Exact Permission codes are not approved.

System-authorized support may operate platform-wide according to capabilities. Parent Franchise supervision over child Partner Agency activity does not itself grant private-data or modification authority.

# 17. Global Marketplace Eligibility

All authenticated System, Franchise, and Partner Agency Users may browse marketplace-eligible Listings platform-wide. Organization hierarchy does not isolate the marketplace. Marketplace visibility does not grant administrative, Owner-data, document, reporting, commission, Organization, or User authority.

Eligibility is derived from lifecycle and publication rules, never an arbitrary `is_marketplace_visible` checkbox:

| State | Marketplace behavior |
|---|---|
| `DRAFT` | Hidden |
| `PENDING_INTERNAL_APPROVAL` | Hidden |
| `PENDING_OWNER_APPROVAL` | Hidden |
| `OWNER_REJECTED` | Hidden |
| `AVAILABLE` | Visible; accepts Requests |
| `HOLD` | Visible as temporarily unavailable; no new Requests |
| `SOLD_PENDING_APPROVAL` | Not in active marketplace |
| `SOLD` | Not in active marketplace |
| `WITHDRAWN` | Hidden |
| `ARCHIVED` | Hidden |

Publication requires correct internal state, current Owner approval where required, core unit data, pricing/payment data, usable location, and the approved publication-media threshold. Exact fields and media counts remain deferred.

# 18. Future Integration Boundaries

- **Requests:** separate future domain. `AVAILABLE` accepts Requests; `HOLD` does not. Holds and sold outcomes may reference Requests, and Request history is preserved.
- **Deals:** separate future domain that references Listings and preserves sale outcome.
- **Commissions:** separate future engine. Historical snapshots must prevent later default changes from rewriting closed history.
- **Transfers:** separate future engine deriving candidacy from provenance and preserving approvals/history.
- **Approvals:** internal, Owner, and sold approvals require audit history; delivery channels do not define the workflow.
- **Catalog administration:** future workflow for proposed Unit Type, Developer, Project, and location values.

# 19. Explicit Non-Goals and Deferred Decisions

This architecture does not implement or approve:

- physical SQL tables, columns, indexes, foreign keys, JSON structures, migrations, or enum storage; the approved conceptual physical model is documented separately
- APIs, routes, services, repositories, frontend pages, or implementation sprint scope
- exact Listing Permission codes or Team scope
- complete Request, Deal, Commission, Transfer, Viewing, Rental, or catalog-review workflows
- exact publication field lists or media counts
- automatic hold expiry/release
- production WhatsApp approval delivery
- historical sold-property marketplace/intelligence views

No Listing implementation sprint is approved by this document.
