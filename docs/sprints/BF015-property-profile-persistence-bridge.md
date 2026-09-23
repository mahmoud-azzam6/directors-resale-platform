# BF015 Ã¢â‚¬â€ Property Profile Persistence Bridge

**Status:** IMPLEMENTED AND VERIFIED / CLOSED — BF015.1 through BF015.6 IMPLEMENTED AND VERIFIED / CLOSED

**Foundation:** BF013 Ã¢â‚¬â€ Property & Ownership Foundation Ã¢â‚¬â€ CLOSED; BF014 Ã¢â‚¬â€ Canonical Property Catalog Foundation Ã¢â‚¬â€ CLOSED

## Purpose and boundary

BF015 makes the BF013 Organization Property shell persistable as a Property Profile by attaching canonical BF014 selections and configuration-bound typed values. It unlocks genuine Property Admin Add Unit Steps 1 and 2 without fake persistence.

BF015 is not a general Property-domain or Listing-domain sprint. It reuses BF013 Owner, Ownership, Ownership Parties, Authorized Acting Owner, and Global Physical Property Identity unchanged. It reuses BF014 catalogs, configuration rules, Geography, Development, and dynamic form projections unchanged.

## Physical model

BF015 adds exactly three normalized tables: organization_property_profiles, property_measurements, and property_attribute_values.

organization_property_profiles is a one-to-one extension of organization_properties. Its row may be absent while a BF013 Property shell exists; BF015 does not widen organization_properties with rich-profile columns.

The profile row contains organization_property_id, nullable property_category_id, unit_type_id, accepted_configuration_version_id, and geographic_location_id; a nullable development_reference_type plus nullable developer_id, project_id, and project_phase_id; a monotonic revision; and creator/updater/timestamps consistent with repository conventions. When development_reference_type is present, exactly its matching canonical FK is present; the other two are null.

### Category, Unit Type, and configuration

Category is persisted directly because progressive setup may select a Category before a Unit Type. Unit Type is nullable. A newly assigned Unit Type must be ACTIVE and belong to the persisted Category.

accepted_configuration_version_id is the configuration against which typed values are interpreted. When a Property first becomes configuration-bound, the server accepts only the selected Unit TypeÃ¢â‚¬â„¢s current ACTIVE configuration. Once values are saved against that version, later activation of a newer version does not rebind the Property or change value semantics.

For example, a Property bound to APARTMENT V1 remains bound to V1 after APARTMENT V2 becomes ACTIVE. An unbound partial profile may use the current ACTIVE projection for entry guidance. Rebinding requires an explicit authorized replacement or migration operation; BF015 does not implement a general profile-version-history system.

When a Unit Type changes and configuration-bound values exist, the API uses explicit transactional replacement: the request supplies the new Unit Type, its current ACTIVE configuration, and replacement values. The service rejects a change that would silently retain stale values.

### Geography and Development

The profile stores one nullable geographic_location_id, representing the deepest selected canonical BF014 location. Its Country/Governorate/City/Area/District ancestry is resolved from the canonical hierarchy and is never redundantly stored.

Development stores only the most-specific selected canonical reference: no reference, Developer, Project, or Phase. The reference type identifies which of developer_id, project_id, or project_phase_id is populated. A Project derives its Developer where present; a Phase derives its Project and Developer. Project and Phase validation uses their canonical parent relationships. BF015 creates no fake hierarchy records and introduces no new Geography/Development coupling.

### Measurements

property_measurements stores organization_property_id, measurement_definition_id, value_decimal DECIMAL(18,4), canonical unit code snapshot, and actor/timestamp metadata. The pair of organization_property_id and measurement_definition_id is unique.

Each submitted Definition must belong to the accepted configuration. Values are positive decimals in the DefinitionÃ¢â‚¬â„¢s canonical unit; the bridge accepts neither frontend-defined measurement codes nor unit conversion.

### Attributes

property_attribute_values stores organization_property_id, attribute_definition_id, a data-type snapshot, typed nullable value columns, an Attribute Option FK where applicable, and actor/timestamp metadata. The pair of organization_property_id and attribute_definition_id is unique.

The bridge supports every BF014 Attribute Definition type: INTEGER, DECIMAL, BOOLEAN, TEXT, ENUM, and DATE. A row has exactly one compatible representation: signed integer, DECIMAL(18,4), text, boolean, date, or ENUM Option. Submitted Definitions must belong to the accepted configuration. ENUM Option ownership must match the Attribute Definition, and a new ENUM assignment requires an active valid Option.

## Progressive persistence and validation

BF015 supports a shell without a profile and a profile with Category only, Category plus Unit Type, partial Geography/Development, and partial Measurements or Attributes. Missing configured required values do not reject ordinary profile saves. Requiredness belongs to a future completeness/validation boundary.

BF015 adds no DRAFT Property lifecycle. BF013 ACTIVE and ARCHIVED remain authoritative.

Every profile save is one aggregate transaction. It locks the Property/profile, validates authorization and expected revision, revalidates canonical references and hierarchy, validates configuration membership and typed values, and persists supplied core/value replacements atomically. Failure rolls back the complete aggregate.

Concurrent updates use row locking and a monotonic profile revision or equivalent expected-update token. Stale updates fail rather than silently overwriting newer state.

The bridge records current-state creator/updater attribution, timestamps, configuration binding, and revision. It does not add a full Property Activity/event-history system or full Property Profile version history.

## HTTP, authorization, privacy, and lifecycle

BF013 shell creation remains separate at POST /organization-properties. BF015 adds GET /organization-properties/{id}/profile and PUT /organization-properties/{id}/profile.

The profile API is a bounded domain API, not a React-wizard endpoint. The frontend may orchestrate shell creation, profile save, and existing BF013 Owner/Ownership calls without a mandatory giant orchestration endpoint.

Profile reads require properties.view; profile saves require properties.manage. property_catalogs.manage never authorizes operational Property mutation, and catalog visibility never grants Property access. Profile data follows explicit private-Organization rules: parent Franchise supervision does not automatically expose child Organization Property Profile data; System-wide access requires explicit authorized System capability.

Archived Properties retain Profile and values, remain readable where authorized, and reject normal profile mutation. Reactivation resumes profile mutation. BF015 adds no hard delete; clearing or replacing values is an explicit authorized profile update.

BF015 does not create or mutate Owner, Ownership, Ownership Parties, shares, or Authorized Acting Owner data. Those remain BF013 operations and APIs.

## Internal implementation units

| Unit | Deliverable | Status |
| --- | --- | --- |
| BF015.1 | Schema & Integrity Foundation | IMPLEMENTED AND VERIFIED / CLOSED |
| BF015.2 | Repository Foundation | IMPLEMENTED AND VERIFIED / CLOSED |
| BF015.3 | Property Profile Domain Service | IMPLEMENTED AND VERIFIED / CLOSED |
| BF015.4 | HTTP & Authorization | IMPLEMENTED AND VERIFIED / CLOSED |
| BF015.5 | Integrated Acceptance | IMPLEMENTED AND VERIFIED / CLOSED |
| BF015.6 | Documentation Closure | IMPLEMENTED AND VERIFIED / CLOSED |

## Acceptance target

BF015 acceptance must prove shell-without-profile, partial and Category-only saves, Category/Unit Type pairing, ACTIVE Unit Type assignment, V1-to-V2 configuration safety, explicit atomic Unit Type replacement, canonical Geography and normalized Development validation, measurement membership/uniqueness/unit/numeric validation, every Attribute type and ENUM ownership, allowed missing required values during progressive save, rollback, optimistic-concurrency conflict, archived-Property mutation rejection, Property authorization/private-profile scope, BF013 Ownership compatibility, BF014 catalog compatibility, and no Listing side effects.

## Explicit non-goals

BF015 does not implement completeness, media, private documents, Catalog Proposals, Listing or Listing Versions, Listing approvals, Owner Listing Approval, Marketplace, Requests, HOLD, SOLD workflow, Sale Closing, Ownership Transfer Confirmation, Commission Engine, Transfer Engine, or frontend Property UI.

The next frontend workstream is intentionally not defined by this contract.

## BF015.1 implementation result

Migration 033 creates organization_property_profiles, property_measurements, and property_attribute_values without altering BF013 or BF014 tables. Development uses development_reference_type with exactly one matching nullable canonical FK: developer_id, project_id, or project_phase_id. The database CHECK prevents contradictory combinations.

Profile revision is INT UNSIGNED, defaults to 1, and is constrained positive. Profile and value rows use the existing operational creator/updater user references and timestamps. Measurements enforce positive DECIMAL(18,4) values and nonblank unit snapshots. Attribute rows enforce the six canonical data types, boolean shape, and exactly one compatible typed representation.

All operational-to-canonical and operational-to-Property FKs use RESTRICT. Value rows also reference the one-to-one profile key, so a profile with values cannot be deleted independently. This preserves Property data and prevents catalog deletes from cascading into operational values.

Existing BF014 keys do not provide clean composite foreign keys for Category-to-Unit Type membership, Configuration-to-Unit Type membership, or Attribute Option-to-Definition ownership. BF015.1 intentionally uses simple existence FKs; BF015.3 Service validation will enforce those cross-table semantics, active-state selection, configuration-rule membership, hierarchy relationships, and canonical measurement-unit equality.

## BF015.2 implementation result

OrganizationPropertyProfileRepository, PropertyMeasurementRepository, and PropertyAttributeValueRepository provide persistence-only reads and writes for the three BF015 tables. The profile repository provides an optimistic expected-revision update primitive that increments the revision exactly once and returns no row for a stale expected revision.

Measurement and Attribute reads use deterministic Definition-ID ordering. Attribute upsert persists the complete typed shape on replacement, clearing incompatible stored columns while preserving creation attribution. Repositories do not begin, commit, or roll back transactions; BF015.3 owns aggregate transactions and all semantic validation, including canonical lifecycle, membership, hierarchy, and typed-value rules.

Focused real-MariaDB acceptance verified profile locking and revision behavior, scoped value persistence, all six Attribute representations, constraint-failure recovery, and caller-owned rollback/commit behavior. BF015 schema, BF014 repository, BF013 database, QueryBuilder FOR UPDATE, and DatabaseManager transaction regressions passed.

## BF015.3 implementation result

OrganizationPropertyProfileService provides `getProfile(propertyId, organizationId)` and `saveProfile(propertyId, organizationId, payload, actorId)`. Reads return the BF013 shell with the nullable profile, pinned configuration and canonical context, Geography ancestry, Development context, and persisted values. A missing profile remains a stable empty profile aggregate for an existing shell.

Save payloads are patches: omitted core fields and value collections remain unchanged; nullable core fields use explicit null to clear; `clear_measurement_definition_ids` and `clear_attribute_definition_ids` explicitly remove individual values. New Unit Type binding selects the current ACTIVE configuration once, while later unrelated saves retain the existing pinned version. Changing Unit Type with values requires `replace_values: true` and complete replacement value collections; stale values are removed inside the same transaction.

The Service owns the aggregate transaction, locks the Property then Profile, validates canonical active state and relationships, normalizes all six Attribute shapes and measurements, and uses the expected profile revision for existing updates. A stale expected revision returns `PROFILE_REVISION_CONFLICT`. Canonical lifecycle, configuration membership, units, option ownership, Geography, and Development are Service validation concerns; repositories remain persistence-only.

Focused real-MariaDB service acceptance and BF015 repository/schema, BF014 projection/configuration/catalog/geography/development, BF013 database, and DatabaseManager transaction regressions passed.

The Service normalizes DECIMAL(18,4) values as validated strings without PHP float conversion: measurements are positive and Attribute DECIMAL values may be negative. Clear lists are validated against the pinned configuration before deletion, while not requiring historical definitions to remain ACTIVE merely to clear their persisted values. Focused acceptance also verifies replacement rollback, omitted-value preservation, explicit clear behavior, and historical V1 validation after V2 activation.
