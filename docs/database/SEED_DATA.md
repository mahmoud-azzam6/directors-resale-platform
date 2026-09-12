# Directors Resale Platform
# Seed Data Strategy

**Document Type:** Canonical Baseline Seed Strategy
**Status:** APPROVED / NOT IMPLEMENTED
**Selected Backend Sprint:** BF014 - SELECTED / DOCUMENTED / NOT IMPLEMENTED

## Authority and baseline

[BF014 - Canonical Property Catalog Foundation](../sprints/BF014-canonical-property-catalog-foundation.md) is the locked baseline dataset and future seed execution contract. Its baseline Category, Unit Type, Measurement, Attribute, option, configuration-rule, and geography sections are authoritative; no second editable dataset is maintained here.

[Property Catalog Architecture](../architecture/PROPERTY_CATALOG_ARCHITECTURE.md) governs catalogs, versioning, and data governance. [Rich Property Profile Architecture](../architecture/PROPERTY_PROFILE_ARCHITECTURE.md) governs future profile behavior. BF013 remains IMPLEMENTED AND VERIFIED / CLOSED. Rich Property Profile and Listing remain APPROVED / NOT IMPLEMENTED. No BF015 or subsequent sprint is selected.

## Locked BF014 baseline

| Baseline | Exact initial seed contract |
| --- | --- |
| Property Categories | 6: RESIDENTIAL, COMMERCIAL, ADMINISTRATIVE, MEDICAL, LAND, OTHER |
| Unit Types | 20, with Category assignments and Arabic/English labels in the BF014 contract |
| Measurement Definitions | 6; V1 unit SQM, extensible to additional units |
| Attribute Definitions | 15; VIEW is TEXT, not ENUM |
| Attribute Options | 9 across FURNISHING, FINISHING, DELIVERY_STATUS; each belongs to one Definition |
| Configuration Versions | V1 for each of the 20 Unit Types, using exactly the locked Measurement/Attribute matrices |
| Geographic Locations | EG (Egypt), COUNTRY, plus exactly 27 GOVERNORATE children |
| Cities / areas / districts | Zero seeded |
| Developers / Projects / Phases | Zero seeded in each catalog; no invented business data |

Counts describe the initial baseline dataset, not limits on later authorized Admin-created records. Stable machine codes do not change when localized labels change. Geography remains a flexible hierarchy. Development remains Developer -> Project -> Phase, separate from geography; Project may have an optional Developer and Geographic Location, and Phase belongs to Project.

## Versioned package sequence

1. 001_core_property_categories
2. 002_core_unit_types
3. 003_egypt_geography
4. 004_core_measurement_definitions
5. 005_core_attribute_definitions
6. 006_core_attribute_options
7. 007_initial_unit_type_configurations

Schema migrations and data seeds are separate concerns. The future runner must track successfully applied seed versions; failed execution must not be recorded as applied. These are conceptual packages, not existing seed scripts.

## Execution safety

Seeds must be versioned, idempotent, non-destructive, and stable-code based. Reruns must not create duplicates, overwrite System Admin label/content edits, reactivate intentionally deactivated records, delete canonical records, overwrite newer configurations, or make V1 active again after Admin activates a newer version.

Already-applied structural configuration seeds must not mutate historical rules. Structural baseline changes require a new explicit seed version. At most one Configuration Version is ACTIVE per Unit Type and at most one Primary Measurement exists per version. Structural changes create new versions. Future completed Properties retain accepted versions; incomplete Properties use the current active version. BF014 does not implement Property assignment or completeness.

## Provenance and governance

| Provenance | Edit | Deactivate | Reactivate | Hard Delete |
| --- | --- | --- | --- | --- |
| SYSTEM_SEED | Allowed | Allowed | Allowed | Never allowed |
| SYSTEM_ADMIN | Allowed | Allowed | Allowed | Only when unreferenced and audit-safe |

BF014 explicitly narrows the architecture's general safe-delete allowance: seeded canonical records cannot be hard-deleted even when unreferenced. Deactivation prevents new selection and preserves current and historical references. Provenance is not authorization; mutation requires property_catalogs.manage with SYSTEM_ONLY scope, and operational reads require property_catalogs.view under existing authorization. Organization administration alone never grants canonical mutation authority.

## Not implemented

Seed scripts, seed runner/tracking storage, catalog schema, backend management APIs, and dynamic projections are future BF014 work and remain NOT IMPLEMENTED. Catalog Proposals/resolution, OrganizationProperty classification and rich values, Additional Information, completeness, Property Activity, Property Media, frontend workflows, and Listing remain outside BF014. This documentation task creates no migrations or seeds and executes no data changes.
