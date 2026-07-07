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

# Table 1

# organizations

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
| uuid | CHAR(36) | No | Public Identifier |
| parent_organization_id | BIGINT UNSIGNED | Yes | Parent Franchise |
| code | VARCHAR(100) | No | Unique Business Code |
| legal_name | VARCHAR(255) | No | Official Company Name |
| display_name | VARCHAR(255) | No | Display Name |
| type | ENUM | No | System / Franchise / Partner Agency |
| email | VARCHAR(255) | Yes | Main Email |
| phone | VARCHAR(50) | Yes | Main Phone |
| website | VARCHAR(255) | Yes | Company Website |
| tax_number | VARCHAR(100) | Yes | Future Finance |
| commercial_register | VARCHAR(100) | Yes | Future Finance |
| country_id | BIGINT UNSIGNED | Yes | Reference Data |
| city_id | BIGINT UNSIGNED | Yes | Reference Data |
| address | TEXT | Yes | Company Address |
| logo_url | VARCHAR(500) | Yes | Logo |
| timezone | VARCHAR(100) | No | Default Timezone |
| default_language | ENUM | No | ar / en |
| status | ENUM | No | Active / Inactive / Suspended |
| created_by_user_id | BIGINT UNSIGNED | Yes | Audit |
| updated_by_user_id | BIGINT UNSIGNED | Yes | Audit |
| created_at | TIMESTAMP | No | |
| updated_at | TIMESTAMP | No | |
| deleted_at | TIMESTAMP | Yes | Soft Delete |

---

## Enums

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

### default_language

```text
ar

en
```

---

## Foreign Keys

parent_organization_id

→ organizations.id

country_id

→ countries.id

city_id

→ cities.id

created_by_user_id

→ users.id

updated_by_user_id

→ users.id

---

## Indexes

INDEX(code)

INDEX(type)

INDEX(status)

INDEX(parent_organization_id)

INDEX(country_id)

INDEX(city_id)

---

## Unique Constraints

UNIQUE(uuid)

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