# Reference Data Database Schema

Module ID: M00

Module Name: Reference Data

Version: 1.0

Status: Documentation Freeze v1.0

---

# Purpose

The Reference Data module provides all shared lookup data used across the Directors Resale Platform.

Reference data represents standardized information that is shared between multiple business modules.

These tables are considered system master data and should never contain transactional business information.

Every business module should reference these tables using Foreign Keys instead of storing duplicate values.

---

# Responsibilities

The Reference Data module is responsible for:

- Geographic information
- Languages
- Currencies
- Currency exchange rates
- Property lookup values
- Shared business classifications

---

# Design Principles

Reference Data should always follow these principles:

- Stable
- Reusable
- Normalized
- Shared across modules
- Managed centrally
- Seedable
- Multi-language ready
- Referenced using Foreign Keys
- Never duplicated
- Never store business transactions

---

# Module Boundaries

Reference Data is responsible only for reusable lookup data shared across multiple modules.

This module must not contain:

- Business transactions
- Workflow states
- Organization-specific configuration
- User-generated data
- AI generated data

Business entities such as Developers, Compounds and Projects belong to the Property Engine.

Workflow entities belong to their respective business modules.

---

# Database Tables

## Global Reference Data

| ID | Table | Status |
|-----|---------------------|-----------|
| DB001 | countries | Planned |
| DB002 | languages | Planned |
| DB003 | currencies | Planned |
| DB004 | currency_rates | Planned |
| DB005 | states | Planned |
| DB006 | cities | Planned |
| DB007 | districts | Planned |
| DB008 | postal_codes | Planned |

---

## Business Reference Data

| ID | Table | Status |
|-----|---------------------------|-----------|
| DB009 | property_types | Planned |
| DB010 | property_categories | Planned |
| DB011 | finishing_types | Planned |
| DB012 | delivery_statuses | Planned |
| DB013 | ownership_types | Planned |
| DB014 | amenities | Planned |
| DB015 | amenity_categories | Planned |

---

# Module Dependencies

## Depends On

None

Reference Data is the foundation module.

## Referenced By

- Core
- CRM
- Property Engine
- Matching Engine
- Deal Engine
- Commission Engine
- Notification Engine
- AI Engine
- Analytics

---

# Relationships

| Parent | Child |
|----------|---------|
| countries | states |
| states | cities |
| cities | districts |
| districts | postal_codes |
| currencies | currency_rates |
| property_categories | property_types |
| amenity_categories | amenities |

---

# Reference Data Ownership

| Table | Managed By |
|---------|------------|
| Countries | System |
| Languages | System |
| Currencies | System |
| Currency Rates | System |
| States | System |
| Cities | System |
| Districts | System |
| Postal Codes | System |
| Property Types | System |
| Property Categories | System |
| Finishing Types | System |
| Delivery Statuses | System |
| Ownership Types | System |
| Amenities | System |
| Amenity Categories | System |

---

# Seed Strategy

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
| 9 | Business Reference Data |

---

# MVP Seed

The MVP includes only:

Country

- Egypt

Language

- Arabic

Currency

- EGP

Timezone

- Africa/Cairo

Additional countries, languages and currencies will be introduced in future releases through Seed Data.

Additional reference records will be introduced through seed migrations without requiring schema changes.

The System User account must be created before seeding Reference Data to preserve audit consistency.

---

# Migration Strategy

The logical documentation order is independent from the physical migration execution order.

Migration execution order will be determined based on foreign key dependencies during implementation.

Detailed migration documentation will be maintained under:

docs/database/migrations/

---

# AI Usage

The AI Engine uses reference data for:

- Market Intelligence
- Regional Analysis
- Smart Matching
- Duplicate Detection
- Property Classification
- Recommendation Engine
- Analytics
- Reporting
- AI Content Generation

---

# Future Expansion

Future tables may include:

- Tax Rules
- VAT Rates
- Banks
- Payment Methods
- Construction Companies
- Interior Design Styles
- Educational Levels
- Property View Types
- Parking Types

---

# Related ADRs

| ADR | Title |
|------|-----------------------------------------------|
| ADR-005 | ULID Strategy |
| ADR-006 | Generic Attachments |
| ADR-013 | Reference Data Strategy |
| ADR-014 | Multi-Currency Strategy |
| ADR-016 | Business Entities vs Reference Data |
| ADR-017 | MVP First, Global Architecture |

---

# Module Statistics

| Item | Value |
|------|-------|
| Module ID | M00 |
| Module Name | Reference Data |
| Version | 1.0 |
| Status | Documentation Freeze v1.0 |
| Total Tables | 15 |
| Dependencies | None |
| Referenced By | Core, CRM, Property Engine, Matching Engine, Deal Engine, Commission Engine, Notification Engine, AI Engine, Analytics |
| Estimated Countries | 250+ |
| Estimated States / Governorates | 5,000+ |
| Estimated Cities | 50,000+ |
| Estimated Districts | 500,000+ |
| Estimated Postal Codes | Millions |
| Estimated Property Types | 20+ |
| Estimated Amenities | 100+ |
| MVP Scope | Egypt |
| Future Scope | Global |

---

# Related Database Contracts

| Contract | Status |
|-----------|--------|
| DB001-countries.md | Planned |
| DB002-languages.md | Planned |
| DB003-currencies.md | Planned |
| DB004-currency_rates.md | Planned |
| DB005-states.md | Planned |
| DB006-cities.md | Planned |
| DB007-districts.md | Planned |
| DB008-postal_codes.md | Planned |
| DB009-property_types.md | Planned |
| DB010-property_categories.md | Planned |
| DB011-finishing_types.md | Planned |
| DB012-delivery_statuses.md | Planned |
| DB013-ownership_types.md | Planned |
| DB014-amenities.md | Planned |
| DB015-amenity_categories.md | Planned |

---

# Notes

Reference Data tables should never contain business transactions.

Business entities such as Developers, Projects and Compounds belong to the Property Engine module.

All business modules must reference these tables using Foreign Keys.

Reference Data is considered the foundation of the entire database architecture.