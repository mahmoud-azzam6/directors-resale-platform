# Directors Resale Platform
# Rich Property Profile Architecture

**Document Type:** Domain Architecture
**Status:** APPROVED / NOT IMPLEMENTED
**Foundation:** BF013 - IMPLEMENTED AND VERIFIED / CLOSED

---

# 1. Purpose and Boundary

This document is the canonical source for Rich Property Profile behavior. It defines approved domain, completeness, persistence, media, privacy, and future Admin workflow boundaries without approving migrations, APIs, frontend implementation, or a subsequent sprint.

`OrganizationProperty` remains the BF013 operational aggregate root. **Add Unit** is business and UI terminology; the backend domain remains **Organization Property**. Property and Listing are separate domains. Completing an Organization Property never creates a Listing, and price or commercial sale terms are Listing data rather than Property completeness requirements.

The broader Listing Domain remains **APPROVED / NOT IMPLEMENTED**. The Rich Property Profile, Property Media processing, Add Unit UI, and frontend Property workflow are not implemented.

# 2. Setup and Completeness

Property setup uses derived states:

- `INCOMPLETE`
- `PENDING_REVIEW`
- `COMPLETE`

These are derived setup/completeness states, not Property lifecycle statuses. BF013 lifecycle remains `ACTIVE` or `ARCHIVED`.

- `ACTIVE + COMPLETE` appears in **Active Properties**.
- `ACTIVE + INCOMPLETE` or `ACTIVE + PENDING_REVIEW` appears in **Drafts**.

Minimum completeness broadly requires:

- Property Category
- resolved Unit Type where required
- sufficient geographic context
- required primary measurement
- type-required essential attributes
- current Ownership
- at least one `READY` Property image

Developer, Project, and Phase are optional. A standalone Organization Property may be `COMPLETE` using sufficient geographic location without Developer, Project, or Phase.

Internal identifiers such as Unit No, Building, Block, Internal Label, Company Reference, and Developer Reference are optional and private by default.

The backend is the sole authority for validation and completeness. Its completeness result provides:

- state
- completed and total requirement counts
- machine-readable missing requirements
- blocking review reason codes
- a derived resume hint when useful

An optional persisted completeness snapshot may optimize reads, but it is derived/cacheable and never independent truth.

The backend re-evaluates completeness after business changes that may affect requirements, including:

- Property classification or Unit Type changes
- Catalog Proposal resolution affecting required classification or context
- Measurement changes
- required typed Attribute changes
- current Ownership changes
- `READY` Property Media changes

Completeness remains derived from authoritative persisted data plus the applicable Unit Type Configuration version. If a persisted completeness snapshot is implemented later, it remains only a cache/projection and never becomes independent truth.

# 3. Structured Profile Model

The approved profile model is hybrid:

- core Organization Property relations
- structured Measurements
- typed canonical Attributes
- free-form Additional Information

Canonical searchable facts must not be hidden in unrestricted JSON. Measurements and Attributes are driven by the accepted version of Unit Type Configuration.

Applicable Measurement and Attribute rules use `REQUIRED` or `OPTIONAL`. Absence of an Attribute rule means `NOT APPLICABLE`.

Typed Attributes support `INTEGER`, `DECIMAL`, `BOOLEAN`, `TEXT`, `ENUM`, and `DATE`; `ENUM` values reference canonical Attribute Options.

The frontend consumes backend dynamic form definitions and must not hardcode Apartment-, Villa-, or other Unit Type-specific fields.

# 4. Progressive Persistence and Add Unit

Property setup uses progressive persistence:

- no `DRAFT` Property lifecycle status
- no separate temporary Property entity
- no long-running database transaction across wizard steps
- an incomplete persisted Organization Property is resumable

The conceptual Add Unit experience is Arabic-first and RTL, with these steps:

1. **بيانات الوحدة**
2. **المالك والملكية**
3. **الصور والمراجعة**

Backend and domain terminology remains English. Ownership creation and maintenance continue to use the BF013 Ownership APIs and domain boundaries.

# 5. Property Media

Property Media is a reusable Organization Property media library. A future Listing selects an explicit subset for publication; it never implicitly publishes the whole library. Private Documents are a separate security and storage concern.

Accepted image inputs are JPEG, PNG, and WebP. SVG and animated images are not accepted. Proposed configurable V1 defaults are:

- maximum 10 MB per image
- maximum 30 images per Organization Property
- minimum dimensions 600 x 600

The approved conceptual processing pipeline is:

1. retain the original upload temporarily only
2. validate MIME type, decodeability, dimensions, and security policy
3. normalize orientation
4. strip EXIF, GPS, and unnecessary metadata
5. generate processed WebP variants
6. verify every required variant
7. mark the media item `READY`
8. delete the temporary original after successful verification

Proposed variants are a 400 px thumbnail, 800 px card image, and 1600 px large image. Variant processing preserves the source aspect ratio, and a smaller source image is not unnecessarily upscaled merely to reach a configured variant width. Only processed variants are retained; there is no retained original or Master WebP requirement.

At most one active cover image exists per Organization Property. Deleting the cover selects the first ordered `READY` image. Removing the last `READY` image may make the Organization Property incomplete.

# 6. State, Activity, and Privacy

The architecture uses current-state persistence plus append-only Property Activity, not full event sourcing. Activity captures meaningful changes without requiring state replay.

Owner and Ownership remain `private_organization` data. Property Activity must not leak Owner PII, private Ownership details, private catalog proposals, or cross-Organization information.

Future marketplace and public Listing APIs must use explicit public projections. They must never expose raw `OrganizationProperty` aggregates.

# 7. Conceptual Physical Components

The approved, not-implemented conceptual model includes:

- lean `organization_properties` root extended through relations rather than a wide profile row
- `property_measurements`
- `property_attribute_values`
- `property_additional_information`
- `property_media`
- `property_media_variants`
- optional derived `property_completeness` snapshot/cache
- `property_activities`

These names document conceptual physical boundaries only. They do not claim that tables, migrations, APIs, media infrastructure, or frontend workflows exist.

# 8. Deferred Work

Still deferred are rich Property Profile implementation, developer/project/location catalogs, typed profile persistence, media processing and storage infrastructure, Private Documents, Add Unit UI, frontend Property administration, Listings and Listing Versions, approvals, Marketplace, Requests, HOLD, Sale Closing, Ownership Transfer, matching, commissions, and broader audit/event architecture. No subsequent sprint is selected.
