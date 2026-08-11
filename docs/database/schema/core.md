# Core Database Schema

**Module:** Core

**Version:** 1.0

**Status:** Approved

---

# Overview

The Core module is the foundation of Directors Resale Platform.

Every other module depends on this module.

The Core module is responsible for:

- Organizations
- Users
- Positions
- Permissions
- Global Settings
- Events
- Activity Logs

No business module should duplicate identity or organization information.

---

## DB101 - organizations

## BF006 Implementation Status

BF006 intentionally implements a staged subset of this broader architecture. The live
`organizations` table currently contains only:

- id
- name
- code
- organization_type
- status
- created_at
- updated_at

This staged implementation is defined by `docs/sprints/BF006-Organization-Module.md` and
`database/migrations/001_create_organizations_table.sql`. It must not be silently expanded
from this broader DB101 design. The remaining fields, relationships, ULID, and audit columns
below remain architectural/planned requirements until an approved future milestone addresses them.

## Purpose

Represents every organization inside the platform.

An organization can be:

- Directors Resale Hub (System)
- Franchise Company
- Partner Agency

Everything inside the platform belongs to an organization.

---

## Business Rules

• There is only one System organization.

• A Franchise can have multiple Partner Agencies.

• A Partner Agency belongs to one Franchise.

• Organizations own:

- Users
- Listings
- Leads
- Deals
- Commission Plans
- Settings

Organizations never own People.

People belong to the CRM.

---

## Columns

| Column | Type | Nullable | Notes |
|----------|----------|----------|----------|
| id | BIGINT UNSIGNED | No | Primary Key |
| ulid | CHAR(26) | No | Public Identifier |
| parent_organization_id | BIGINT UNSIGNED | Yes | Parent Franchise |
| code | VARCHAR(100) | No | Unique Business Code |
| legal_name | VARCHAR(255) | No | Official Company Name |
| display_name | VARCHAR(255) | No | Display Name |
| type |  VARCHAR(50) | No | Organization Type |
| email | VARCHAR(255) | Yes | Main Email |
| phone | VARCHAR(50) | Yes | Main Phone |
| website | VARCHAR(255) | Yes | Company Website |
| tax_number | VARCHAR(100) | Yes | Future Finance |
| commercial_register | VARCHAR(100) | Yes | Future Finance |
| country_id | BIGINT UNSIGNED | Yes | Reference Data |
| city_id | BIGINT UNSIGNED | Yes | Reference Data |
| address | TEXT | Yes | Company Address |
| logo_url | VARCHAR(500) | Yes | Logo |
| timezone | VARCHAR(100) | No | IANA Time Zone (e.g. Africa/Cairo) |
| default_language_id | BIGINT UNSIGNED | No | Default Language |
| status | VARCHAR(50) | No | Business Status |
| created_by | BIGINT UNSIGNED | Yes | Audit |
| updated_by | BIGINT UNSIGNED | Yes | Audit |
| created_at | TIMESTAMP | No | |
| updated_at | TIMESTAMP | No | |
| archived_at | TIMESTAMP | Yes | Archive Timestamp |
| archived_by | BIGINT UNSIGNED | Yes | Archived By |

---

## Allowed Values

### type

```text
system

franchise

partner_agency
```

### status

```text
active

inactive

suspended
```

---

## Foreign Keys

parent_organization_id

→ organizations.id

country_id

→ countries.id

city_id

→ cities.id

default_language_id

→ languages.id

created_by

→ users.id

updated_by

→ users.id

archived_by

→ users.id

---

## Indexes

INDEX(ulid)

INDEX(code)

INDEX(type)

INDEX(status)

INDEX(parent_organization_id)

INDEX(country_id)

INDEX(city_id)

INDEX(default_language_id)

---

## Unique Constraints

UNIQUE(ulid)

UNIQUE(code)

---

## Relationships

One Organization

↓

Many Users

---

One Organization

↓

Many Listings

---

One Organization

↓

Many Leads

---

One Organization

↓

Many Deals

---

One Organization

↓

One Settings Record

---

One Organization

↓

Many Positions

---

## Events

Organization Created

Organization Updated

Organization Suspended

Organization Activated

Organization Archived

---

## AI Usage

The AI engine uses organization information to:

- Route Leads
- Score Organizations
- Recommend Lead Distribution
- Market Intelligence
- Organization Performance Analytics

---

## Future Expansion

Reserved for:

- Subscription Plans
- Billing
- ERP Integration
- Multi-Branch Management
- White Label Support

---

## Notes

Organizations represent companies only.

They never represent customers.

Customers are stored inside the People module.

## Known Discrepancy

The broader DB101 design and global ULID/audit standards exceed the intentional BF006 staged
implementation. This is recorded as a known architecture/documentation discrepancy; it is not
resolved by changing the BF006 table during documentation synchronization.
