# BF014 - Canonical Property Catalog Foundation

**Status: IN PROGRESS**

**Foundation:** BF013 - IMPLEMENTED AND VERIFIED / CLOSED

**Architecture:** Property Catalog - APPROVED / NOT IMPLEMENTED; Rich Property Profile - APPROVED / NOT IMPLEMENTED; Listing Domain - APPROVED / NOT IMPLEMENTED.

**Sprint Family:** BF - Backend / Domain / Foundation

**Milestone:** Milestone 2 - Core Business

**Baseline:** Current HEAD `42d4906` of `feature/listing-domain-architecture`.

**Selected/documented:** 2026-09-13

BF014 is the selected current Backend sprint and remains **IN PROGRESS**. BF014.1 through BF014.5 are **IMPLEMENTED AND VERIFIED / CLOSED**. BF014.6 is **NEXT / NOT STARTED**; BF014.7-BF014.8 remain **NOT STARTED**, and BF015 or subsequent sprints are not selected. This parent contract remains authoritative for scope and future BF014 work.

## Authority and preserved boundaries

Canonical sources:

- [PROPERTY_CATALOG_ARCHITECTURE.md](../architecture/PROPERTY_CATALOG_ARCHITECTURE.md)
- [PROPERTY_PROFILE_ARCHITECTURE.md](../architecture/PROPERTY_PROFILE_ARCHITECTURE.md)
- [LISTING_PHYSICAL_DATA_MODEL.md](../architecture/LISTING_PHYSICAL_DATA_MODEL.md)
- [SEED_DATA.md](../database/SEED_DATA.md)
- [DATABASE_ARCHITECTURE.md](../architecture/DATABASE_ARCHITECTURE.md)
- [MASTER_INDEX.md](../MASTER_INDEX.md)
- [BUSINESS_RULES.md](../product/BUSINESS_RULES.md)
- [CURRENT_SYSTEM_STATE.md](../development/CURRENT_SYSTEM_STATE.md)
- [IMPLEMENTATION_PLAN.md](../development/IMPLEMENTATION_PLAN.md)
- [SPRINT_LOG.md](../development/SPRINT_LOG.md)
- [AI_CONTEXT.md](../../AI_CONTEXT.md)
- [PROJECT_STATUS.md](../../PROJECT_STATUS.md)
- [Closed BF013 contract](BF013-property-ownership-foundation.md)

The matrices below are the locked BF014 dataset contract. The seed strategy links here rather than maintaining a second dataset. Older architecture statements that no subsequent sprint is selected describe architecture approval time; this contract records the later BF014 selection. Broader approved architecture includes work explicitly excluded from BF014.

BF014 tightens the general architecture safe-delete allowance: SYSTEM_SEED records can never be hard-deleted. Only unreferenced, audit-safe SYSTEM_ADMIN records qualify for safe Delete. This explicit sprint-specific refinement takes precedence for BF014 without changing the architecture files or BF013 semantics.

OrganizationProperty remains the operational aggregate root with BF013 ACTIVE / ARCHIVED lifecycle. Owner and Ownership remain private_organization; Global Physical Identity remains System-only. Future Property completeness never creates a Listing; price and commercial terms remain Listing data. Property Media stays deferred; no retained original or Master WebP requirement is introduced.

The schema, repositories and BF014.3 services described below are implemented where recorded in their canonical closure records. APIs, seed execution and the remaining BF014 scope are future deliverables.

## BF014 OBJECTIVE

The future BF014 implementation covers only the canonical Property catalog and configuration
foundation required by future Property Profile work.

Conceptual layers:

```text
Property Category
    -> Unit Type
        -> Configuration Version
            -> Measurement Rules
            -> Attribute Rules

Geographic Location

Developer
    -> Project
        -> Phase
```

BF014 builds the vocabulary and rules.

BF014 does NOT yet store rich profile values against an
Organization Property.

## BF014 IN-SCOPE DOMAIN

The future implementation scope is:

Canonical Classification:

- Property Category
- Unit Type

Configuration:

- Unit Type Configuration Version
- Measurement Definition
- Unit Type Measurement Rule
- Attribute Definition
- Attribute Option
- Unit Type Attribute Rule

Reference Data:

- Geographic Location
- Developer
- Project
- Project Phase

Backend Management:

- System Admin catalog management
- read APIs for authorized operational users
- dynamic property-form configuration projection

Seed Foundation:

- versioned seed execution/tracking
- stable canonical codes
- baseline canonical data
- provenance of seeded vs Admin-created records

## EXPLICIT BF014 NON-GOALS

BF014 must NOT implement:

- Catalog Proposals
- Catalog Proposal resolution/rebinding
- OrganizationProperty classification assignment
- Property Measurements
- Property Attribute Values
- Additional Information
- Property Completeness Engine
- completeness snapshots
- Property Activity
- Property Media
- image processing
- storage providers
- changes to BF013 Owner/Ownership behavior
- Add Unit frontend
- Drafts frontend
- Active Properties frontend
- Property Details frontend
- Listing
- Listing Readiness
- Owner Approval
- Marketplace
- Requests
- HOLD
- Sale Closing
- Ownership Transfer
- Commission Engine
- Notification Engine
- Reports
- Matching Engine
- Transfer Engine

## BASELINE PROPERTY CATEGORIES

| Code | Arabic | English |
| --- | --- | --- |
| RESIDENTIAL | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â³ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â  | Residential |
| COMMERCIAL | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚ÂªÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â±ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â  | Commercial |
| ADMINISTRATIVE | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¥ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¯ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â±ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â  | Administrative |
| MEDICAL | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â·ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¨ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â  | Medical |
| LAND | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â£ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â±ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¶ | Land |
| OTHER | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â£ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â®ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â±ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â° | Other |

Stable machine codes must not change when labels change.

## BASELINE UNIT TYPES

| Code | Category | Arabic | English |
| --- | --- | --- | --- |
| APARTMENT | RESIDENTIAL | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â´ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© | Apartment |
| DUPLEX | RESIDENTIAL | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¯ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¹Ã¢â‚¬Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¨ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â³ | Duplex |
| PENTHOUSE | RESIDENTIAL | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¨ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚ÂªÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¡ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¹Ã¢â‚¬Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â³ | Penthouse |
| STUDIO | RESIDENTIAL | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â³ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚ÂªÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¹Ã¢â‚¬Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¯ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¹Ã¢â‚¬Â  | Studio |
| VILLA | RESIDENTIAL | ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€šÃ‚ÂÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ | Villa |
| TOWNHOUSE | RESIDENTIAL | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚ÂªÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¹Ã¢â‚¬Â ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â  ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¡ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¹Ã¢â‚¬Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â³ | Townhouse |
| TWIN_HOUSE | RESIDENTIAL | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚ÂªÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¹Ã¢â‚¬Â ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â  ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¡ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¹Ã¢â‚¬Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â³ | Twin House |
| CHALET | RESIDENTIAL | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â´ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¡ | Chalet |
| RETAIL_SHOP | COMMERCIAL | ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â­ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚ÂªÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â±ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â  | Retail Shop |
| COMMERCIAL_UNIT | COMMERCIAL | ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¹Ã¢â‚¬Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â­ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¯ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚ÂªÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â±ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© | Commercial Unit |
| WAREHOUSE | COMMERCIAL | ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â³ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚ÂªÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¹Ã¢â‚¬Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¯ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¹ | Warehouse |
| OFFICE | ADMINISTRATIVE | ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚ÂªÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¨ | Office |
| ADMINISTRATIVE_UNIT | ADMINISTRATIVE | ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¹Ã¢â‚¬Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â­ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¯ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¥ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¯ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â±ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© | Administrative Unit |
| CLINIC | MEDICAL | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¹ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¯ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© | Clinic |
| MEDICAL_CENTER | MEDICAL | ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â±ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â² ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â·ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¨ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â  | Medical Center |
| RESIDENTIAL_LAND | LAND | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â£ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â±ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¶ ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â³ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© | Residential Land |
| COMMERCIAL_LAND | LAND | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â£ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â±ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¶ ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚ÂªÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â±ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© | Commercial Land |
| AGRICULTURAL_LAND | LAND | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â£ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â±ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¶ ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â²ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â±ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¹ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© | Agricultural Land |
| INDUSTRIAL_LAND | LAND | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â£ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â±ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¶ ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚ÂµÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¹ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© | Industrial Land |
| OTHER | OTHER | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â£ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â®ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â±ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â° | Other |

Exactly these 20 baseline Unit Types are locked. No additional baseline Unit Types are included.

## MEASUREMENT DEFINITIONS

| Code | Arabic | English |
| --- | --- | --- |
| BUILT_UP_AREA | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â³ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â­ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¨ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© | Built-up Area |
| PLOT_AREA | ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â³ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â­ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â£ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â±ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¶ | Plot Area |
| NET_AREA | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â³ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â­ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚ÂµÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€šÃ‚ÂÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© | Net Area |
| GARDEN_AREA | ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â³ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â­ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â­ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¯ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© | Garden Area |
| TERRACE_AREA | ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â³ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â­ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚ÂªÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â±ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â³ / ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¨ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¹Ã¢â‚¬Â ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© | Terrace / Balcony Area |
| ROOF_AREA | ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â³ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â­ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â³ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â·ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â­ | Roof Area |

V1 baseline unit:
SQM

Architecture must remain extensible to additional units later.

Future Property measurement values must use decimal semantics, never float.

## MEASUREMENT CONFIGURATION MATRIX

The following matrix is normative:

```text
Residential:

APARTMENT
Primary Required: BUILT_UP_AREA
Optional: NET_AREA, GARDEN_AREA, TERRACE_AREA

DUPLEX
Primary Required: BUILT_UP_AREA
Optional: NET_AREA, GARDEN_AREA, TERRACE_AREA, ROOF_AREA

PENTHOUSE
Primary Required: BUILT_UP_AREA
Optional: NET_AREA, TERRACE_AREA, ROOF_AREA

STUDIO
Primary Required: BUILT_UP_AREA
Optional: NET_AREA, TERRACE_AREA

VILLA
Primary Required: BUILT_UP_AREA
Optional: PLOT_AREA, NET_AREA, GARDEN_AREA, TERRACE_AREA, ROOF_AREA

TOWNHOUSE
Primary Required: BUILT_UP_AREA
Optional: PLOT_AREA, NET_AREA, GARDEN_AREA, TERRACE_AREA, ROOF_AREA

TWIN_HOUSE
Primary Required: BUILT_UP_AREA
Optional: PLOT_AREA, NET_AREA, GARDEN_AREA, TERRACE_AREA, ROOF_AREA

CHALET
Primary Required: BUILT_UP_AREA
Optional: NET_AREA, GARDEN_AREA, TERRACE_AREA, ROOF_AREA

Commercial:

RETAIL_SHOP
Primary Required: BUILT_UP_AREA
Optional: NET_AREA, TERRACE_AREA

COMMERCIAL_UNIT
Primary Required: BUILT_UP_AREA
Optional: NET_AREA

WAREHOUSE
Primary Required: BUILT_UP_AREA
Optional: PLOT_AREA, NET_AREA

Administrative:

OFFICE
Primary Required: BUILT_UP_AREA
Optional: NET_AREA, TERRACE_AREA

ADMINISTRATIVE_UNIT
Primary Required: BUILT_UP_AREA
Optional: NET_AREA

Medical:

CLINIC
Primary Required: BUILT_UP_AREA
Optional: NET_AREA

MEDICAL_CENTER
Primary Required: BUILT_UP_AREA
Optional: PLOT_AREA, NET_AREA

Land:

RESIDENTIAL_LAND
COMMERCIAL_LAND
AGRICULTURAL_LAND
INDUSTRIAL_LAND

Primary Required:
PLOT_AREA

No other baseline Measurement rules.

OTHER:
No required baseline Measurement rules.

At most one Primary Measurement exists per Configuration Version.

Primary Measurement is configuration-derived, never manually selected
or duplicated on Organization Property.
```

## ATTRIBUTE DEFINITIONS

| Code | Type | Arabic | English |
| --- | --- | --- | --- |
| BEDROOMS | INTEGER | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚ÂºÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â±ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€šÃ‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¹Ã¢â‚¬Â ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ | Bedrooms |
| BATHROOMS | INTEGER | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â­ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Âª | Bathrooms |
| FLOOR | INTEGER | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â·ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¨ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ | Floor |
| FLOORS | INTEGER | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¹ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¯ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¯ ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â·ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¹Ã¢â‚¬Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¨ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ | Number of Floors |
| PARKING_SPACES | INTEGER | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â£ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â  ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚ÂªÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¸ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â± | Parking Spaces |
| FURNISHING | ENUM | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â­ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€šÃ‚ÂÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â±ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â´ | Furnishing |
| FINISHING | ENUM | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â­ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚ÂªÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â´ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â·ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¨ | Finishing |
| VIEW | TEXT | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¥ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â·ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© | View |
| BALCONY | BOOLEAN | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¨ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¹Ã¢â‚¬Â ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© / ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚ÂªÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â±ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â³ | Balcony / Terrace |
| GARDEN | BOOLEAN | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â­ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¯ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© | Garden |
| MAID_ROOM | BOOLEAN | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚ÂºÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â±ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€šÃ‚ÂÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â®ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¯ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© | Maid Room |
| STORAGE | BOOLEAN | ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â®ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â²ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â  | Storage |
| DELIVERY_STATUS | ENUM | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â­ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â³ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚ÂªÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ | Delivery Status |
| DELIVERY_DATE | DATE | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚ÂªÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â±ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â® ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â³ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚ÂªÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ | Delivery Date |
| YEAR_BUILT | INTEGER | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â³ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¨ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¡ | Year Built |

Exactly 15 baseline definitions. VIEW is TEXT in V1, not ENUM. The architecture also supports DECIMAL attributes; none of these baseline definitions uses that type.

## ATTRIBUTE OPTIONS

| Attribute | Option code | Arabic | English |
| --- | --- | --- | --- |
| FURNISHING | UNFURNISHED | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚ÂºÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â± ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€šÃ‚ÂÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â±ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¹Ã¢â‚¬Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â´ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© | Unfurnished |
| FURNISHING | SEMI_FURNISHED | ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚ÂµÃƒÆ’Ã¢â€žÂ¢Ãƒâ€šÃ‚Â ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€šÃ‚ÂÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â±ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¹Ã¢â‚¬Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â´ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© | Semi-Furnished |
| FURNISHING | FURNISHED | ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€šÃ‚ÂÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â±ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¹Ã¢â‚¬Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â´ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© | Furnished |
| FINISHING | UNFINISHED | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¨ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¯ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¹Ã¢â‚¬Â ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â  ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚ÂªÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â´ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â·ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¨ | Unfinished |
| FINISHING | SEMI_FINISHED | ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚ÂµÃƒÆ’Ã¢â€žÂ¢Ãƒâ€šÃ‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚ÂªÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â´ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â·ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¨ | Semi-Finished |
| FINISHING | FINISHED | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚ÂªÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â´ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â·ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¨ ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ | Finished |
| FINISHING | LUXURY_FINISHED | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚ÂªÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â´ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â·ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¨ ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€šÃ‚ÂÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â®ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â± | Luxury Finished |
| DELIVERY_STATUS | READY_TO_MOVE | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¡ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â²ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â³ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚ÂªÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ | Ready to Move |
| DELIVERY_STATUS | UNDER_CONSTRUCTION | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚ÂªÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â­ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Âª ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¥ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â´ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¡ | Under Construction |

Exactly nine baseline options. Attribute Options belong to exactly one Attribute Definition.

## ATTRIBUTE RULE MATRIX

The following matrix is normative:

```text
Required attributes:

APARTMENT:
BEDROOMS, BATHROOMS

DUPLEX:
BEDROOMS, BATHROOMS

PENTHOUSE:
BEDROOMS, BATHROOMS

STUDIO:
BATHROOMS

VILLA:
BEDROOMS, BATHROOMS

TOWNHOUSE:
BEDROOMS, BATHROOMS

TWIN_HOUSE:
BEDROOMS, BATHROOMS

CHALET:
BEDROOMS, BATHROOMS

All Commercial, Administrative, Medical, Land, and OTHER baseline
Unit Types have no required Attribute rules.

Optional rules:

APARTMENT:
FLOOR, PARKING_SPACES, FURNISHING, FINISHING, VIEW,
BALCONY, STORAGE, DELIVERY_STATUS, DELIVERY_DATE, YEAR_BUILT

DUPLEX:
FLOOR, FLOORS, PARKING_SPACES, FURNISHING, FINISHING,
VIEW, BALCONY, GARDEN, MAID_ROOM, STORAGE,
DELIVERY_STATUS, DELIVERY_DATE, YEAR_BUILT

PENTHOUSE:
FLOOR, PARKING_SPACES, FURNISHING, FINISHING, VIEW,
BALCONY, MAID_ROOM, STORAGE, DELIVERY_STATUS,
DELIVERY_DATE, YEAR_BUILT

STUDIO:
FLOOR, PARKING_SPACES, FURNISHING, FINISHING, VIEW,
BALCONY, STORAGE, DELIVERY_STATUS, DELIVERY_DATE, YEAR_BUILT

VILLA / TOWNHOUSE / TWIN_HOUSE:
FLOORS, PARKING_SPACES, FURNISHING, FINISHING, VIEW,
BALCONY, GARDEN, MAID_ROOM, STORAGE, DELIVERY_STATUS,
DELIVERY_DATE, YEAR_BUILT

CHALET:
FLOOR, PARKING_SPACES, FURNISHING, FINISHING, VIEW,
BALCONY, GARDEN, STORAGE, DELIVERY_STATUS,
DELIVERY_DATE, YEAR_BUILT

RETAIL_SHOP:
FLOOR, PARKING_SPACES, FINISHING, VIEW, STORAGE,
DELIVERY_STATUS, DELIVERY_DATE, YEAR_BUILT

COMMERCIAL_UNIT:
FLOOR, PARKING_SPACES, FINISHING, VIEW, STORAGE,
DELIVERY_STATUS, DELIVERY_DATE, YEAR_BUILT

WAREHOUSE:
PARKING_SPACES, FINISHING, STORAGE,
DELIVERY_STATUS, DELIVERY_DATE, YEAR_BUILT

OFFICE:
FLOOR, BATHROOMS, PARKING_SPACES, FURNISHING,
FINISHING, VIEW, BALCONY, DELIVERY_STATUS,
DELIVERY_DATE, YEAR_BUILT

ADMINISTRATIVE_UNIT:
FLOOR, BATHROOMS, PARKING_SPACES, FINISHING, VIEW,
DELIVERY_STATUS, DELIVERY_DATE, YEAR_BUILT

CLINIC:
FLOOR, BATHROOMS, PARKING_SPACES, FURNISHING,
FINISHING, DELIVERY_STATUS, DELIVERY_DATE, YEAR_BUILT

MEDICAL_CENTER:
FLOOR, FLOORS, BATHROOMS, PARKING_SPACES, FINISHING,
DELIVERY_STATUS, DELIVERY_DATE, YEAR_BUILT

LAND TYPES:
No baseline Attribute rules.

OTHER:
No baseline Attribute rules.

No rule means NOT APPLICABLE, not OPTIONAL.
```

## GEOGRAPHY BASELINE

Country: EG | ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚ÂµÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â± | Egypt | COUNTRY. Seed exactly these 27 children under EG, each with type GOVERNORATE:

| Code | Arabic | English |
| --- | --- | --- |
| CAIRO | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¡ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â±ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© | Cairo |
| GIZA | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â²ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© | Giza |
| ALEXANDRIA | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¥ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â³ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¯ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â±ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© | Alexandria |
| DAKAHLIA | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¯ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¡ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© | Dakahlia |
| RED_SEA | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¨ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â­ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â± ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â£ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â­ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â± | Red Sea |
| BEHEIRA | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¨ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â­ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â±ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© | Beheira |
| FAYOUM | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€šÃ‚ÂÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¹Ã¢â‚¬Â ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ | Fayoum |
| GHARBIA | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚ÂºÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â±ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¨ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© | Gharbia |
| ISMAILIA | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¥ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â³ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¹ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© | Ismailia |
| MENOFIA | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¹Ã¢â‚¬Â ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€šÃ‚ÂÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© | Menofia |
| MINYA | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ | Minya |
| QALYUBIA | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¹Ã¢â‚¬Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¨ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© | Qalyubia |
| NEW_VALLEY | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¹Ã¢â‚¬Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¯ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â  ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¯ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¯ | New Valley |
| SUEZ | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â³ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¹Ã¢â‚¬Â ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â³ | Suez |
| ASWAN | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â£ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â³ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¹Ã¢â‚¬Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â  | Aswan |
| ASSIUT | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â£ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â³ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¹Ã¢â‚¬Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â· | Assiut |
| BENI_SUEF | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¨ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â  ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â³ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¹Ã¢â‚¬Â ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€šÃ‚Â | Beni Suef |
| PORT_SAID | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¨ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¹Ã¢â‚¬Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â±ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â³ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¹ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¯ | Port Said |
| DAMIETTA | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¯ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â· | Damietta |
| SHARQIA | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â´ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â±ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â© | Sharqia |
| SOUTH_SINAI | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¹Ã¢â‚¬Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¨ ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â³ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¡ | South Sinai |
| KAFR_EL_SHEIKH | ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€šÃ‚ÂÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â± ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â´ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â® | Kafr El Sheikh |
| MATROUH | ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â·ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â±ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¹Ã¢â‚¬Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â­ | Matrouh |
| LUXOR | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â£ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚ÂµÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â± | Luxor |
| QENA | ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ | Qena |
| NORTH_SINAI | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â´ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â³ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¡ | North Sinai |
| SOHAG | ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â³ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¹Ã¢â‚¬Â ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¡ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¬ | Sohag |

Do not seed cities, areas, or districts in BF014. Geography hierarchy remains flexible for later expansion without fixed universal depth.

## DEVELOPMENT CATALOG

Developer -> Project -> Phase

All are canonical System-managed catalogs.

Project may have:

- optional Developer
- Geographic Location

Phase belongs to Project.

Do not seed any Developer, Project, or Phase records in BF014.

No invented business data.

## CONFIGURATION VERSIONING

Each baseline Unit Type receives Configuration Version 1.

Configuration structural rules are immutable after becoming historical.

At most one ACTIVE Configuration Version per Unit Type.

Structural changes create a new version.

Completed Organization Properties will later retain their accepted
configuration version.

Incomplete future Properties will use the current active version.

BF014 does not implement Property assignment or completeness.

## SEED PACKAGES

Lock this conceptual sequence:

- `001_core_property_categories`
- `002_core_unit_types`
- `003_egypt_geography`
- `004_core_measurement_definitions`
- `005_core_attribute_definitions`
- `006_core_attribute_options`
- `007_initial_unit_type_configurations`

Schema migrations and data seeds remain separate concerns.

The future seed runner must track applied seed versions.

## SEED SAFETY

Seeds must be:

- versioned
- idempotent
- non-destructive
- stable-code based

Rerunning seeds must not:

- create duplicates
- overwrite System Admin label/content edits
- reactivate records intentionally deactivated by System Admin
- delete canonical records
- overwrite newer configuration versions
- make V1 active again after Admin has activated a newer version

An already-applied structural configuration seed must not mutate its
historical rules.

Future structural baseline changes require a new explicit seed version.

## CATALOG PROVENANCE

Canonical records must distinguish provenance conceptually:

- `SYSTEM_SEED`
- `SYSTEM_ADMIN`

SYSTEM_SEED records:

- Edit allowed
- Deactivate allowed
- Reactivate allowed
- Hard Delete NOT allowed

SYSTEM_ADMIN records:

- Edit allowed
- Deactivate allowed
- Reactivate allowed
- Safe Delete allowed only when unreferenced and audit-safe

Provenance is not authorization.

## AUTHORIZATION

BF014 introduces the capability contract:

- `property_catalogs.view`
- `property_catalogs.manage`

property_catalogs.manage:
SYSTEM_ONLY

Franchise and Partner Agency administrators must not mutate
platform-wide canonical catalogs merely because they administer
their Organization.

property_catalogs.view supports authenticated consumers that require
catalog data according to the existing authorization architecture.

Do not introduce Proposal permissions in BF014.

## BACKEND API CONTRACT

Conceptual resources:

- `/property-categories`
- `/unit-types`
- `/measurement-definitions`
- `/attribute-definitions`
- `/geographic-locations`
- `/developers`
- `/projects`
- `/project-phases`

Configuration resources under Unit Type.

Dynamic projection:

GET /unit-types/{id}/property-form

Core fallback:

GET /property-profile/core-form

Dynamic form output conceptually includes:

- localized labels
- data type
- required/optional
- units where applicable
- enum options
- validation metadata
- ordering

Backend remains authoritative.

Do not include frontend layout/CSS/component metadata.

Exact HTTP route/mutation payload contracts may be finalized in
BF014 implementation planning, but the sprint document must preserve
these domain boundaries.

## ACCEPTANCE CRITERIA

The future BF014 implementation is done only when all criteria below are verified. None is claimed as passed by this documentation task:

1. Additive migrations after migration 017.
2. MariaDB compatibility.
3. Catalog integrity constraints verified.
4. Unit Type -> Category integrity verified.
5. Configuration versioning verified.
6. Maximum one active Configuration per Unit Type verified.
7. Maximum one Primary Measurement per Configuration verified.
8. Measurement/Attribute rule integrity verified.
9. ENUM option ownership verified.
10. Dynamic form projection verified.
11. System-only catalog mutation authorization verified.
12. Operational catalog read authorization verified.
13. Baseline seeds execute on clean database.
14. Seed rerun is idempotent.
15. Admin edits survive seed rerun.
16. Admin-deactivated seeded records remain inactive after rerun.
17. Newer active configuration is not overwritten/reactivated to V1.
18. Egypt + exactly 27 Governorates verified.
19. Exactly six baseline Categories verified.
20. Exactly 20 baseline Unit Types verified.
21. Exactly six Measurement Definitions verified.
22. Exactly 15 Attribute Definitions verified.
23. Exactly nine Attribute Options verified.
24. Each baseline Unit Type receives initial Configuration V1.
25. Zero seeded Developers.
26. Zero seeded Projects.
27. Zero seeded Phases.
28. BF001-BF013 regression coverage remains passing.
29. Real MariaDB acceptance coverage exists.
30. Documentation synchronized.
31. No BF014 non-goal implementation leaks into the sprint.

## IMPLEMENTATION UNIT PLAN

These are implementation units within BF014, not separate sprints. BF014.1 through BF014.5 are closed; BF014.6 is NEXT / NOT STARTED; BF014.7-BF014.8 remain NOT STARTED.

| Unit | Future deliverable |
| --- | --- |
| BF014.1 | Database schema + integrity constraints |
| BF014.2 | Repositories + domain read models |
| BF014.3 | Catalog/configuration services |
| BF014.4 | Authorization + HTTP/API integration |
| BF014.5 | Seed runner + baseline canonical dataset |
| BF014.6 | Dynamic property-form projection |
| BF014.7 | Real MariaDB acceptance + regressions |
| BF014.8 | Documentation closure |

## Implementation boundary details

- Catalog records and rules remain structured canonical data, not unrestricted JSON. Unit Type belongs to one Category; Phase belongs to its Project; Project Developer and Geographic Location references must resolve within their canonical catalogs. Geography must preserve a valid, cycle-free hierarchy.
- Rules belong to one Configuration Version and their corresponding Definition. Duplicate Definition rules within that version are rejected. Options belong to their ENUM Definition. No Property measurement or attribute value persistence is included.
- Structural edits create a new configuration version; historical rules are immutable. Concurrent changes must preserve at most one active version and one Primary Measurement. V1 receives exactly the baseline rules above. No rule means NOT APPLICABLE, not OPTIONAL. OTHER has no baseline Measurement or Attribute rules.
- System Admin backend management covers create, read, permitted edits, deactivate, reactivate, provenance-aware safe deletion, and configuration version management. Deactivation blocks new selection while preserving references. Catalog governance activity is append-only under the approved architecture; this is not Property Activity, Proposal resolution, or a general audit engine.
- property_catalogs.manage requires both effective capability and SYSTEM_ONLY scope. Position names and provenance never grant authority. property_catalogs.view requires authenticated, authorized operational access under the existing capability architecture. Authentication failure remains 401; capability/scope failure remains 403. No Proposal permissions are added.
- Configuration and Measurement/Attribute rule resources live under Unit Type; Attribute Options live under their Definition. Paths are conceptual, not registered routes. Exact HTTP mutations, identifiers, payloads, filtering, and errors may be finalized in BF014 implementation planning.
- GET /unit-types/{id}/property-form projects applicable configuration and its version, localized labels, types, required/optional rules, units, enum options, validation metadata, and ordering. Omitted rules are not exposed as optional fields. Primary Measurement is configuration-derived. Operational reads consume active selectable catalogs/configuration while historical references remain preserved.
- GET /property-profile/core-form provides core catalog-driven configuration before Unit Type selection. It must not fabricate Unit Type rules or store Property values. Neither projection calculates completeness, creates a Property/Listing, or includes frontend layout, CSS, or component metadata. Backend validation remains authoritative.
- Seed tracking records successfully applied package versions and must not mark failed execution as applied. Stable-code reruns preserve Admin content, deactivation, provenance, historical rules, and newer active versions. V1 never regains activation merely from a rerun. Structural baseline changes require explicit new seed versions.

## Documentation-task validation

Only this contract and necessary updates to AI_CONTEXT.md, PROJECT_STATUS.md, docs/MASTER_INDEX.md, docs/development/CURRENT_SYSTEM_STATE.md, docs/development/IMPLEMENTATION_PLAN.md, docs/development/SPRINT_LOG.md, and docs/database/SEED_DATA.md are authorized in this task.

Run git diff --check and git status --short; verify the documentation allowlist and review status/scope contradictions. BF013 remains CLOSED; BF014 remains IN PROGRESS; Rich Property Profile and Listing remain NOT IMPLEMENTED; no BF015 or later sprint is selected. No code, tests, migrations, seeds, frontend, dependency/runtime configuration, or database schema changes belong to this documentation task. Do not commit or push. The remaining acceptance criteria above belong to future BF014 implementation.
