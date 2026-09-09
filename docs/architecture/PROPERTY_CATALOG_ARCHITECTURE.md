# Directors Resale Platform
# Property Catalog and Data Governance Architecture

**Document Type:** Domain and Data Governance Architecture
**Status:** APPROVED / NOT IMPLEMENTED
**Foundation:** BF013 - IMPLEMENTED AND VERIFIED / CLOSED

---

# 1. Purpose and Authority

This document is the canonical source for Property Catalog and Data Governance behavior. It defines approved catalog ownership, versioning, proposal, privacy, and integrity boundaries without approving migrations, seeds, APIs, frontend implementation, or a subsequent sprint.

System Admin owns canonical data governance. Operational users consume active canonical values and may submit private proposals, but cannot mutate platform-wide catalogs.

System-managed canonical catalogs comprise:

- Property Categories
- Unit Types
- Geographic Locations
- Developers
- Projects/Compounds
- Project Phases
- Measurement Definitions
- Attribute Definitions
- Attribute Options

# 2. Geographic and Development Domains

Geographic Location and Development Structure are separate domains.

Geographic Locations use one hierarchical canonical catalog with variable levels such as Country, Governorate/Region, City, and Area/District. A fixed universal depth is not required.

Development Structure is:

```text
Developer -> Project -> Phase
```

Developer, Project, and Phase are all optional for an Organization Property. Selecting a canonical Project may derive its Developer and geographic context. Organization Property must avoid duplicating truth that can reliably be derived from canonical context.

A Phase belongs to its selected Project. Sufficient geographic context may support a complete standalone Organization Property without any Development Structure selection.

# 3. Unit Type Configuration

Unit Type belongs to Property Category. Unit Type configuration is versioned, and structural rule changes create a new configuration version.

Existing completed Organization Properties retain their accepted configuration version and are not retroactively demoted when System Admin adds requirements. Incomplete Organization Properties use the current active configuration.

At most one configuration version is active for a Unit Type at a time.

Unit Type Measurement Rules specify `REQUIRED` or `OPTIONAL` and allow at most one Primary Measurement per configuration version. An Organization Property has at most one Measurement value for each Measurement Definition.

Unit Type Attribute Rules specify `REQUIRED` or `OPTIONAL`; absence means the Attribute is not applicable. An Organization Property has at most one value for each Attribute Definition.

Attribute Definitions use these types:

- `INTEGER`
- `DECIMAL`
- `BOOLEAN`
- `TEXT`
- `ENUM`
- `DATE`

Attribute values must match their definition type. An `ENUM` value references an Attribute Option belonging to the same Attribute Definition. Canonical searchable data must not be hidden in unrestricted JSON.

# 4. Private Catalog Proposals

Missing canonical values use Organization-private proposals. Proposal types include at least:

- `UNIT_TYPE`
- `GEOGRAPHIC_LOCATION`
- `DEVELOPER`
- `PROJECT`
- `PHASE`

A proposal belongs to exactly one Organization. It is visible only to authorized same-Organization users and explicitly authorized System Admin. Parent Franchise scope does not automatically expose Partner Agency proposals.

Resolution actions are:

- `APPROVED_NEW`
- `MERGED_EXISTING`
- `REJECTED`

A proposal resolves exactly once. Resolution is audited, immutable, and transactionally guarded, and its target must match the proposal type.

`APPROVED_NEW` resolution:

1. creates the approved canonical record
2. records the immutable proposal resolution
3. transactionally rebinds affected Organization Properties from the pending proposal role to the new canonical record
4. preserves proposal and history context
5. re-evaluates Property completeness
6. commits the complete resolution atomically

`MERGED_EXISTING` resolution:

1. records the immutable proposal resolution to the selected compatible canonical record
2. transactionally rebinds affected Organization Properties
3. preserves proposal and history context
4. re-evaluates Property completeness
5. commits the complete resolution atomically

`REJECTED` resolution:

1. preserves the proposal and its history
2. does not delete Property data
3. removes or resolves only the pending semantic role as appropriate
4. re-evaluates completeness against the actual mandatory requirements

Rejecting optional Developer, Project, or Phase data must not make an Organization Property incomplete when sufficient mandatory geographic and Unit Type data remains.

Chained Developer, Project, and Phase proposals are allowed, but approval does not cascade. Each proposal is reviewed independently.

Proposal rejection never invalidates an Organization Property by itself. Rejection of optional Project, Developer, or Phase data does not block completeness when sufficient mandatory geographic and Unit Type data exists.

For a semantic role, an Organization Property must not simultaneously reference both a canonical value and a pending proposal. Historical Property-to-Proposal links preserve prior proposal context and resolution without turning rejected proposals into current canonical facts.

# 5. Catalog Lifecycle and Governance

Catalog deactivation prevents new selection while preserving existing references. Seeded and subsequently created canonical records use the same normal System Admin management behavior: Edit, Deactivate, Reactivate, and safe Delete.

Hard delete is allowed only when referential integrity and audit rules prove it safe. Historical and current references must never be silently broken.

The future System Admin UI must support management of predefined canonical data and private proposal review. That UI is not implemented.

# 6. Conceptual Physical Components

The approved, not-implemented conceptual model includes:

- `property_categories`
- `unit_types`
- `unit_type_configuration_versions`
- `measurement_definitions`
- `unit_type_measurement_rules`
- `property_measurements`
- `attribute_definitions`
- `attribute_options`
- `unit_type_attribute_rules`
- `property_attribute_values`
- `geographic_locations`
- `developers`
- `projects`
- `project_phases`
- `property_catalog_proposals` with typed context where required
- historical Property-to-Proposal links
- `property_catalog_activities`

These names define conceptual data boundaries only. They do not claim that tables, migrations, seed scripts, APIs, or frontend screens exist.

# 7. Activity and Integrity

Property Catalog Activity is append-only. It records governance changes and proposal resolution while current catalog records remain efficient current-state projections. This is not full event sourcing.

Approved logical invariants include:

1. Unit Type belongs to its selected Property Category.
2. Phase belongs to its selected Project.
3. At most one Unit Type configuration version is active.
4. At most one Primary Measurement rule exists per configuration version.
5. One Measurement value exists per definition per Organization Property.
6. One Attribute value exists per definition per Organization Property.
7. An ENUM option belongs to its Attribute Definition.
8. Typed Attribute values match their definition data type.
9. A proposal belongs to exactly one Organization and resolves exactly once.
10. A resolution target matches its proposal type.
11. Canonical and pending-proposal values cannot both represent the same semantic role.
12. Catalog deactivation preserves historical and current references.
13. Catalog activity and resolution history are append-only.

# 8. Seed Strategy and Deferred Work

`docs/database/SEED_DATA.md` is the canonical baseline seed strategy. It does not claim that seed scripts currently exist.

Catalog implementation, seed scripts, System Admin catalog UI, proposal review UI, Add Unit UI, rich Property persistence, automatic Global Property matching, cross-Organization Owner reconciliation, Listings, transfers, commissions, and broader audit/event architecture remain deferred. No subsequent sprint is selected.
