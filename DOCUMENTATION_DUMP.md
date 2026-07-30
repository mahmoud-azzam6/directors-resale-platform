# Relative Path

AI_CONTEXT.md

## Line Count

407

## Full Content

# AI_CONTEXT.md

# Directors Resale Platform

Version: 1.0

---

# Project Overview

Directors Resale Platform is an enterprise SaaS platform designed to manage real estate resale operations across a network of franchise organizations and partner agencies.

The platform is not a traditional CRM or property portal.

It is a complete business operating platform for resale transactions, commission management, approvals, AI-powered matching, analytics, and market intelligence.

Commercial Brand Name:

Directors Resale Hub

Internal Project Name:

Directors Resale Platform

---

# Project Philosophy

The platform follows five core principles.

## 1. History First

Business history must never be lost.

Every important change should be preserved.

Never overwrite historical business data.

---

## 2. Human Approval

AI can recommend.

Users can request.

Only authorized humans approve business-critical actions.

Examples:

* Property Approval
* Sold Approval
* Transfer Approval
* Commission Override

---

## 3. AI Suggests — Human Decides

Artificial Intelligence never makes final business decisions.

AI responsibilities:

* Matching
* Recommendations
* Duplicate Detection
* OCR
* Market Intelligence
* Content Generation

Humans approve the final action.

---

## 4. Event Driven Architecture

Everything important is treated as a business event.

Examples:

Property Listed

Owner Approved

Lead Assigned

Deal Created

Deal Sold

Transfer Completed

Commission Generated

Notifications and automations react to events instead of direct module-to-module calls.

---

## 5. No Hard Delete

Business records are never permanently removed.

Use:

* Archive
* Suspend
* History
* Audit

instead of deleting records.

---

# Product Vision

Build the leading enterprise resale platform for franchise-based real estate companies.

The system must support:

* Franchise Networks
* Partner Agencies
* Sales Teams
* Commission Management
* Property Marketplace
* AI Matching
* Market Intelligence
* Enterprise Audit
* Multi Currency
* Future Mobile Applications
* Third-party Integrations

---

# Development Principles

Always design for scalability.

Never design only for today's requirements.

Future expansion should require adding modules instead of rewriting existing ones.

---

# Backend Architecture

Architecture Pattern:

Repository Pattern

↓

Service Layer

↓

Controller Layer

↓

REST API

↓

Frontend

Business logic must remain inside Services.

Repositories are responsible only for database access.

Controllers contain no business logic.

---

# Database Principles

Normalize business entities.

Avoid duplicated business data.

Avoid JSON except for:

* AI payloads
* Integration payloads
* Dynamic settings
* Logs

Business fields must be stored as proper database columns.
All *_by columns reference the internal users.id primary key.
Public ULIDs must never be stored in audit relationships.

---

# Naming Conventions

Tables:

snake_case

plural

Example:

properties

property_listings

commission_transactions

Columns:

snake_case

Foreign Keys:

table_singular_id

Example:

organization_id

property_id

deal_id

---

# Status Strategy

Statuses must be standardized across the platform.

Reuse common values whenever possible.

Examples:

draft

pending

approved

rejected

active

inactive

hold

cancelled

completed

archived

---

# Security Principles

Every API request must be authenticated.

Every business action must be authorized.

Never expose owner phone numbers outside approved workflows.

Always log sensitive operations.

---

# Audit Strategy

Every critical action should be traceable.

Examples:

Who created it?

Who modified it?

Who approved it?

Who transferred it?

When did it happen?

---

# AI Strategy

AI is a business assistant.

AI is not a business decision maker.

AI modules include:

* OCR
* Identity Matching
* Duplicate Detection
* Smart Matching
* Opportunity Discovery
* Lead Scoring
* Market Intelligence
* Listing Content Generation
* Fraud Detection

---

# Core Modules

Core

CRM

Property

Deals

Commissions

Transfers

Notifications

Communications

AI

Analytics

Integrations

Each module must remain independent.

Communication between modules should happen through business events whenever possible.

---

# Development Workflow

Every feature follows the same lifecycle.

Business Rule

↓

Architecture

↓

Database

↓

API

↓

Backend

↓

Frontend

↓

Testing

No implementation starts before architecture and database design are complete.

---

# Project Roles

Product Owner

Defines business vision and priorities.

Technical PM / Solution Architect

Owns architecture, database design, API design, implementation planning, technical decisions, and development governance.

Developer

Implements approved architecture.

AI Assistant

Supports implementation while respecting this document.

---

# Golden Rules

Never break architecture for speed.

Never duplicate business logic.

Never bypass approval workflows.

Never expose confidential business information.

Always preserve business history.

Always think enterprise-first.

Always design for future expansion.

This document is the primary technical reference for all AI assistants, developers, and contributors working on Directors Resale Platform.

---

# Relative Path

CONTRIBUTING.md

## Line Count

0

## Full Content


---

# Relative Path

docs/ai/AI_CONTENT_GENERATION.md

## Line Count

0

## Full Content


---

# Relative Path

docs/ai/AI_CONTEXT.md

## Line Count

0

## Full Content


---

# Relative Path

docs/ai/AI_DUPLICATE_DETECTION.md

## Line Count

0

## Full Content


---

# Relative Path

docs/ai/AI_MARKET_INTELLIGENCE.md

## Line Count

0

## Full Content


---

# Relative Path

docs/ai/AI_MATCHING_ENGINE.md

## Line Count

0

## Full Content


---

# Relative Path

docs/ai/AI_OCR.md

## Line Count

0

## Full Content


---

# Relative Path

docs/ai/AI_ROADMAP.md

## Line Count

0

## Full Content


---

# Relative Path

docs/architecture/AI_ARCHITECTURE.md

## Line Count

0

## Full Content


---

# Relative Path

docs/architecture/DATABASE_ARCHITECTURE.md

## Line Count

583

## Full Content

# DATABASE_ARCHITECTURE.md

# Directors Resale Platform

Version: 1.0

Status: Approved

---

# Purpose

This document defines the database architecture of Directors Resale Platform.

It describes:

* Database modules
* Module responsibilities
* Relationships
* Ownership
* Business boundaries
* Event flow
* Approval flow
* AI interaction
* Integration strategy

This document intentionally does not describe table structures in detail.

Detailed schemas are documented separately in DATABASE_SCHEMA.md.

---

# Architecture Philosophy

The database is designed using Domain-Driven Design (DDD) principles.

Business domains are isolated into independent modules.

Each module owns its own data.

Cross-module communication should happen through business events whenever possible.

---

# High Level Architecture

```text
                    Core Platform
                          │
        ┌─────────────────┼─────────────────┐
        │                 │                 │
      Identity          CRM            Property
        │                 │                 │
        └──────────────┬──┴──────────────┐
                       │                 │
                    Matching         Deal Engine
                       │                 │
                       └──────────┬──────┘
                                  │
                           Commission Engine
                                  │
              ┌───────────────────┼────────────────────┐
              │                   │                    │
      Notifications          AI Engine          Analytics
                                  │
                           Integration Layer
```

---

# Module 1

## Core

Purpose

Provides the platform foundation.

Responsibilities

* Organizations
* Users
* Positions
* Permissions
* Settings
* Audit
* Events

Owns

* organizations
* organization_settings
* users
* user_profiles
* positions
* permissions
* system_events
* activity_logs

No business module should duplicate identity information.

---

# Module 2

## CRM

Purpose

Represents people and customer relationships.

Responsibilities

* People
* Leads
* Requirements
* Customer Journey

Owns

* people
* people_history
* leads
* lead_assignment_history
* requirement_groups
* requirements

Business Rule

One Person may have multiple Leads.

One Lead may have multiple Requirements.

---

# Module 3

## Property Engine

Purpose

Represents physical real estate assets.

Responsibilities

* Properties
* Ownership
* Listings
* Media
* Approval

Owns

* properties
* property_ownerships
* property_listings
* property_media
* owner_approvals
* property_listing_history

Business Rule

Property is permanent.

Ownership changes.

Listings are temporary.

Never mix those concepts.

---

# Module 4

## Matching Engine

Purpose

Creates relationships between requirements and listings.

Responsibilities

* AI Matching
* Manual Matching
* Reverse Matching
* Match Feedback

Owns

* matches
* match_feedback

Business Rule

Matches are stored.

Matches are never calculated only at runtime.

Historical matches are preserved.

---

# Module 5

## Deal Engine

Purpose

Represents negotiations and completed transactions.

Responsibilities

* Negotiations
* Offers
* Contracts
* Sold Workflow

Owns

* deals
* deal_participants
* deal_history

Business Rule

Multiple deals may exist for the same listing.

Only one deal becomes Sold.

---

# Module 6

## Commission Engine

Purpose

Calculates financial commissions.

Responsibilities

* Commission Plans
* Overrides
* Sharing
* Transactions

Owns

* commission_plans
* commission_plan_rules
* commission_transactions

Business Rule

Commission values are stored as snapshots.

Changing commission plans never changes historical transactions.

---

# Module 7

## Approval Engine

Purpose

Provides a generic approval workflow.

Responsibilities

* Owner Approval
* Sold Approval
* Transfer Approval
* Commission Approval

Owns

* approval_requests

Business Rule

Approval engine is generic.

No module implements custom approval logic.

---

# Module 8

## Transfer Engine

Purpose

Handles movement of users and business assets.

Responsibilities

* User Transfers
* Asset Transfers
* Listing Agreements

Owns

* user_transfers
* user_asset_transfers
* listing_transfer_agreements

Business Rule

Transfers preserve complete history.

Nothing is overwritten.

---

# Module 9

## Notification Engine

Purpose

Handles user notifications.

Responsibilities

* In-App Notifications
* Email
* WhatsApp
* Future SMS

Owns

* notifications
* notification_preferences
* notification_templates
* notification_batches

Notifications are reactions to business events.

---

# Module 10

## Communication Engine

Purpose

Tracks external communications.

Responsibilities

* Email Logs
* WhatsApp Logs
* Future Communication Providers

Owns

* communication_logs
* communication_templates

---

# Module 11

## AI Engine

Purpose

Provides intelligent assistance.

Responsibilities

* OCR
* Duplicate Detection
* Identity Matching
* Smart Matching
* Content Generation
* Opportunity Discovery
* Fraud Detection
* Market Intelligence

Owns

* ai_events
* ai_profiles
* ai_insights
* ai_recommendations

Business Rule

AI never performs business actions.

AI only generates recommendations.

---

# Module 12

## Analytics

Purpose

Provides reporting and business intelligence.

Responsibilities

* KPIs
* Heat Maps
* Dashboards
* Demand Index
* Forecasting

Analytics primarily consumes data.

It should not own transactional business data.

---

# Module 13

## Integration Layer

Purpose

Connects external systems.

Responsibilities

* Webhooks
* API Keys
* Sync Jobs
* External Events

Future integrations include

* ERP
* CRM
* Power BI
* Accounting Systems
* Digital Signature Platforms

---

# Cross Module Rules

CRM never owns property data.

Property never owns people.

Deals never own properties.

Deals reference Listings.

Listings reference Ownership.

Ownership references People.

---

# Event Flow

Business Events

↓

Automation

↓

Notifications

↓

AI Learning

↓

Analytics

Every important business action generates a system event.

---

# Approval Flow

Business Action

↓

Pending Approval

↓

Approve / Reject

↓

Business Event

↓

Automation

Approval always happens before financial impact.

---

# AI Flow

Business Data

↓

AI Analysis

↓

Recommendation

↓

Human Decision

↓

Business Action

AI never bypasses approvals.

---

# Security Model

Authentication

↓

Authorization

↓

Business Validation

↓

Approval

↓

Execution

↓

Audit

Every business action must be auditable.

---

# Future Expansion

The architecture is designed to support future modules without changing existing domains.

Potential future modules include:

* Mobile Applications
* Public API
* Customer Portal
* Digital Contracts
* E-Signature
* Payment Gateway
* AI Agents
* Marketplace Integrations

---

# Final Principle

The database models business facts, not UI screens.

Every table exists because of a business concept.

Never create database tables only to satisfy a page or interface.

---

# Relative Path

docs/architecture/EVENT_ARCHITECTURE.md

## Line Count

0

## Full Content


---

# Relative Path

docs/architecture/INTEGRATION_ARCHITECTURE.md

## Line Count

0

## Full Content


---

# Relative Path

docs/architecture/PERMISSION_ARCHITECTURE.md

## Line Count

0

## Full Content


---

# Relative Path

docs/architecture/SECURITY_ARCHITECTURE.md

## Line Count

0

## Full Content


---

# Relative Path

docs/architecture/SYSTEM_ARCHITECTURE.md

## Line Count

0

## Full Content


---

# Relative Path

docs/database/DATABASE_CHANGELOG.md

## Line Count

0

## Full Content


---

# Relative Path

docs/database/DATABASE_OVERVIEW.md

## Line Count

0

## Full Content


---

# Relative Path

docs/database/DATABASE_RELATIONSHIPS.md

## Line Count

0

## Full Content


---

# Relative Path

docs/database/DATABASE_STANDARDS.md

## Line Count

553

## Full Content

# Database Standards

Version: 1.0

Status: Documentation Freeze v1.0

Last Updated: YYYY-MM-DD

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

---

# Relative Path

docs/database/ENUMS.md

## Line Count

0

## Full Content


---

# Relative Path

docs/database/schema/ai.md

## Line Count

0

## Full Content


---

# Relative Path

docs/database/schema/analytics.md

## Line Count

0

## Full Content


---

# Relative Path

docs/database/schema/commissions.md

## Line Count

0

## Full Content


---

# Relative Path

docs/database/schema/communication.md

## Line Count

0

## Full Content


---

# Relative Path

docs/database/schema/core.md

## Line Count

277

## Full Content

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

---

# Relative Path

docs/database/schema/crm.md

## Line Count

0

## Full Content


---

# Relative Path

docs/database/schema/deals.md

## Line Count

0

## Full Content


---

# Relative Path

docs/database/schema/matching.md

## Line Count

0

## Full Content


---

# Relative Path

docs/database/schema/notifications.md

## Line Count

0

## Full Content


---

# Relative Path

docs/database/schema/property.md

## Line Count

0

## Full Content


---

# Relative Path

docs/database/schema/reference-data.md

## Line Count

316

## Full Content

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

---

# Relative Path

docs/database/schema/transfers.md

## Line Count

0

## Full Content


---

# Relative Path

docs/database/SEED_DATA.md

## Line Count

0

## Full Content


---

# Relative Path

docs/database/tables/DB001-countries.md

## Line Count

0

## Full Content


---

# Relative Path

docs/database/tables/DB002-languages.md

## Line Count

0

## Full Content


---

# Relative Path

docs/database/tables/DB003-currencies.md

## Line Count

0

## Full Content


---

# Relative Path

docs/database/tables/DB004-currency_rates.md

## Line Count

0

## Full Content


---

# Relative Path

docs/decisions/ADR_INDEX.md

## Line Count

11

## Full Content

# ADR Index

| ADR | Title | Status |
|------|-----------------------------------------------|----------|
| ADR-001 | Organization Hierarchy | Approved |
| ADR-005 | ULID Strategy | Approved |
| ADR-006 | Generic Attachments | Approved |
| ADR-013 | Reference Data Strategy | Approved |
| ADR-014 | Multi-Currency Strategy | Approved |
| ADR-016 | Business Entities vs Reference Data | Approved |
| ADR-017 | MVP First, Global Architecture | Approved |

---

# Relative Path

docs/decisions/ADR-001-Organization-Hierarchy.md

## Line Count

0

## Full Content


---

# Relative Path

docs/decisions/ADR-017-MVP-First-Global-Architecture.md

## Line Count

75

## Full Content

# ADR-017

## Title

MVP First, Global Architecture

---

## Status

Approved

---

## Context

The platform will initially launch in Egypt.

Future expansion is planned for GCC countries.

Potential future expansion may include additional international markets.

The architecture must support expansion without requiring database redesign.

---

## Decision

The system architecture will always be global.

The MVP implementation will initially include:

- Egypt
- Arabic
- Egyptian Pound (EGP)
- Africa/Cairo Timezone

Additional countries, languages and currencies will be introduced through Seed Data and Configuration.

---

## Consequences

### Benefits

- No future database redesign.
- Faster expansion.
- Cleaner architecture.
- Simpler MVP implementation.
- Lower development cost.

### Trade-offs

- Some reference tables contain future-ready structures.
- Initial seed data is intentionally minimal.

---

## Related ADRs

ADR-005 UUID / ULID Strategy

ADR-013 Reference Data Strategy

ADR-014 Multi-Currency Strategy

ADR-016 Business Entities vs Reference Data

---

Approved By

Product Owner

Solution Architect

---

# Relative Path

docs/decisions/ADR-018-Universal-Audit-Columns.md

## Line Count

83

## Full Content

# ADR-018

## Title

Universal Audit Columns

---

## Status

Approved

---

## Context

The Directors Resale Platform requires complete accountability for all business data.

Every important business record should preserve information about:

- Who created it
- Who modified it
- Who approved it (if applicable)
- Who archived it (if applicable)

This supports auditing, reporting, security investigations, compliance and future AI analysis.

---

## Decision

Every persistent business table must include:

- created_at
- updated_at
- created_by
- updated_by

When applicable, tables should also include:

- approved_at
- approved_by
- archived_at
- archived_by

All *_by columns reference:

users.id

Reference Data tables follow the same rule.

No exceptions.

---

## Consequences

Advantages:

- Complete auditability
- Better reporting
- Better security
- AI can understand ownership history
- Consistent database design

Disadvantages:

- Additional foreign keys
- Slightly larger tables

The benefits outweigh the costs.

---

## Approved By

Architecture Team

---

## Date

YYYY-MM-DD

---

# Relative Path

docs/development/CODING_STANDARDS.md

## Line Count

0

## Full Content


---

# Relative Path

docs/development/CURRENT_SYSTEM_STATE.md

## Line Count

0

## Full Content


---

# Relative Path

docs/development/DEVELOPMENT_LIFECYCLE.md

## Line Count

0

## Full Content


---

# Relative Path

docs/development/DEVELOPMENT_RULES.md

## Line Count

0

## Full Content


---

# Relative Path

docs/development/GIT_WORKFLOW.md

## Line Count

0

## Full Content


---

# Relative Path

docs/development/IMPLEMENTATION_PLAN.md

## Line Count

0

## Full Content


---

# Relative Path

docs/development/MASTER_INDEX.md

## Line Count

754

## Full Content

# Directors Resale Platform

# MASTER_INDEX.md

Version: 2.0

Status: Active

Last Updated: YYYY-MM-DD

---

# Project Information

| Item | Value |
|------|-------|
| Commercial Name | Directors Resale Hub |
| Internal Project Name | Directors Resale Platform |
| Project Type | Enterprise SaaS Platform |
| Industry | Real Estate Resale |
| Architecture Style | Modular + Event Driven |
| Current Phase | Sprint 1 - Core Database Design |
| Current Sprint | Sprint 1 |
| Current Version | 0.1.0 |
| Repository Status | Active |

---

# Product Scope

| Scope | Status |
|---------|--------|
| Architecture Scope | Global |
| Database Scope | Global |
| API Scope | Global |
| AI Scope | Global |
| Initial MVP | Egypt |
| Initial Language | Arabic |
| Initial Currency | EGP |
| Initial Timezone | Africa/Cairo |
| Initial Market | Egyptian Resale Market |
| Planned Expansion | GCC |
| Future Expansion | Europe (Optional) |

---

## MVP Strategy

The platform is architected for global expansion from day one.

However, the initial business implementation targets Egypt only.

New countries should be enabled through Seed Data and Configuration rather than database redesign.

Architecture remains global.

Business rollout remains incremental.

---

# Documentation Structure

## Product

- PROJECT_VISION.md
- BUSINESS_RULES.md
- EDGE_CASES.md
- FEATURE_ROADMAP.md
- PRODUCT_BACKLOG.md

---

## Architecture

- SYSTEM_ARCHITECTURE.md
- DATABASE_ARCHITECTURE.md
- EVENT_ARCHITECTURE.md
- AI_ARCHITECTURE.md
- SECURITY_ARCHITECTURE.md
- PERMISSION_ARCHITECTURE.md
- INTEGRATION_ARCHITECTURE.md

---

## Database

- DATABASE_OVERVIEW.md
- DATABASE_RELATIONSHIPS.md
- ENUMS.md
- SEED_DATA.md
- DATABASE_CHANGELOG.md
- Database Contracts (docs/database/tables/)

---

## Development

- CURRENT_SYSTEM_STATE.md
- IMPLEMENTATION_PLAN.md
- DEVELOPMENT_RULES.md
- CODING_STANDARDS.md
- MODULE_STRUCTURE.md
- GIT_WORKFLOW.md
- SPRINT_LOG.md

---

## AI

- AI_CONTEXT.md
- AI_ROADMAP.md
- AI_MATCHING_ENGINE.md
- AI_CONTENT_GENERATION.md
- AI_DUPLICATE_DETECTION.md
- AI_MARKET_INTELLIGENCE.md
- AI_OCR.md

---

# Source of Truth

| Information | Primary Source |
|-------------|----------------|
| Product Vision | PROJECT_VISION.md |
| Business Rules | BUSINESS_RULES.md |
| Edge Cases | EDGE_CASES.md |
| Database Architecture | DATABASE_ARCHITECTURE.md |
| Database Standards | DATABASE_STANDARDS.md |
| Database Modules | docs/database/schema/ |
| Database Tables | docs/database/tables/ |
| ADRs | docs/decisions/ |
| APIs | docs/api/ |

---

# Module Registry

| ID | Module | Status |
|----|-------------------------|------------|
| M00 | Reference Data | Planned |
| M01 | Core | Planned |
| M02 | CRM | Planned |
| M03 | Property Engine | Planned |
| M04 | Matching Engine | Planned |
| M05 | Deal Engine | Planned |
| M06 | Commission Engine | Planned |
| M07 | Notification Engine | Planned |
| M08 | Communication Engine | Planned |
| M09 | Approval Engine | Planned |
| M10 | Transfer Engine | Planned |
| M11 | AI Engine | Planned |
| M12 | Analytics | Planned |
| M13 | Integration Layer | Planned |
| M14 | Attachments Engine | Planned |

---

# Current Development Target

| Item | Value |
|------|-------|
| Current Phase | Sprint 1 - Core Database Foundation |
| Current Sprint | Sprint 1 |
| Current Module | M00 - Reference Data |
| Current Feature | Database Foundation |
| Current Database Contract | DB001 - Countries |
| Current Database Module | Reference Data |
| Current Status | Documentation Normalization |
| Current Milestone | Documentation Freeze v1.0 |
| Target Release | R0.2.0 |

---

# Database Registry

---

# M00 - Reference Data

| ID | Table | Status |
|-----|------------------------|-----------|
| DB001 | countries | Planned |
| DB002 | languages | Planned |
| DB003 | currencies | Planned |
| DB004 | currency_rates | Planned |
| DB005 | states | Planned |
| DB006 | cities | Planned |
| DB007 | districts | Planned |
| DB008 | postal_codes | Planned |
| DB009 | property_types | Planned |
| DB010 | property_categories | Planned |
| DB011 | finishing_types | Planned |
| DB012 | delivery_statuses | Planned |
| DB013 | ownership_types | Planned |
| DB014 | amenities | Planned |
| DB015 | amenity_categories | Planned |

---

# M01 - Core

| ID | Table | Status |
|-----|-------------------------------|-----------|
| DB101 | organizations | Planned |
| DB102 | organization_settings | Planned |
| DB103 | positions | Planned |
| DB104 | users | Planned |
| DB105 | user_profiles | Planned |
| DB106 | permissions | Planned |
| DB107 | user_permissions | Planned |
| DB108 | system_settings | Planned |
| DB109 | system_events | Planned |
| DB110 | activity_logs | Planned |

---

# M02 - CRM

| ID | Table | Status |
|-----|-------------------------------|-----------|
| DB201 | people | Planned |
| DB202 | people_history | Planned |
| DB203 | leads | Planned |
| DB204 | lead_assignment_history | Planned |
| DB205 | requirement_groups | Planned |
| DB206 | requirements | Planned |
| DB207 | requirement_notes | Planned |
| DB208 | lead_tags | Planned |

---

# M03 - Property Engine

| ID | Table | Status |
|-----|-----------------------------------|-----------|
| DB301 | developers | Planned |
| DB302 | developer_projects | Planned |
| DB303 | compounds | Planned |
| DB304 | compound_phases | Planned |
| DB305 | properties | Planned |
| DB306 | property_ownerships | Planned |
| DB307 | property_listings | Planned |
| DB308 | property_media | Planned |
| DB309 | property_listing_history | Planned |
| DB310 | owner_approvals | Planned |
| DB311 | property_notes | Planned |
| DB312 | property_tags | Planned |

---

# M04 - Matching Engine

| ID | Table | Status |
|-----|----------------------------|-----------|
| DB401 | matches | Planned |
| DB402 | match_feedback | Planned |
| DB403 | match_history | Planned |

---

# M05 - Deal Engine

| ID | Table | Status |
|-----|------------------------------|-----------|
| DB501 | deals | Planned |
| DB502 | deal_participants | Planned |
| DB503 | deal_history | Planned |
| DB504 | negotiations | Planned |
| DB505 | viewing_requests | Planned |
| DB506 | offers | Planned |
| DB507 | deal_statuses | Planned |

---

# M06 - Commission Engine

| ID | Table | Status |
|-----|-----------------------------------|-----------|
| DB601 | commission_plans | Planned |
| DB602 | commission_plan_rules | Planned |
| DB603 | commission_transactions | Planned |
| DB604 | commission_shares | Planned |
| DB605 | commission_snapshots | Planned |

---

# M07 - Notification Engine

| ID | Table | Status |
|-----|---------------------------------|-----------|
| DB701 | notifications | Planned |
| DB702 | notification_preferences | Planned |
| DB703 | notification_templates | Planned |
| DB704 | notification_batches | Planned |

---

# M08 - Communication Engine

| ID | Table | Status |
|-----|--------------------------------|-----------|
| DB801 | communication_logs | Planned |
| DB802 | communication_templates | Planned |
| DB803 | email_queue | Planned |
| DB804 | whatsapp_queue | Planned |

---

# M09 - Approval Engine

| ID | Table | Status |
|-----|-------------------------------|-----------|
| DB901 | approval_requests | Planned |
| DB902 | approval_history | Planned |

---

# M10 - Transfer Engine

| ID | Table | Status |
|-----|-----------------------------------------|-----------|
| DB1001 | user_transfers | Planned |
| DB1002 | user_asset_transfers | Planned |
| DB1003 | listing_transfer_agreements | Planned |
| DB1004 | transfer_history | Planned |

---

# M11 - AI Engine

| ID | Table | Status |
|-----|--------------------------------|-----------|
| DB1101 | ai_events | Planned |
| DB1102 | ai_profiles | Planned |
| DB1103 | ai_insights | Planned |
| DB1104 | ai_recommendations | Planned |
| DB1105 | ai_duplicate_candidates | Planned |
| DB1106 | ai_matching_history | Planned |

---

# M12 - Analytics

Analytics is implemented using reporting tables, SQL Views, Materialized Views and aggregated datasets.

(No transactional tables.)

---

# M13 - Integration Layer

| ID | Table | Status |
|-----|--------------------------|-----------|
| DB1301 | api_keys | Planned |
| DB1302 | webhooks | Planned |
| DB1303 | integration_logs | Planned |
| DB1304 | sync_jobs | Planned |

---

# M14 - Attachments Engine

| ID | Table | Status |
|-----|---------------------------|-----------|
| DB1401 | attachments | Planned |
| DB1402 | attachment_links | Planned |
| DB1403 | attachment_versions | Planned |

---

# ADR Registry

| ADR | Title | Status |
|------|-----------------------------------------------------------|-----------|
| ADR-001 | Organization Hierarchy | ✅ Approved |
| ADR-002 | Property / Ownership / Listing Separation | Planned |
| ADR-003 | Requirement Groups | Planned |
| ADR-004 | Event Driven Architecture | Planned |
| ADR-005 | ULID Strategy | Planned |
| ADR-006 | Generic Attachments Module | Planned |
| ADR-007 | Match Persistence Strategy | Planned |
| ADR-008 | Commission Snapshot Strategy | Planned |
| ADR-009 | AI Suggests, Human Decides | Planned |
| ADR-010 | History First Philosophy | Planned |
| ADR-011 | Approval Engine | Planned |
| ADR-012 | Transfer Engine | Planned |
| ADR-013 | Reference Data Strategy | Planned |
| ADR-014 | Multi-Currency Strategy | Planned |
| ADR-015 | Organization Settings Inheritance | Planned |
| ADR-016 | Business Entities vs Reference Data | Approved |
| ADR-017 | MVP First, Global Architecture | Approved |
| ADR-018 | Universal Audit Columns | Approved |

---

# API Registry

| ID | Module | Endpoint Group | Status |
|------|----------------------|-----------------------|-----------|
| API001 | Authentication | /auth | Planned |
| API002 | Organizations | /organizations | Planned |
| API003 | Users | /users | Planned |
| API004 | Permissions | /permissions | Planned |
| API005 | CRM | /people | Planned |
| API006 | Leads | /leads | Planned |
| API007 | Requirements | /requirements | Planned |
| API008 | Properties | /properties | Planned |
| API009 | Listings | /listings | Planned |
| API010 | Matching | /matches | Planned |
| API011 | Deals | /deals | Planned |
| API012 | Commissions | /commissions | Planned |
| API013 | Transfers | /transfers | Planned |
| API014 | Notifications | /notifications | Planned |
| API015 | Communications | /communications | Planned |
| API016 | Attachments | /attachments | Planned |
| API017 | AI | /ai | Planned |
| API018 | Reports | /reports | Planned |
| API019 | Dashboard | /dashboard | Planned |
| API020 | Integrations | /integrations | Planned |

---

# Feature Registry

| ID | Feature | Module | Status |
|------|----------------------------------------|----------------|-----------|
| F001 | Authentication | Core | Planned |
| F002 | Organization Management | Core | Planned |
| F003 | User Management | Core | Planned |
| F004 | Dynamic Positions | Core | Planned |
| F005 | CRM | CRM | Planned |
| F006 | Lead Management | CRM | Planned |
| F007 | Requirement Management | CRM | Planned |
| F008 | Property Management | Property | Planned |
| F009 | Listing Management | Property | Planned |
| F010 | Matching Engine | Matching | Planned |
| F011 | Deal Management | Deals | Planned |
| F012 | Negotiation Workflow | Deals | Planned |
| F013 | Approval Workflow | Approval | Planned |
| F014 | Commission Engine | Commission | Planned |
| F015 | Transfer Engine | Transfer | Planned |
| F016 | Notification Engine | Notification | Planned |
| F017 | Communication Center | Communication | Planned |
| F018 | AI Assistant | AI | Planned |
| F019 | AI OCR | AI | Planned |
| F020 | AI Matching | AI | Planned |
| F021 | AI Market Intelligence | AI | Planned |
| F022 | Reports & Analytics | Analytics | Planned |
| F023 | Dashboard | Analytics | Planned |
| F024 | Attachments Management | Attachments | Planned |
| F025 | Integration Layer | Integration | Planned |

---

# Sprint Registry

| Sprint | Goal | Status |
|----------|----------------------------------------------|-------------|
| Sprint 0 | Discovery & Architecture Foundation | ✅ Completed |
| Sprint 1 | Core Database Module | In Progress |
| Sprint 2 | CRM Module | Planned |
| Sprint 3 | Property Engine | Planned |
| Sprint 4 | Matching Engine | Planned |
| Sprint 5 | Deal Engine | Planned |
| Sprint 6 | Commission Engine | Planned |
| Sprint 7 | Notification & Communication | Planned |
| Sprint 8 | AI Engine | Planned |
| Sprint 9 | Analytics & Reporting | Planned |
| Sprint 10 | Integration Layer | Planned |
| Sprint 11 | Backend API Completion | Planned |
| Sprint 12 | Frontend Dashboard | Planned |
| Sprint 13 | AI Optimization | Planned |
| Sprint 14 | Testing & QA | Planned |
| Sprint 15 | Beta Release | Planned |

---

# Release Registry

| Release | Description | Status |
|-----------|--------------------------------------|-----------|
| R0.1.0 | Architecture Foundation | Current |
| R0.2.0 | Database Complete | Planned |
| R0.3.0 | Backend MVP | Planned |
| R0.4.0 | Frontend MVP | Planned |
| R0.5.0 | AI MVP | Planned |
| R0.6.0 | Internal Beta | Planned |
| R0.7.0 | Closed Beta | Planned |
| R1.0.0 | Production Release | Planned |

---

# Version History

| Version | Description |
|----------|------------------------------------------------|
| 0.1.0 | Architecture & Documentation Foundation |
| 0.2.0 | Database Design Complete |
| 0.3.0 | Backend Core Modules |
| 0.4.0 | Frontend Dashboard |
| 0.5.0 | AI Integration |
| 0.6.0 | Internal Testing |
| 0.7.0 | Beta Release |
| 1.0.0 | Production Release |

---

# Current Focus

| Item | Value |
|------|-------|
| Current Phase | Sprint 1 - Documentation Normalization |
| Current Module | M00 - Reference Data |
| Current Document | docs/database/schema/reference-data.md |
| Current Database Contract | DB001-countries.md |
| Current ADR | ADR-017 - MVP First, Global Architecture |
| Current Milestone | Documentation Freeze v1.0 |
| Current Release Target | R0.2.0 |

---

# Progress Dashboard

| Area | Progress |
|------|----------|
| Product Discovery | ████████████████████ 100% |
| Business Analysis | ████████████████████ 100% |
| Architecture | ████████████████████ 100% |
| Documentation | ██████████████████░░ 90% |
| Database Design | ███░░░░░░░░░░░░░░░░░ 10% |
| Backend Development | ░░░░░░░░░░░░░░░░░░░░ 0% |
| Frontend Development | ░░░░░░░░░░░░░░░░░░░░ 0% |
| AI Development | ░░░░░░░░░░░░░░░░░░░░ 0% |
| Testing | ░░░░░░░░░░░░░░░░░░░░ 0% |
| Production Readiness | ░░░░░░░░░░░░░░░░░░░░ 0% |

---

# Current Statistics

| Metric | Value |
|---------|-------|
| Modules | 15 |
| Planned Database Tables | 70+ |
| Planned APIs | 20+ |
| Planned Features | 25+ |
| ADR Documents | 17 |
| Documentation Files | 40+ |
| Project Templates | 6 |
| Current Sprint | Sprint 1 |
| Current Release | R0.1.0 |

---

# Team Roles

## Product Owner

Responsibilities:

- Product Vision
- Business Decisions
- Product Priorities
- Workflow Approval
- Feature Acceptance

---

## Solution Architect / Technical PM

Responsibilities:

- System Architecture
- Database Design
- API Design
- Technical Decisions
- Sprint Planning
- Documentation Governance
- Development Guidance

---

## Developers

Responsibilities:

- Backend Development
- Frontend Development
- Database Implementation
- Testing
- Bug Fixing

---

## AI Assistants

Responsibilities:

- Documentation Support
- Code Generation
- Architecture Validation
- Refactoring Assistance
- Test Generation

AI assistants must always follow:

- AI_CONTEXT.md
- MASTER_INDEX.md
- PROJECT_VISION.md
- DATABASE_ARCHITECTURE.md

---

# Development Workflow

Every feature follows the same lifecycle.

Idea

↓

Workshop (if required)

↓

ADR (if required)

↓

Business Rules

↓

Architecture

↓

Database

↓

API

↓

Backend

↓

Frontend

↓

Testing

↓

Release

No implementation starts before Architecture and Database Design are approved.

---

# Documentation Update Rules

Whenever a new feature is introduced, update the following documents if applicable:

- MASTER_INDEX.md
- PROJECT_STATUS.md
- VERSION.md
- DATABASE_CHANGELOG.md
- CURRENT_SYSTEM_STATE.md
- IMPLEMENTATION_PLAN.md

---

# Definition of Done

A feature is considered complete only when:

- Business Rules Approved
- Architecture Approved
- Database Designed
- API Documented
- Backend Completed
- Frontend Completed
- Permissions Implemented
- Notifications Implemented
- Audit Logging Added
- AI Hooks Added (if applicable)
- Tests Passed
- Documentation Updated

---

# Project Principles

- Architecture First
- History First
- Single Source of Truth
- Human Approval
- AI Suggests, Human Decides
- Event Driven Architecture
- No Hard Delete
- Enterprise First
- Modular Design
- API First
- Security by Design
- Scalability by Design

---

# Next Milestones

## Sprint 1

- M00 Reference Data
- M01 Core

---

## Sprint 2

- CRM Module

---

## Sprint 3

- Property Engine

---

## Sprint 4

- Matching Engine

---

## Sprint 5

- Deal Engine

---

## Project Notes

This document is the master registry for the entire Directors Resale Platform project.

Every module, database table, API, feature, architecture decision, sprint, and release must be registered here before implementation.

MASTER_INDEX.md is the first document to review before starting any development session.

AI assistants, developers, and contributors should use this file together with AI_CONTEXT.md as the primary navigation guide for the project.

---

# Relative Path

docs/development/MODULE_STRUCTURE.md

## Line Count

0

## Full Content


---

# Relative Path

docs/development/SPRINT_LOG.md

## Line Count

134

## Full Content

# Sprint Log

---

# Sprint 0

## Name

Enterprise Foundation

---

## Status

Completed ✅

---

## Duration

Architecture Phase

---

## Objective

Design the complete enterprise architecture before implementation.

---

## Completed

- Repository Structure
- Documentation Structure
- Product Vision
- Business Rules
- Edge Cases
- AI Context
- Architecture Documentation
- Database Architecture
- Database Standards
- Reference Data Module
- Master Index
- ADR Foundation
- GitHub Repository
- Git Workflow
- Documentation Freeze v1.0

---

## Deliverables

- Enterprise Repository
- Documentation Foundation
- Architecture Decisions
- Database Standards
- Development Workflow

---

## Lessons Learned

- Single Source of Truth is mandatory.
- Architecture decisions should always be documented using ADRs.
- Documentation should be completed before implementation.
- Every module should have clear ownership and responsibilities.

---

## Result

Sprint completed successfully.

The project is now ready for implementation.

---

# Sprint 1

## Name

Database Foundation

---

## Status

In Progress 🚧

---

## Objective

Build the complete Reference Data foundation.

---

## Planned Deliverables

- Database Contract Template
- DB001 - Countries
- DB002 - Languages
- DB003 - Currencies
- DB004 - Currency Rates
- DB005 - States
- DB006 - Cities
- DB007 - Districts
- DB008 - Postal Codes

---

## Current Task

DB001 - Countries Database Contract

---

## Next Task

Create the official Database Contract Template.

---

## Notes

This sprint marks the beginning of the implementation phase.

Every completed database contract will immediately produce:

- SQL Migration
- Seeder
- Repository
- Validation Rules
- API Contract

---

# Relative Path

docs/product/BUSINESS_RULES.md

## Line Count

0

## Full Content


---

# Relative Path

docs/product/EDGE_CASES.md

## Line Count

0

## Full Content


---

# Relative Path

docs/product/FEATURE_ROADMAP.md

## Line Count

0

## Full Content


---

# Relative Path

docs/product/PRODUCT_BACKLOG.md

## Line Count

0

## Full Content


---

# Relative Path

docs/product/PROJECT_VISION.md

## Line Count

0

## Full Content


---

# Relative Path

docs/templates/ADR_TEMPLATE.md

## Line Count

0

## Full Content


---

# Relative Path

docs/templates/API_TEMPLATE.md

## Line Count

23

## Full Content

Endpoint

Method

Authentication

Authorization

Business Rules

Request

Validation

Response

Errors

Events Triggered

Audit

AI Hooks

---

# Relative Path

docs/templates/FEATURE_TEMPLATE.md

## Line Count

25

## Full Content

# Feature

Business Goal

User Story

Business Rules

Database Impact

API Impact

Frontend Impact

AI Impact

Notifications

Reports

Permissions

Testing

Release Notes

---

# Relative Path

docs/templates/MODULE_TEMPLATE.md

## Line Count

57

## Full Content

# Module Name

Version

Status

---

# Purpose

---

# Responsibilities

---

# Business Rules

---

# Database Tables

---

# APIs

---

# Services

---

# Events

---

# AI Integration

---

# Permissions

---

# Notifications

---

# Reports

---

# Future Expansion

---

# Related ADRs

---

# Relative Path

docs/templates/SCREEN_TEMPLATE.md

## Line Count

21

## Full Content

Screen Name

Purpose

Actors

Permissions

Components

Actions

Business Rules

API Calls

Notifications

Edge Cases

Future Improvements

---

# Relative Path

docs/templates/TABLE_TEMPLATE.md

## Line Count

27

## Full Content

# Table Name

Purpose

Business Rules

Columns

Foreign Keys

Indexes

Constraints

Relationships

Lifecycle

Events

Permissions

AI Usage

Future Expansion

Migration Notes

---

# Relative Path

PROJECT_STATUS.md

## Line Count

22

## Full Content

# Documentation Freeze v1.0

Status: Active

Effective Date: YYYY-MM-DD

The following documents are considered the approved architectural baseline:

- MASTER_INDEX.md
- PROJECT_VISION.md
- BUSINESS_RULES.md
- EDGE_CASES.md
- DATABASE_ARCHITECTURE.md
- DATABASE_STANDARDS.md
- REFERENCE_DATA.md
- AI_CONTEXT.md

From this point forward:

- No architectural changes may be introduced without an approved ADR.
- Development must follow the documented standards.
- All implementation work should reference these documents.

---

# Relative Path

README.md

## Line Count

357

## Full Content

# Directors Resale Platform

> Enterprise SaaS Platform for Real Estate Resale Networks

**Commercial Brand:** Directors Resale Hub

**Internal Project Name:** Directors Resale Platform

Current Version: **0.1.0 (Architecture Phase)**

---

# Overview

Directors Resale Platform is an enterprise-grade SaaS platform built to manage real estate resale operations across franchise networks, partner agencies, and sales teams.

The platform combines CRM, Property Management, Matching Engine, Deal Management, Commission Management, AI, Business Intelligence, and Enterprise Workflow Automation into a single scalable system.

This is not a traditional CRM or listing website.

It is a complete operating platform for resale businesses.

---

# Core Objectives

* Build a scalable enterprise platform
* Support franchise and partner agency networks
* Manage complete resale lifecycle
* Automate business workflows
* Preserve full business history
* Deliver AI-powered recommendations
* Provide market intelligence
* Support future integrations and mobile applications

---

# Core Modules

## Core

* Organizations
* Organization Settings
* Users
* User Profiles
* Positions
* Permissions
* System Settings
* Event Engine

---

## CRM

* People
* Leads
* Requirements
* Requirement Groups
* Lead Assignment
* CRM History

---

## Property Engine

* Properties
* Ownership
* Listings
* Owner Approvals
* Property History
* Property Media

---

## Matching Engine

* AI Matching
* Manual Matching
* Match History
* Match Feedback
* Reverse Matching

---

## Deal Engine

* Deal Pipeline
* Negotiations
* Viewings
* Contracts
* Sold Approval
* Deal History

---

## Commission Engine

* Commission Plans
* Commission Rules
* Commission Sharing
* Commission Transactions
* Commission Snapshots

---

## Transfer Engine

* User Transfers
* Asset Transfers
* Listing Agreements
* Commission Protection

---

## Communication

* Notifications
* Emails
* WhatsApp
* SMS (Future)
* Communication Logs

---

## AI Engine

* Duplicate Detection
* OCR
* Identity Matching
* Listing Content Generation
* Smart Matching
* Opportunity Discovery
* Lead Scoring
* Fraud Detection
* Market Intelligence

---

## Analytics

* Dashboards
* KPIs
* Heat Maps
* Demand Index
* Reports
* Forecasting

---

## Integration Layer

* REST API
* Webhooks
* ERP Integrations
* CRM Integrations
* Power BI
* Future External Services

---

# Technology Stack

## Backend

Custom PHP API

Repository Pattern

Service Layer

REST API

MySQL

---

## Frontend

Next.js

TypeScript

Bootstrap 5

---

## Infrastructure

Cloudflare

Git

GitHub

Future Queue Workers

Future Object Storage

---

# Development Philosophy

The project follows an architecture-first approach.

Every feature must follow this lifecycle:

Business Rule

↓

Architecture

↓

Database

↓

API

↓

Backend

↓

Frontend

↓

Testing

No implementation starts before architecture approval.

---

# Project Structure

```text
backend/
frontend/
database/
resources/
storage/
scripts/
tests/
docs/

README.md
AI_CONTEXT.md
CHANGELOG.md
```

---

# Documentation

The `/docs` directory contains all project documentation.

Main sections include:

* Product
* Architecture
* Database
* API
* Development
* UI/UX
* AI
* Business Intelligence
* Project Management

---

# Architecture Principles

* History First
* Human Approval
* AI Suggests, Human Decides
* Event Driven Architecture
* No Hard Delete
* Enterprise First

---

# Current Project Status

Current Phase:

Architecture & Documentation

Current Sprint:

Sprint 1

Current Milestone:

Architecture Foundation

Implementation Status:

Not Started

Architecture Status:

Completed

Business Analysis:

Completed

Database Discovery:

Completed

---

# Long-Term Vision

Directors Resale Platform is designed to become a regional enterprise platform capable of supporting:

* Large real estate networks
* Franchise organizations
* Independent partner agencies
* Multi-country deployments
* Multi-currency operations
* AI-assisted sales operations
* Enterprise reporting and market intelligence

---

# Contributors

## Product Owner

Defines business vision, priorities, workflows, and product direction.

## Solution Architect / Technical PM

Owns architecture, database design, API design, technical planning, implementation strategy, and development governance.

## Developers

Implement approved architecture following project standards.

## AI Assistants

Support implementation while respecting AI_CONTEXT.md and project architecture.

---

# License

Private Project

Copyright © Directors Resale Platform

All rights reserved.

---

# Relative Path

VERSION.md

## Line Count

0

## Full Content


---

