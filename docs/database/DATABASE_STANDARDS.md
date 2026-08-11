# Database Standards

Version: 1.0

Status: Documentation Freeze v1.0

Last Updated: 2026-08-12

---

# Purpose

This document defines the official database standards for the Directors Resale Platform.

Every database table, migration, relationship, index and constraint must follow these standards.

These rules are mandatory.

No exceptions are allowed unless approved through an Architecture Decision Record (ADR).

---

# Database Philosophy

The database is designed using the following principles:

- Architecture First
- Business First
- Normalization First
- History First
- Performance First
- Security First
- Scalability First
- API First
- AI Ready
- Event Driven
- Human Approval
- No Hard Delete
- Single Source of Truth

---

# General Design Principles

Every table should represent one business concept.

Never design tables around screens.

Never duplicate business data.

Never store calculated values unless they represent historical snapshots.

Always prefer normalization over duplication.

Business rules belong in the backend, not inside the database.

---

# Naming Conventions

## Tables

Plural

Examples

users

organizations

properties

deals

---

## Columns

snake_case

Examples

created_at

organization_id

currency_id

listing_price

---

## Foreign Keys

Always end with:

_id

Examples

organization_id

country_id

currency_id

created_by_user_id

---

## Primary Key

Always

id

---

## Public Identifier

Always

ulid

---

# Primary Key Standards

Every business table must contain:

```sql
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
```

The internal ID is used for:

- Foreign Keys
- Joins
- Performance

It should never be exposed publicly.

---

# ULID Standards

Every business table must contain:

```sql
ulid CHAR(26)
```

ULID is used by:

- APIs
- Frontend
- External integrations
- Public URLs

ULID must be unique.

ULID must never change.

---

# Foreign Key Standards

Every relationship must use Foreign Keys.

Example

organization_id

↓

organizations.id

Never store names instead of IDs.

---

# Audit Standards

Every persistent business table must include:

| Column | Required |
|---------|----------|
| created_at | Yes |
| updated_at | Yes |
| created_by | Yes |
| updated_by | Yes |

When applicable, tables should also include:

| Column | Required |
|---------|----------|
| approved_at | Conditional |
| approved_by | Conditional |
| archived_at | Conditional |
| archived_by | Conditional |

All *_by columns reference the internal users.id primary key.

Public ULIDs must never be stored in audit relationships.

Reference Data tables also include audit columns.

System seeded records should reference the dedicated System User account as created_by.

These columns are mandatory unless an ADR explicitly states otherwise.

---

# Timestamp Standards

Every business table must contain:

created_at

updated_at

Use UTC.

Application converts to local timezone.

---

# Soft Delete Policy

Business tables must never be hard deleted.

Preferred approach:

deleted_at

or

status = archived

Historical information must always remain available.

---

# Status Standards

Avoid MySQL ENUM.

Preferred:

status VARCHAR(50)

Validated by backend.

Examples

active

inactive

draft

pending

approved

rejected

suspended

sold

archived

hold

Each module defines its own allowed statuses.

---

# Boolean Standards

Use BOOLEAN only for true/false values.

Examples

is_active

is_verified

is_default

Do not use BOOLEAN where multiple states may exist.

---

# Money Standards

Never store money without currency.

Always use:

```sql
amount DECIMAL(18,2)

currency_id BIGINT UNSIGNED
```

Never use FLOAT or DOUBLE for financial values.

---

# Multi Currency Standards

Each transaction keeps its original currency.

Exchange rates are stored separately.

Historical exchange rates never change.

Reports may display values in any supported currency.

Conversions always use historical exchange rates.

---

# Lookup Table Standards

Reference data belongs inside dedicated lookup tables.

Examples

countries

currencies

languages

property_types

Never duplicate lookup values.

---

# History Table Standards

Every important business entity should have history.

Examples

deal_history

listing_history

approval_history

History tables are append-only.

Records must never be edited.

---

# Approval Standards

Approvals should use the Approval Engine.

Business tables should not implement custom approval logic.

Approval requests remain immutable after completion.

---

# Attachment Standards

Files should never be stored inside business tables.

Business tables reference attachments.

Attachments are reusable.

Versioning is supported.

---

# Index Standards

Index:

- Foreign Keys
- ULID
- Status
- Frequently searched columns

Avoid unnecessary indexes.

Indexes should improve read performance.

---

# Constraint Standards

Use:

- Primary Keys
- Foreign Keys
- Unique Constraints
- Check Constraints (where supported)

Business validation belongs in backend services.

---

# Migration Standards

Migration file names should follow the convention:

NNN_create_<table_name>_table

Examples:

001_create_countries_table

002_create_languages_table

101_create_organizations_table

Migration execution order may differ from documentation order based on foreign key dependencies.

Migrations must be:

- Small
- Atomic
- Reversible

Never mix unrelated tables in a single migration.

---

# Seed Standards

System lookup data must always be seeded.

Business transactional data must never be seeded.

Recommended Seed Order:

| Order | Seed |
|--------|------|
| 1 | Countries |
| 2 | Languages |
| 3 | Currencies |
| 4 | Currency Rates |
| 5 | States |
| 6 | Cities |
| 7 | Districts |
| 8 | Postal Codes |
| 9 | Business Lookup Tables |

Seed execution may evolve as the database grows.

---

# Performance Standards

Use BIGINT for primary keys.

Avoid SELECT *.

Always paginate.

Index frequently queried columns.

Avoid unnecessary joins.

Optimize before scaling.

---

# Security Standards

Internal IDs are never exposed.

Public APIs use ULID.

Validate every foreign key.

Never trust client-side IDs.

Audit sensitive actions.

---

# AI Ready Standards

Tables should support:

- Analytics
- Recommendations
- Matching
- Predictions
- Classification

Avoid database structures that prevent future AI analysis.

---

# Database Checklist

## BF006-BF007 Staged Implementation Record

The BF006-BF007 `organizations` table is an intentional staged implementation with the BF006
fields plus `parent_organization_id` for the Franchise hierarchy relationship:
`id`, `parent_organization_id`, `name`, `code`, `organization_type`, `status`, `created_at`, and `updated_at`.

It does not currently contain the ULID, extended audit, or broader DB101 fields required by
these global standards. This is a recorded discrepancy, not a change to the standards and not
authorization to expand the table outside an approved milestone. See
`docs/database/DATABASE_CHANGELOG.md` and `docs/database/schema/core.md`.

Before approving any database table verify:

- [ ] Business purpose defined
- [ ] Primary Key added
- [ ] ULID added
- [ ] Foreign Keys defined
- [ ] created_at added
- [ ] updated_at added
- [ ] created_by added
- [ ] updated_by added
- [ ] Approval columns reviewed
- [ ] Archive columns reviewed
- [ ] Status strategy defined
- [ ] Money fields include Currency
- [ ] Indexes added
- [ ] Constraints defined
- [ ] Related ADR documented
- [ ] MASTER_INDEX updated
- [ ] Migration documented
- [ ] Seeder planned
- [ ] API impact reviewed
- [ ] AI impact reviewed

---

# Related Documents

| Document | Purpose |
|----------|---------|
| DATABASE_ARCHITECTURE.md | Overall database architecture |
| DATABASE_OVERVIEW.md | Database overview |
| REFERENCE_DATA.md | Reference Data module |
| MASTER_INDEX.md | Project registry |
| AI_CONTEXT.md | AI architecture context |

---

# Final Rule

If a new requirement conflicts with this document, do not modify the database directly.

Create an ADR first.

After approval, update this document if necessary.

Database consistency is more important than development speed.
