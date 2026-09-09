# Directors Resale Platform
# Seed Data Strategy

**Document Type:** Canonical Baseline Seed Strategy
**Status:** APPROVED / NOT IMPLEMENTED

---

# 1. Purpose

This document defines the canonical baseline seed strategy for System-managed Property catalogs. It does not approve a seed implementation sprint and does not claim that seed scripts currently exist.

Property Catalog ownership, configuration, proposals, and governance are defined by `docs/architecture/PROPERTY_CATALOG_ARCHITECTURE.md`. Rich Property Profile behavior is defined by `docs/architecture/PROPERTY_PROFILE_ARCHITECTURE.md`.

# 2. Seed Execution

Baseline seeds use versioned, idempotent seed scripts that are separate from schema migrations. Seed application and version history must be trackable.

Stable machine codes identify seeded canonical records. Reapplying a seed version must not create duplicates or destructively overwrite changes made by System Admin.

Seed execution must not:

- overwrite System Admin edits without an explicitly approved migration strategy
- reactivate a record intentionally deactivated by System Admin
- delete referenced or audited canonical data
- silently replace a newer seed/application version

# 3. Baseline Scope

The proposed baseline seed scope includes:

- core Property Categories
- common Unit Types
- core Measurement Definitions
- core Attribute Definitions and common Attribute Options
- initial Unit Type configuration versions and rules
- Egypt as a country and its 27 governorates

Illustrative Unit Types may include Apartment, Villa, Townhouse, Twin House, Duplex, Penthouse, Chalet, Studio, Office, Clinic, Retail/Shop, Warehouse, and Land. The final taxonomy, codes, category assignments, configuration rules, labels, and translations remain subject to a separately approved implementation sprint.

The baseline must not invent or present an authoritative nationwide Developer or Project catalog without approved business data. Developers and Projects may be introduced later through a separately approved business dataset.

# 4. Governance After Seeding

Seeded entries become normal canonical records governed by System Admin. Subject to referential integrity and audit rules, supported actions are:

- Edit
- Deactivate
- Reactivate
- safe Delete

Deactivation prevents new selection but preserves existing references. Hard delete is permitted only when existing references, historical use, and audit requirements make it safe.

# 5. Deferred Work

Seed scripts, seed runners, version-history storage, authoritative taxonomy contents, Developer/Project datasets, migrations, APIs, and System Admin catalog screens are not implemented. No subsequent sprint is selected.
