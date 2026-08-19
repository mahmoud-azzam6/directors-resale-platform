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

BF006-BF007 intentionally implement a staged subset of this broader architecture. The live
`organizations` table currently contains:

- id
- parent_organization_id
- name
- code
- organization_type
- status
- created_at
- updated_at

This staged implementation is defined by `docs/sprints/BF006-Organization-Module.md`,
`database/migrations/001_create_organizations_table.sql`, and
`database/migrations/002_add_parent_organization_id_to_organizations_table.sql`. It must not be silently expanded
from this broader DB101 design. The remaining fields, relationships, ULID, and audit columns
below remain architectural/planned requirements until an approved future milestone addresses them.

## BF009 Implementation Status: users

BF009 implements the staged User entity in `users`:

- `id`
- `organization_id`
- `full_name`
- `email`
- `phone`
- `status`
- `created_at`
- `updated_at`

Each User belongs to one active System, Franchise, or Partner Agency Organization. Email is
globally unique. Normal User updates cannot change `organization_id`, and DELETE deactivates the
row with `status = inactive` rather than physically deleting it. BF010 adds nullable
`password_hash` credentials and the separate `auth_tokens` table; passwords are securely hashed,
bearer tokens are stored only as SHA-256 hashes, and tokens support expiration and revocation.
Authentication is identity-only. Authorization, Roles, Permissions, Positions, User Profiles,
Transfers, reset, registration, refresh tokens, sessions, and full audit relationships are deferred.

## BF011 Implementation Status: positions

BF011 adds the staged `positions` entity with `id`, `organization_id`, `name`, `code`, `status`,
`created_at`, and `updated_at`. Position codes are unique within an Organization, and each
Position belongs to an active supported Organization. Users have nullable `position_id`; assignment,
reassignment, and clearing require an active Position in the same Organization. Position DELETE
sets `status = inactive` without physically deleting the row or clearing existing User relationships.
Authorization, Permissions, Roles, Position hierarchy, and Team hierarchy remain deferred.

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
| parent_organization_id | BIGINT UNSIGNED | Yes | Parent Organization (System for BF007 Franchise) |
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

The broader DB101 design and global ULID/audit standards exceed the intentional BF006-BF007 staged
implementation. This is recorded as a known architecture/documentation discrepancy; it is not
resolved by changing the BF006 table during documentation synchronization.
