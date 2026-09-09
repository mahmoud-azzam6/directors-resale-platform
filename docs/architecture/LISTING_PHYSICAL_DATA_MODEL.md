# Directors Resale Platform
# Listing Physical Data Model Architecture

**Document Type:** Physical Data Model Architecture
**Status:** APPROVED / NOT IMPLEMENTED
**MVP Scope:** Residential Resale
**Foundation Sprint:** BF013 - IMPLEMENTED AND VERIFIED / CLOSED

---

# 1. Purpose and Status

This document is the canonical source for the approved physical Listing data-model architecture. It refines the domain boundaries in `docs/architecture/LISTING_DOMAIN_ARCHITECTURE.md` without approving SQL tables, columns, indexes, constraints, migrations, APIs, or application behavior.

Names in this document identify conceptual entities and relationships. They are not a committed physical table list. Logical constraints may later be enforced by database design, Service/business logic, or both.

The Listing Domain Architecture and this broader Physical Listing Data Model Architecture are approved and not implemented. BF013 implemented and verified the limited Property & Ownership backend foundation only. BF013 does not implement Listing workflows, rich Property Profile behavior, or frontend Property workflows.

# 2. Identity and Organization Isolation

## 2.1 Persistent real-world identity

A real-world unit should retain a persistent identity whenever the platform can reliably determine that multiple records represent the same physical property. Ownership and Listings may change while that real-world identity remains.

Operational property records remain independent Organization-owned representations:

```text
SYSTEM-ONLY

Global Physical Property Identity
        |
        +-- Organization Property A
        +-- Organization Property B
        +-- Organization Property C
```

The Global Physical Property Identity is exclusively a System analytical and reconciliation layer. Confirming an identity match never merges, replaces, deduplicates, or transfers Organization Property records and never merges their Ownerships, Listings, documents, or history. It does not expose one Organization's record to another Organization.

Each Organization independently owns and operates its Property record, property data, Ownerships, Listings, documents, and history. Organization users receive no warning or indication that another Organization registered the same real-world unit.

Architecture and future interfaces should use **Confirm Identity Match**, **Link to Global Physical Identity**, **Reconciliation**, **Grouping**, **Unlink**, and **Relink**. They must not imply an operational Property merge.

## 2.2 System reconciliation

Cross-Organization matching may create System-only candidate matches with confidence and matching signals. Authorized System administration may confirm or reject a match and unlink or relink an Organization Property. Corrections preserve every Organization record and its history.

Conflicting observations remain independent. System intelligence may show, for example, that one Organization recorded 180 sqm and another recorded 175 sqm; it must not silently select a value or overwrite either record.

## 2.3 Organization Property

Organization Property is the operational property root and belongs to exactly one Organization. It stores that Organization's current known physical/profile information. Ownership, Listing, Property Media, and context-appropriate documents attach to Organization Property, not directly to Global Physical Property Identity.

Within one Organization, the platform may detect potential duplicate properties, encourage reuse, and allow an authorized user to confirm **This Is A Different Property**. The model must not depend on a blind hard unique constraint such as Project + Building + Unit Number because property data may be incomplete or inconsistent. Across Organizations, no duplicate warning or disclosure is permitted.

# 3. Owners and Ownership

## 3.1 Owner

Owner is an Organization-scoped external party, not a Platform User. It may represent an individual or a company/legal entity, including optional contact-person information. The future schema must not assume every Owner has only `first_name` and `last_name`.

Within the same Organization, normalized mobile or email may be matching signals. The platform may suggest **Use Existing Owner** or **Different Person**, but mobile and email are not blindly unique: family, shared, company, and office contact details may repeat.

Cross-Organization Owner matching or merging is not part of MVP. Global property reconciliation does not reconcile Owners. Owner PII remains Organization-isolated.

## 3.2 Ownership history and parties

Ownership is an append-oriented, Organization-scoped historical relationship between one Organization Property and one or more Organization Owner records. It records the platform's known ownership context; it is not legal-title verification and does not make the platform a land registry.

An Organization Property has zero or one current Ownership state. Co-owners are parties within that single Ownership rather than separate current Ownerships. Ownership follows `CURRENT -> CLOSED`; closed Ownership and its parties remain historical and immutable through normal operations.

One Ownership may have multiple Ownership Parties. Shares may be recorded when known. If all shares are known as complete percentages, they should logically total 100%. Missing shares must not by themselves block recording the Ownership.

One Ownership Party may be designated Authorized Acting Owner after the Organization records that co-owner authority was confirmed through its operational process. This is a historical designation referencing the Ownership Party, not a simple Owner reference on Ownership. It retains authority basis/source, recording actor/time, optional notes, and start/end/current semantics. At most one designation is current per Ownership; changing it ends the previous designation and creates another, and no designation remains active beyond its Ownership. It does not alter ownership shares. In MVP, the Authorized Acting Owner must be one of the recorded Ownership Parties; a general legal representative or power-of-attorney engine is deferred.

Only the designated Authorized Acting Owner may need to receive Listing Owner Approval for that Ownership context.

## 3.3 Sale and ownership transfer

These are separate business events:

```text
Listing SOLD != Sale Recorded != Ownership Transfer Confirmed
```

A finalized Sale does not replace seller Ownership. Seller Ownership remains in the recorded chain/current recorded state until a separate Ownership Transfer Confirmation closes it historically and establishes a new Ownership using the confirmed transfer date. Prior history is never erased.

Buyer data captured at Sale Closing is a transaction snapshot, not automatically an Owner record. On explicit transfer confirmation, an appropriate same-Organization Owner may be reused or a new Organization Owner created and later enriched. An external Sale with unknown buyer data may still be recorded without changing Ownership.

# 4. Property Profile, Catalogs, and Attributes

## 4.1 Property data versus Listing data

Rich Property Profile behavior is defined canonically by `docs/architecture/PROPERTY_PROFILE_ARCHITECTURE.md`. Property Catalog and Data Governance behavior is defined canonically by `docs/architecture/PROPERTY_CATALOG_ARCHITECTURE.md`. Both architectures are **APPROVED / NOT IMPLEMENTED**.

Organization Property remains the lean BF013 operational aggregate root. Rich profile data extends it through core relations, structured Measurements, typed canonical Attributes, free-form Additional Information, reusable Property Media, derived completeness, and append-only Property Activity. **Add Unit** is business/UI terminology; the backend domain remains Organization Property.

Listing holds the current commercial sale instruction: asking amount, currency, negotiability, payment state, amount paid, remaining developer balance, seller premium/overprice, seller asking amount, installment summary, relevant fees, commission terms, marketing description, and lifecycle state.

Property completeness does not create a Listing. Price and commercial terms are not Property completeness requirements. Current Property edits must not rewrite immutable historical Listing material snapshots.

## 4.2 Canonical catalogs and private proposals

System-managed canonical catalogs cover Property Categories, Unit Types, Geographic Locations, Developers, Projects/Compounds, Project Phases, Measurement Definitions, Attribute Definitions, and Attribute Options. Geographic Location and Development Structure are separate domains. Development follows Developer to Project to Phase, but every level is optional for Organization Property.

Missing values use Organization-private typed Catalog Proposals. Authorized System Admin resolves a proposal once as `APPROVED_NEW`, `MERGED_EXISTING`, or `REJECTED`. Parent Franchise access does not automatically expose a Partner Agency proposal, and chained Developer, Project, and Phase approvals do not cascade.

Location uses a flexible canonical hierarchy such as Country, Governorate/Region, City, and Area/District without requiring a universal depth. A canonical Project may derive Developer and geographic context; Organization Property avoids duplicated truth where context is derived. A standalone Organization Property may be complete using sufficient geographic context without Developer, Project, or Phase.

Catalog deactivation prevents new selection while preserving references. Canonical data governance and proposal review belong to System Admin. Operational users consume canonical values and may submit proposals but cannot mutate platform-wide catalogs.

## 4.3 Hybrid attributes

The hybrid model avoids both a single oversized Property table and unrestricted Organization-defined EAV. Canonical searchable data remains structured rather than hidden in unrestricted JSON.

Unit Type belongs to Property Category. Versioned Unit Type Configuration drives Measurement and Attribute rules. Structural changes create new versions; completed Organization Properties retain their accepted version, while incomplete Organization Properties use the current active version. Rules are `REQUIRED` or `OPTIONAL`; absence of an Attribute rule means not applicable. Attributes support `INTEGER`, `DECIMAL`, `BOOLEAN`, `TEXT`, `ENUM`, and `DATE`, with ENUM values using canonical Attribute Options.

The approved, not-implemented conceptual components are:

- lean `organization_properties`
- `property_categories`, `unit_types`, and `unit_type_configuration_versions`
- `measurement_definitions`, `unit_type_measurement_rules`, and `property_measurements`
- `attribute_definitions`, `attribute_options`, `unit_type_attribute_rules`, and `property_attribute_values`
- `property_additional_information`
- `geographic_locations`, `developers`, `projects`, and `project_phases`
- `property_catalog_proposals` with typed context and historical Property-to-Proposal links
- `property_media` and `property_media_variants`
- optional derived `property_completeness` snapshot/cache
- append-only `property_activities` and `property_catalog_activities`

These are conceptual physical-model names, not claims that SQL tables or migrations exist.

# 5. Listing Identity, Versions, and Lifecycle

## 5.1 Listing identity and active uniqueness

An Organization Property may have many historical Listings but only one active marketing Listing at a time. Active context includes `DRAFT`, `PENDING_INTERNAL_APPROVAL`, `PENDING_OWNER_APPROVAL`, `OWNER_REJECTED`, `AVAILABLE`, `HOLD`, and `SOLD_PENDING_APPROVAL`. Closed/inactive context includes `SOLD`, `WITHDRAWN`, and `ARCHIVED`.

A withdrawn Listing returning to market reuses its Listing identity through the approved reactivation workflow. Independent active Listings belonging to different Organizations are allowed even when linked to the same Global Physical Property Identity.

## 5.2 Material versions

The model distinguishes:

1. Editable working state.
2. Immutable material snapshots at approval/publication boundaries.
3. Operational activity and history.

A material version is not created for every draft save. Material changes include price, payment terms, remaining balance, seller premium, commission, core unit identity, and important structural/property facts. Internal Approval and Owner Approval reference a specific material Listing Version. Once a version enters an approval/publication boundary, it is immutable historical truth.

For a proposed material change to an approved `AVAILABLE` Listing, a candidate material version is created while the previous approved version is preserved. The candidate receives required internal and fresh Owner Approval and replaces the published version atomically after approval. The current approved version may remain published during review, unless an authorized workflow suspends or withdraws unsafe/materially incorrect publication.

Photos, marketing description, internal notes, and assignment changes are non-material and auditable; they do not automatically invalidate material approval.

## 5.3 Current state and history

Listing stores current operational lifecycle state for efficient queries and also retains append-oriented lifecycle/activity history. Full event sourcing is not approved; current state must not require replaying history. State transitions are later enforced through Service/business logic, not arbitrary direct status updates.

Approved states are:

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

Important activity includes creation, submission, internal decisions, Owner Approval actions, publication, assignment changes, holds, material revisions, sold actions, withdrawal, reactivation, and archive. History retains, where relevant, actor, timestamp, action, previous/resulting state, resource/version, and reason/notes.

# 6. Holds, Requests, and Marketplace

Repeated Holds preserve distinct history rather than overwriting one status value. A Hold may optionally reference a Request or be manual/external. It may retain holder/time, reason, linked Request, notes, expiry, release actor, and release time. Complex automatic expiry/release is deferred.

Requests remain separate from lifecycle. Multiple Requests may coexist while `AVAILABLE`; the first Request never automatically creates a Hold. While `HOLD`, the Listing remains globally visible as temporarily unavailable, new Requests are disabled, and existing Requests are preserved.

Marketplace eligibility is derived from lifecycle and approval/publication eligibility, not a freely editable `is_marketplace_visible` flag. `AVAILABLE` is globally visible and accepts Requests; `HOLD` is globally visible without new Requests. Other review, sold, withdrawn, and archived states are not in the active marketplace. Global marketplace visibility remains separate from Organization administrative scope.

# 7. Provenance, Management, and Assignment

Listing distinguishes Managing Organization, Created By, Originated By, and Assigned To.

- **Created By** is immutable historical attribution.
- **Originated By** is protected historical provenance and supports User or Organization origin; exceptional correction is auditable.
- **Assigned To** is the current operational handler and may be null or change with retained assignment history.
- **Managing Organization** matches the Organization Property's Organization and cannot change through ordinary Listing editing.

Assignment history may retain assigned User, assignment/unassignment time, changing actor, and optional reason/notes. Assignment is limited to an eligible User in the Managing Organization. A child Partner Agency is a separate Organization and is not automatically a Franchise assignment target. Cross-Organization movement belongs to the future Transfer Engine.

# 8. Media and Private Documents

## 8.1 Property Media Library

Public/publishable media belongs to a reusable Organization Property media library. Each Listing explicitly selects which Property Media items it publishes; a new Listing never automatically republishes all historical media. Media publication history remains auditable without copying every binary into every material Listing Version.

## 8.2 Security separation

Publishable photos, floor plans, and videos are distinct from private documents such as Owner ID, ownership evidence, developer contracts, payment schedules, receipts, authorization evidence, and legal records. Private Documents may attach to Owner, Ownership, Organization Property, Listing, or approval context as appropriate.

Marketplace visibility never publishes private documents. Parent Franchise supervision does not automatically grant Partner Agency Owner PII or private-document access; capabilities, Organization/resource scope, and authorized resource relationship remain required.

Private documents must support at least PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, and PNG in MVP. Approximately 25 MB per document is a configurable proposed default, not a domain constant. ZIP and executable formats require future approval. Audit-required files are not silently overwritten or hard-deleted; conceptual states may include Active, Superseded, and Archived. Exact statuses and Organization quotas are deferred.

## 8.3 Image and video policy

Uploaded images are temporary sources. The approved conceptual pipeline validates MIME, decodeability, dimensions, and security; normalizes orientation; strips EXIF, GPS, and unnecessary metadata; generates processed WebP variants; verifies every required variant; marks the item `READY`; and then deletes the temporary original. Only processed variants are retained.

Proposed configurable V1 defaults accept JPEG, PNG, and WebP but reject SVG and animated images; allow at most 10 MB per image and 30 images per Organization Property; and require minimum dimensions of 600 x 600. Proposed processed variants are 400 px thumbnail, 800 px card, and 1600 px large.

At most one active cover exists per Organization Property. Deleting the cover selects the first ordered `READY` image. Removing the last `READY` image may make the Organization Property incomplete.

Proposed video defaults are primarily MP4, approximately 250 MB, approximately three minutes, 1080p recommended, and approximately five videos per Organization Property. Storage and delivery are abstracted from the PHP application server for future object storage, CDN, and transcoding services; that infrastructure is not part of this documentation task.

# 9. Sale Closing and Transfer Boundary

Sale Closing is a distinct domain record/context associated with Listing, not mutable `sold_*` fields on Listing. It conceptually captures Listing, Sale Source (`PLATFORM_REQUEST` or `EXTERNAL`), optional Winning Request, actual price/currency, sale date, buyer transaction snapshot, notes, marking actor, Managing Organization, approval/finalization information, and future seller/buyer commission snapshots. An external Sale never requires a fake Request.

Sold flow remains:

```text
AVAILABLE / HOLD -> SOLD_PENDING_APPROVAL -> authorized Organization approval -> SOLD
```

Authorized Partner Agency approval is sufficient for its Listing; Parent Franchise supervision adds no mandatory approval. Actor audit is required. Finalized material Sale corrections retain actor, time, old value, new value, and reason rather than silently changing history.

Sale Closing is architecture-ready for transaction commission snapshots so later default changes cannot rewrite historical terms. Calculation behavior and the Commission Engine remain deferred.

Ownership Transfer Confirmation is a distinct later workflow referencing the relevant Sale, Property, and Ownership chain. It historically closes prior Ownership and reuses or creates the new Organization Owner/Ownership as appropriate. It is never a hidden side effect of Mark Sold.

# 10. Conceptual Relationship Map

```text
SYSTEM-ONLY

Global Physical Property Identity
        |
        +----------------------+
        |                      |
Organization Property A   Organization Property B
        |
        +-- Current Property/Profile Data
        |       |
        |       +-- Category / Unit Type Configuration Version
        |       +-- Geographic / Optional Development Context
        |       +-- Measurements
        |       +-- Typed Attributes
        |       +-- Additional Information
        |       +-- Private Catalog Proposal Links
        |       +-- Derived Completeness
        |       +-- Property Activity
        |
        +-- Ownership History
        |       |
        |       +-- Ownership Parties
        |               |
        |               +-- Organization Owners
        |               +-- Authorized Acting Owner
        |
        +-- Property Media Library
        |       |
        |       +-- Processed Media Variants
        |       +-- Listing Published Media Selection
        |
        +-- Private Documents as applicable
        |
        +-- Listing History
                |
                +-- Current Listing
                +-- Material Versions
                |       |
                |       +-- Internal Approval
                |       +-- Owner Approval
                |
                +-- Lifecycle / Activity History
                +-- Assignment History
                +-- Requests
                +-- Holds
                +-- Sale Closing
                        |
                        +-- Ownership Transfer Confirmation
                                |
                                +-- New Ownership
```

This map is conceptual architecture, not an approved SQL table list.

# 11. Logical Constraints

These constraints are architectural invariants. Future implementation design determines whether each belongs in database constraints, Service/business logic, or both.

1. Organization Property belongs to exactly one Organization.
2. Owner belongs to exactly one Organization.
3. Ownership belongs to one Organization Property.
4. Ownership Parties reference Owners in the same Organization context as the Organization Property.
5. Authorized Acting Owner is one of the Ownership Parties in MVP.
6. Listing belongs to one Organization Property.
7. Listing Managing Organization matches the Organization Property's Organization.
8. Listing Ownership context belongs to the same Organization Property and Organization context.
9. Assigned User belongs to the Managing Organization and satisfies future eligibility/capability rules.
10. Only one active Listing exists per Organization Property within one Organization.
11. Different Organizations may independently have active Listings for the same Global Physical Property Identity.
12. Global identity reconciliation never merges operational Organization records.
13. A Winning Request, when present, belongs to the Sale's Listing.
14. Sale Closing belongs to its relevant Listing.
15. Ownership Transfer references the relevant Sale, Property, and Ownership chain.
16. Historical versions, Ownership, lifecycle, assignment, and finalized transaction history are not silently overwritten.
17. Cross-Organization Property duplicate information is not disclosed to Organization users.
18. Cross-Organization Owner matching is outside MVP.
19. Unit Type belongs to the selected Property Category.
20. Canonical and pending Proposal values cannot both represent the same semantic role at once.
21. Phase belongs to the selected Project.
22. At most one Unit Type Configuration Version is active.
23. One Measurement value exists per Definition per Organization Property.
24. At most one Primary Measurement rule exists per Configuration Version.
25. One Attribute value exists per Definition per Organization Property.
26. An ENUM option belongs to its Attribute Definition, and typed values match the Definition data type.
27. A Catalog Proposal belongs to exactly one Organization, resolves exactly once, and resolves to a target matching its type.
28. At most one active cover image exists per Organization Property.
29. `READY` media requires every required processed variant to be verified.
30. A completeness snapshot is derived/cacheable and never independent truth.
31. Property and Catalog Activity history is append-only and must not expose private Owner/Ownership data.
32. Catalog deactivation prevents new selection while preserving historical and current references.

# 12. Deferred and Non-Implemented Work

The architecture is ready for, but does not implement or authorize:

- Listing workflows or any unapproved future Listing implementation sprint
- BF013 work beyond the Property & Ownership Foundation contract
- Rich Property Profile and Property Catalog migrations, tables, APIs, services, seed scripts, and dynamic form definitions
- Add Unit and System Admin Catalog/Proposal Review frontend workflows
- full Property matching algorithms, advanced confidence scoring, or automated reconciliation
- cross-Organization Owner matching
- legal-title verification or Land Registry integration
- general legal representative/power-of-attorney workflows
- advanced media transcoding, CDN/provider implementation, or Organization storage quotas
- automatic Hold expiry/release
- detailed installment schedules
- Commission Engine or Transfer Engine
- advanced Ownership Transfer workflow
- WhatsApp Owner Approval
- historical sold marketplace sections
- exact APIs, routes, Permission codes, UI, or implementation tests
