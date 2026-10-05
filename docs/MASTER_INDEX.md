# Directors Resale Platform

# MASTER_INDEX.md

Version: 2.0

Status: Active

Last Updated: 2026-10-05

---

# Project Information

| Item | Value |
|------|-------|
| Commercial Name | Directors Resale Hub |
| Internal Project Name | Directors Resale Platform |
| Project Type | Enterprise SaaS Platform |
| Industry | Real Estate Resale |
| Architecture Style | Modular + Event Driven |
| Current Phase | BF016 IN PROGRESS / OPEN; BF016.2/BF016.3/BF016.4 IMPLEMENTED AND VERIFIED; BF013/BF014/BF015/AF001-AF004 remain CLOSED |
| Current Sprint | BF016 — Organization and Property Setup Data Foundation — IN PROGRESS / OPEN |
| Next Internal Unit | None selected |
| Next Selected Sprint | No next sprint selected |
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
- ADMIN_EXPERIENCE_ARCHITECTURE.md
- LISTING_DOMAIN_ARCHITECTURE.md
- LISTING_PHYSICAL_DATA_MODEL.md
- PROPERTY_PROFILE_ARCHITECTURE.md
- PROPERTY_CATALOG_ARCHITECTURE.md

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
| Admin Experience, Marketplace Visibility, and Administrative Scope | ADMIN_EXPERIENCE_ARCHITECTURE.md |
| Listing Domain | LISTING_DOMAIN_ARCHITECTURE.md |
| Listing Physical Data Model | LISTING_PHYSICAL_DATA_MODEL.md |
| Rich Property Profile | PROPERTY_PROFILE_ARCHITECTURE.md |
| Property Catalog and Data Governance | PROPERTY_CATALOG_ARCHITECTURE.md |
| Canonical Seed Strategy | docs/database/SEED_DATA.md |
| Selected BF014 Sprint and Locked Baseline Matrices | [BF014 contract](sprints/BF014-canonical-property-catalog-foundation.md) |
| AF004 Property Administration UI Architecture & Route Contract | [AF004 contract](sprints/AF004-property-administration-ui.md) |
| BF014 Internal Unit BF014.1 Implementation Record | [BF014.1 schema and integrity](sprints/BF014.1-database-schema-and-integrity-constraints.md) |
| BF014 Internal Unit BF014.2 Implementation Record | [BF014.2 repositories and read models](sprints/BF014.2-repositories-and-domain-read-models.md) |

---

# Module Registry

| ID | Module | Status |
|----|-------------------------|------------|
| M00 | Reference Data | Legacy planning placeholders; canonical Property schema and repositories implemented through BF014.2; baseline seeds and Services not implemented |
| M01 | Core | Organization, Franchise, Partner Agency, User, Authentication, Position, Permission, and Authorization scope implemented (BF006-BF012) |
| M02 | CRM | Planned |
| M03 | Property Engine | BF013, BF014, and BF015 closed; Rich Property Profile beyond the BF015 bridge and Listing remain approved/not implemented |
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
| Current Phase | BF016 IN PROGRESS / OPEN; BF016.2/BF016.3/BF016.4 IMPLEMENTED AND VERIFIED; BF013/BF014/BF015/AF001-AF004 remain CLOSED |
| Current Sprint | BF016 — Organization and Property Setup Data Foundation — IN PROGRESS / OPEN |
| Current Module | M03 - Property Administration UI implemented through AF004; BF013/BF014/BF015 contracts composed |
| Current Feature | BF016.4 Property Administrative Details Verification and Formal Acceptance — IMPLEMENTED AND VERIFIED / READY |
| Current Database Contract | Repository migrations 001–035 verified in disposable MariaDB; migrations 034 and 035 remain unapplied to directors_resale_platform |
| Current Database Module | Core plus BF013 Property/Owner/Ownership/Global Identity foundation |
| Current Status | BF016.2/BF016.3/BF016.4 IMPLEMENTED AND VERIFIED; BF016 parent OPEN; next internal unit not selected |
| Current Milestone | Milestone 2 - Core Business |
| Next Internal Unit | None selected |
| Next Selected Sprint | No next sprint selected |
| Target Release | Not defined by the current canonical roadmap |

---

# Database Registry

---

# M00 - Reference Data

The entries below are legacy planning placeholders. They are not canonical Property table contracts. In particular, `countries`, `states`, `cities`, and `districts` are superseded for Property architecture by one hierarchical `geographic_locations` concept; `property_types` is superseded by Unit Types belonging to Property Categories. Canonical Property reference data is defined by `docs/architecture/PROPERTY_CATALOG_ARCHITECTURE.md` and is **APPROVED**, partially implemented through BF014.1 schema, BF014.2 repositories/read models, and BF014.3 services.

| ID | Table | Status |
|-----|------------------------|-----------|
| DB001 | countries | Legacy placeholder; superseded for Property by `geographic_locations` |
| DB002 | languages | Planned |
| DB003 | currencies | Planned |
| DB004 | currency_rates | Planned |
| DB005 | states | Legacy placeholder; superseded for Property by `geographic_locations` |
| DB006 | cities | Legacy placeholder; superseded for Property by `geographic_locations` |
| DB007 | districts | Legacy placeholder; superseded for Property by `geographic_locations` |
| DB008 | postal_codes | Planned |
| DB009 | property_types | Legacy placeholder; superseded by canonical `unit_types` |
| DB010 | property_categories | Legacy placeholder; canonical table implemented through BF014.1 |
| DB011 | finishing_types | Planned |
| DB012 | delivery_statuses | Planned |
| DB013 | ownership_types | Planned |
| DB014 | amenities | Planned |
| DB015 | amenity_categories | Planned |

---

# M01 - Core

| ID | Table | Status |
|-----|-------------------------------|-----------|
| DB101 | organizations | Implemented (BF006-BF008 staged schema) |
| DB102 | organization_settings | Planned |
| DB103 | positions | Implemented (BF011) |
| DB104 | users | Implemented (BF009-BF011 staged schema) |
| DB105 | user_profiles | Planned |
| DB106 | permissions | Implemented (BF012 controlled catalog) |
| DB107 | user_permissions | Deferred; direct User permissions not approved |
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

BF013 implemented the limited Property & Ownership foundation. Property Catalog architecture is approved and partially implemented through BF014.2: 13 tables in migrations 018-030 plus six repositories and read models; baseline seeds and Services remain unimplemented. Rich Property Profile remains APPROVED / NOT IMPLEMENTED.

Canonical future Property reference/profile concepts are `geographic_locations`, `property_categories`, `unit_types`, versioned Unit Type configurations, `measurement_definitions`, `attribute_definitions` and options, `developers`, `projects`, and `project_phases`. See `docs/architecture/PROPERTY_PROFILE_ARCHITECTURE.md`, `docs/architecture/PROPERTY_CATALOG_ARCHITECTURE.md`, and `docs/architecture/LISTING_PHYSICAL_DATA_MODEL.md`.

The following names are retained only as legacy planning placeholders, not approved SQL table contracts:

| ID | Legacy Planned Record | Status |
|-----|-----------------------------------|-----------|
| DB301 | developers | Canonical table implemented through BF014.1; no baseline data |
| DB302 | developer_projects | Legacy placeholder; canonical concept is `projects` |
| DB303 | compounds | Legacy placeholder; canonical concept is `projects` |
| DB304 | compound_phases | Legacy placeholder; canonical concept is `project_phases` |
| DB305 | properties | Legacy placeholder; BF013 uses `organization_properties` |
| DB306 | property_ownerships | Legacy placeholder; BF013 uses `ownerships` and `ownership_parties` |
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

Analytics is planned to use reporting tables, SQL Views, Materialized Views, and aggregated datasets.

No Analytics or reporting implementation exists yet.

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
| ADR-002 | Property / Ownership / Listing Separation | ✅ Approved |
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
| API001 | Authentication | /auth | Implemented (BF010; AF001 adds safe `/auth/context`) |
| API002 | Organizations | /organizations | Implemented (BF006) |
| API021 | Franchises | /franchises | Implemented (BF007) |
| API022 | Partner Agencies | /partner-agencies | Implemented (BF008) |
| API003 | Users | /users | Implemented (BF009; BF012-authorized) |
| API004 | Permissions | /permissions | Implemented (BF012 controlled catalog) |
| API023 | Positions | /positions | Implemented (BF011; BF012-authorized) |
| API005 | CRM | /people | Planned |
| API006 | Leads | /leads | Planned |
| API007 | Requirements | /requirements | Planned |
| API008 | Organization Properties | /organization-properties | BF013 foundation implemented; rich profile not implemented |
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
| F001 | Authentication | Core | Implemented (BF010) |
| F002 | Organization and Franchise Management | Core | Implemented (BF006-BF007) |
| F026 | Partner Agency Management | Core | Implemented (BF008) |
| F003 | User Management | Core | Implemented (BF009) |
| F004 | Dynamic Positions | Core | Implemented (BF011) |
| F027 | Permissions & Authorization | Core | Implemented (BF012) |
| F028 | Admin UI Foundation | Core | Implemented (AF001) |
| F005 | CRM | CRM | Planned |
| F006 | Lead Management | CRM | Planned |
| F007 | Requirement Management | CRM | Planned |
| F008 | Organization Property Foundation | Property | Implemented (BF013); rich profile/admin workflow not implemented |
| F009 | Listing Management | Property | Architecture approved; implementation planned |
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
| Sprint 1 | Core Database Module | Legacy roadmap; superseded by BF milestone sequence |
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
| BF013 | Property & Ownership Foundation | Implemented and verified; closed |
| BF014 | [Canonical Property Catalog Foundation](sprints/BF014-canonical-property-catalog-foundation.md) | Implemented and verified; closed |
| BF015 | [Property Profile Persistence Bridge](sprints/BF015-property-profile-persistence-bridge.md) | Implemented and verified; closed |
| AF004 | [Property Administration UI](sprints/AF004-property-administration-ui.md) | IMPLEMENTED AND VERIFIED / CLOSED |
| BF016 | [Organization and Property Setup Data Foundation](sprints/BF016-organization-and-property-setup-data-foundation.md) | IN PROGRESS / OPEN; BF016.5–BF016.6 pending |
| BF016.2 | [Franchise Basic Profile Implementation](sprints/BF016-organization-and-property-setup-data-foundation.md#bf0162--franchise-basic-profile-implementation) | IMPLEMENTED AND VERIFIED |
| BF016.3 | [Property Administrative Details Implementation](sprints/BF016-organization-and-property-setup-data-foundation.md#bf0163--property-administrative-details-implementation) | IMPLEMENTED AND VERIFIED |
| BF016.4 | [Property Administrative Details Verification and Formal Acceptance](sprints/BF016-organization-and-property-setup-data-foundation.md#bf0164--property-administrative-details-verification-and-formal-acceptance) | IMPLEMENTED AND VERIFIED / READY |

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
| Current Phase | BF016 IN PROGRESS / OPEN; BF016.2/BF016.3/BF016.4 IMPLEMENTED AND VERIFIED; BF013/BF014/BF015/AF001-AF004 remain CLOSED |
| Current Module | M03 - Property Administration UI implemented through AF004; BF013/BF014/BF015 contracts composed |
| Current Documents | [BF016 implementation record](sprints/BF016-organization-and-property-setup-data-foundation.md); PROPERTY_PROFILE_ARCHITECTURE.md; PROPERTY_CATALOG_ARCHITECTURE.md; SEED_DATA.md |
| Current Database Contract | Repository migrations 001–035 verified in disposable MariaDB; migrations 034 and 035 remain unapplied to directors_resale_platform |
| Current ADR | ADR-002 Property / Ownership / Listing Separation |
| Current Milestone | Milestone 2 - Core Business; BF013 closed |
| Current Release Target | Not defined by the current canonical roadmap |

---

# Progress Dashboard

| Area | Progress |
|------|----------|
| Product Discovery | ████████████████████ 100% |
| Business Analysis | ████████████████████ 100% |
| Architecture | ████████████████████ 100% |
| Documentation | ██████████████████░░ 90% |
| Database Design | ███░░░░░░░░░░░░░░░░░ 10% |
| Backend Development | BF001-BF013 foundation and Core business/security scope implemented |
| Frontend Development | AF001 Admin UI Foundation implemented; feature CRUD UI planned |
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
| Current Sprint | BF016 — Organization and Property Setup Data Foundation — IN PROGRESS / OPEN |
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

## Immediate Next Development Target

BF014 — [Canonical Property Catalog Foundation](sprints/BF014-canonical-property-catalog-foundation.md), BF015 — [Property Profile Persistence Bridge](sprints/BF015-property-profile-persistence-bridge.md), and AF004 — [Property Administration UI](sprints/AF004-property-administration-ui.md) — are **IMPLEMENTED AND VERIFIED / CLOSED**. AF004 composes existing Property, Profile, and Ownership contracts but does not implement Rich Property Profile beyond BF015, media/private-document persistence, completeness truth, Listing, Marketplace, Requests, Deals, Commissions, or transfers.

BF001-BF015 and AF001-AF003 are implemented and verified. BF015 is closed and no next workstream is currently selected. Property Catalog/Data Governance is implemented through BF014, and Rich Property Profile beyond the BF015 bridge, Listing Domain, and the broader Listing implementation remain approved but not implemented.

## Sprint 1

Historical roadmap sequence, retained for context and superseded by the BF milestone sequence:

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

## BF016 — Organization and Property Setup Data Foundation

**BF016.2 — Franchise Basic Profile Implementation and BF016.3 — Property Administrative Details: IMPLEMENTED AND VERIFIED**, recorded 2026-10-05. The BF016 parent is **IN PROGRESS / OPEN** until BF016.5–BF016.6 are completed. No next internal unit is selected by this handoff. BF013, BF014, BF015 and AF004 remain closed.

Focused real-MariaDB acceptance and Operational Franchise Admin Activation passed. All 51 required BF016/BF013/BF014/BF015 test/regression entry points passed, including integrated suites and additional service/concurrency tests; all 147 process cleanup checks passed. PHP syntax and frontend typecheck, lint and build passed. No real development database schema/data was modified.

Organization logo/media, persisted Property images, documents/contracts, galleries/videos, Listing, Marketplace, Requests, Deals, Commissions, completeness and Listing Ready remain deferred. Property setup is not Listing readiness.

Migration synchronization is planned only for `directors_resale_platform` on `127.0.0.1:3306`: migrations 034 then 035 only, in repository order, no canonical seed impact, verified backup and explicit user approval required, all test/acceptance/demo/fixture data excluded, and preflight/post-apply checks required. Migrations 034 and 035 remain unapplied to the real database; no synchronization was executed.

The 14 mojibake sequences are pre-existing in two BF014 fixtures (eight in `BF014RepositoryDatabaseAcceptanceTest.php`, six in `BF014SchemaAcceptanceTest.php`), match `HEAD`, and were not changed by this work. No invalid UTF-8, replacement characters or new mojibake were introduced.

Canonical implementation evidence and synchronization preflight/backup/post-apply requirements: [BF016 implementation record](sprints/BF016-organization-and-property-setup-data-foundation.md). Implementation and its original handoff were committed and pushed on codex-review in c06aa786; official merge remains human-owner controlled.

### Historical BF016.2/BF016.3 review evidence — 2026-10-05

Company profile now supports existing Organization name, System Franchise selection, private own-Franchise editing, optional street address and protected five-level canonical geography with legitimate skips, safe empty reads and ancestry hydration. Property Data/review now expose an immutable persisted `PROP-<ULID>` code, canonical address, optional positive DECIMAL(18,4) asking price with explicit EGP/USD/SAR/AED currency, and a labelled generic image placeholder without storage. Existing BF015 typed-value patches, atomic revisions and explicit conflict retry are preserved. Authorization remains backend-owned; no permission codes or Listing side effects were added.

Verification: 51/51 PHP matrix entry points passed (two focused BF016 acceptances, Operational Franchise Admin Activation, three BF013, 28 BF014 entry/integrated suites, 12 additional BF014 service/concurrency tests, five BF015). All 147 process cleanup checks passed. PHP syntax passed for 190 files. Frontend typecheck, lint, the BF016 canonical-geography/empty-profile/hydration/error contract acceptance and production build passed. The build completed final trace collection in an identical isolated source copy with shared installed dependencies because the running development server held the original `.next` trace; the development server was left running. Strict UTF-8 and diff checks passed. The 14 pre-existing mojibake sequences in the two BF014 fixtures match HEAD and were not changed; no new mojibake or replacement characters were introduced.

MariaDB remained available at 127.0.0.1:3306; configuration targeted exactly directors_resale_platform. Read-only before/after schema, row-count and data fingerprints matched for all 30 real tables. All newly created disposable databases were removed; the pre-existing directors_resale_platform_e1_test_9740 remained untouched. Repository migrations 001–035 are verified by exact ordered filename equality. Migrations 034 and 035 remain unapplied to the real database. Future synchronization requires a reviewed preflight, verified backup, explicit user approval, 034 then 035 only, no canonical seed impact, exclusion of all test/acceptance/demo/fixture data, and post-apply schema/index/constraint, application and unchanged-business-data checks. No real schema/data operation was performed.

BF016.2 and BF016.3 are IMPLEMENTED AND VERIFIED / READY for review on codex-review. BF016 parent remains OPEN; BF016.4–BF016.6 remain OPEN / PENDING and no next unit is selected. Full media/logo/image persistence, galleries/videos, private documents/contracts, Listing, Marketplace, Requests, Deals, Commissions, completeness and Listing Ready remain deferred. Official-branch merge remains controlled by the human owner.

## BF016.4 — Property Administrative Details Verification and Formal Acceptance

**IMPLEMENTED AND VERIFIED / READY**, 2026-10-05. User-authorized reconciliation keeps BF016.3 as implementation (commit c06aa786) and BF016.4 as verification, proven-gap fixing, regression evidence and formal acceptance. No gap was proven; no application code, tests, fields, permissions or migrations were changed or duplicated.

All 51 PHP matrix entries passed: BF016.2/BF016.3 focused acceptances, Operational Franchise Admin Activation, all BF013/BF014/BF015 entry points and integrated suites, and additional BF014 service/concurrency tests. PHP syntax passed for 190 files. Frontend typecheck, lint, existing contract acceptance and production build including final trace passed; the isolated build copy matched all 118 source hashes. Strict UTF-8, mojibake/replacement scans and diff checks passed; the 14 pre-existing BF014 fixture sequences remain unchanged.

All 146 process cleanup checks passed with no new disposable database residue. All 30 real-table schema/data/row-count fingerprints matched before/after. MariaDB 10.4.32 was available at 127.0.0.1:3306, with .env targeting exactly directors_resale_platform. No real schema/data changes or fixture imports occurred; migrations 034/035 remain unapplied there. The pre-existing e1_test_9740 database remained untouched. Verified backup, preflight, explicit approval and post-apply checks remain mandatory for future synchronization.

BF016.5 remains OPEN for the minimum one-primary-property-image foundation; the generic UI placeholder is not a persisted image. BF016.6 remains OPEN for final closure; BF016 parent remains OPEN. Media, galleries/video, Franchise logo, documents, Listing, Marketplace, Requests, Deals, Commissions, completeness and Listing Ready remain deferred. No next unit is selected. [Exact verification and acceptance evidence](sprints/BF016-organization-and-property-setup-data-foundation.md#bf0164--verification-evidence).
