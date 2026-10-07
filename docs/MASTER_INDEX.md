# Directors Resale Platform

# MASTER_INDEX.md

Version: 2.0

Status: Active

Last Updated: 2026-10-07

---

# Project Information

| Item | Value |
|------|-------|
| Commercial Name | Directors Resale Hub |
| Internal Project Name | Directors Resale Platform |
| Project Type | Enterprise SaaS Platform |
| Industry | Real Estate Resale |
| Architecture Style | Modular + Event Driven |
| Current Phase | BF018 IMPLEMENTED AND VERIFIED / READY FOR OWNER REVIEW; BF013-BF017 and AF001-AF004 remain CLOSED |
| Current Sprint | BF018 - Published Listings Catalog & Request Flow - verified; commit/push approval pending |
| Next Internal Unit | None selected; Partner onboarding deferred |
| Next Selected Sprint | None; BF018 owner review pending |
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
| Current Phase | BF017 bounded Listing MVP CLOSED; BF016 and BF016.2–BF016.6 IMPLEMENTED AND VERIFIED / CLOSED; BF013/BF014/BF015/AF001-AF004 remain CLOSED |
| Current Sprint | BF017 — Listing MVP Foundation — IMPLEMENTED AND VERIFIED / CLOSED |
| Current Module | M03 - Property Administration UI implemented through AF004; BF013/BF014/BF015 contracts composed |
| Current Feature | BF016.5 Minimal Primary Property Image Foundation — IMPLEMENTED AND VERIFIED / READY |
| Current Database Contract | Repository migrations 001–037 verified in disposable MariaDB; real 034–036 already applied, 037 remains unapplied |
| Current Database Module | Core plus BF013 Property/Owner/Ownership/Global Identity foundation |
| Current Status | BF016.2–BF016.5 IMPLEMENTED AND VERIFIED; BF016 parent OPEN; next internal unit not selected |
| Current Milestone | Milestone 2 - Core Business |
| Next Internal Unit | None selected; Partner onboarding deferred |
| Next Selected Sprint | BF017 — Listing MVP Foundation |
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
| BF016 | [Organization and Property Setup Data Foundation](sprints/BF016-organization-and-property-setup-data-foundation.md) | IN PROGRESS / OPEN; BF016.6 pending |
| BF016.2 | [Franchise Basic Profile Implementation](sprints/BF016-organization-and-property-setup-data-foundation.md#bf0162--franchise-basic-profile-implementation) | IMPLEMENTED AND VERIFIED |
| BF016.3 | [Property Administrative Details Implementation](sprints/BF016-organization-and-property-setup-data-foundation.md#bf0163--property-administrative-details-implementation) | IMPLEMENTED AND VERIFIED |
| BF016.4 | [Property Administrative Details Verification and Formal Acceptance](sprints/BF016-organization-and-property-setup-data-foundation.md#bf0164--property-administrative-details-verification-and-formal-acceptance) | IMPLEMENTED AND VERIFIED / READY |
| BF016.5 | [Minimal Primary Property Image Foundation](sprints/BF016-organization-and-property-setup-data-foundation.md#bf0165--minimal-primary-property-image-foundation) | IMPLEMENTED AND VERIFIED / READY |

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
| Current Phase | BF017 bounded Listing MVP CLOSED; BF016 and BF016.2–BF016.6 IMPLEMENTED AND VERIFIED / CLOSED; BF013/BF014/BF015/AF001-AF004 remain CLOSED |
| Current Module | M03 - Property Administration UI implemented through AF004; BF013/BF014/BF015 contracts composed |
| Current Documents | [BF016 implementation record](sprints/BF016-organization-and-property-setup-data-foundation.md); PROPERTY_PROFILE_ARCHITECTURE.md; PROPERTY_CATALOG_ARCHITECTURE.md; SEED_DATA.md |
| Current Database Contract | Repository migrations 001–037 verified in disposable MariaDB; real 034–036 already applied, 037 remains unapplied |
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
| Current Sprint | BF017 — Listing MVP Foundation — IMPLEMENTED AND VERIFIED / CLOSED |
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

BF014 — [Canonical Property Catalog Foundation](sprints/BF014-canonical-property-catalog-foundation.md), BF015 — [Property Profile Persistence Bridge](sprints/BF015-property-profile-persistence-bridge.md), and AF004 — [Property Administration UI](sprints/AF004-property-administration-ui.md) — are **IMPLEMENTED AND VERIFIED / CLOSED**. AF004 composes existing Property, Profile, and Ownership contracts but does not implement Rich Property Profile beyond BF015, full media/private-document persistence, completeness truth, Listing, Marketplace, Requests, Deals, Commissions, or transfers.

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

## Historical BF016 — Organization and Property Setup Data Foundation

**BF016.2 — Franchise Basic Profile Implementation and BF016.3 — Property Administrative Details: IMPLEMENTED AND VERIFIED**, recorded 2026-10-05. The BF016 parent is **IN PROGRESS / OPEN** until BF016.6 are completed. No next internal unit is selected by this handoff. BF013, BF014, BF015 and AF004 remain closed.

Focused real-MariaDB acceptance and Operational Franchise Admin Activation passed. All 51 required BF016/BF013/BF014/BF015 test/regression entry points passed, including integrated suites and additional service/concurrency tests; all 147 process cleanup checks passed. PHP syntax and frontend typecheck, lint and build passed. No real development database schema/data was modified.

Organization logo/media, full media galleries, documents/contracts, galleries/videos, Listing, Marketplace, Requests, Deals, Commissions, completeness and Listing Ready remain deferred. Property setup is not Listing readiness.

Migration synchronization is planned only for `directors_resale_platform` on `127.0.0.1:3306`: migrations 034 then 035 only, in repository order, no canonical seed impact, verified backup and explicit user approval required, all test/acceptance/demo/fixture data excluded, and preflight/post-apply checks required. Migrations 034 and 035 remain unapplied to the real database; no synchronization was executed.

The 14 mojibake sequences are pre-existing in two BF014 fixtures (eight in `BF014RepositoryDatabaseAcceptanceTest.php`, six in `BF014SchemaAcceptanceTest.php`), match `HEAD`, and were not changed by this work. No invalid UTF-8, replacement characters or new mojibake were introduced.

Canonical implementation evidence and synchronization preflight/backup/post-apply requirements: [BF016 implementation record](sprints/BF016-organization-and-property-setup-data-foundation.md). Implementation and its original handoff were committed and pushed on codex-review in c06aa786; official merge remains human-owner controlled.

### Historical BF016.2/BF016.3 review evidence — 2026-10-05

Company profile now supports existing Organization name, System Franchise selection, private own-Franchise editing, optional street address and protected five-level canonical geography with legitimate skips, safe empty reads and ancestry hydration. Property Data/review now expose an immutable persisted `PROP-<ULID>` code, canonical address, optional positive DECIMAL(18,4) asking price with explicit EGP/USD/SAR/AED currency, and a labelled generic image placeholder without storage. Existing BF015 typed-value patches, atomic revisions and explicit conflict retry are preserved. Authorization remains backend-owned; no permission codes or Listing side effects were added.

Verification: 51/51 PHP matrix entry points passed (two focused BF016 acceptances, Operational Franchise Admin Activation, three BF013, 28 BF014 entry/integrated suites, 12 additional BF014 service/concurrency tests, five BF015). All 147 process cleanup checks passed. PHP syntax passed for 190 files. Frontend typecheck, lint, the BF016 canonical-geography/empty-profile/hydration/error contract acceptance and production build passed. The build completed final trace collection in an identical isolated source copy with shared installed dependencies because the running development server held the original `.next` trace; the development server was left running. Strict UTF-8 and diff checks passed. The 14 pre-existing mojibake sequences in the two BF014 fixtures match HEAD and were not changed; no new mojibake or replacement characters were introduced.

MariaDB remained available at 127.0.0.1:3306; configuration targeted exactly directors_resale_platform. Read-only before/after schema, row-count and data fingerprints matched for all 30 real tables. All newly created disposable databases were removed; the pre-existing directors_resale_platform_e1_test_9740 remained untouched. Repository migrations 001–035 are verified by exact ordered filename equality. Migrations 034 and 035 remain unapplied to the real database. Future synchronization requires a reviewed preflight, verified backup, explicit user approval, 034 then 035 only, no canonical seed impact, exclusion of all test/acceptance/demo/fixture data, and post-apply schema/index/constraint, application and unchanged-business-data checks. No real schema/data operation was performed.

BF016.2 and BF016.3 are IMPLEMENTED AND VERIFIED / READY for review on codex-review. BF016 parent remains OPEN; BF016.4–BF016.6 remain OPEN / PENDING and no next unit is selected. Full media/logo/image persistence, galleries/videos, private documents/contracts, Listing, Marketplace, Requests, Deals, Commissions, completeness and Listing Ready remain deferred. Official-branch merge remains controlled by the human owner.

## Historical BF016.4 — Property Administrative Details Verification and Formal Acceptance

**IMPLEMENTED AND VERIFIED / READY**, 2026-10-05. User-authorized reconciliation keeps BF016.3 as implementation (commit c06aa786) and BF016.4 as verification, proven-gap fixing, regression evidence and formal acceptance. No gap was proven; no application code, tests, fields, permissions or migrations were changed or duplicated.

All 51 PHP matrix entries passed: BF016.2/BF016.3 focused acceptances, Operational Franchise Admin Activation, all BF013/BF014/BF015 entry points and integrated suites, and additional BF014 service/concurrency tests. PHP syntax passed for 190 files. Frontend typecheck, lint, existing contract acceptance and production build including final trace passed; the isolated build copy matched all 118 source hashes. Strict UTF-8, mojibake/replacement scans and diff checks passed; the 14 pre-existing BF014 fixture sequences remain unchanged.

All 146 process cleanup checks passed with no new disposable database residue. All 30 real-table schema/data/row-count fingerprints matched before/after. MariaDB 10.4.32 was available at 127.0.0.1:3306, with .env targeting exactly directors_resale_platform. No real schema/data changes or fixture imports occurred; migrations 034/035 remain unapplied there. The pre-existing e1_test_9740 database remained untouched. Verified backup, preflight, explicit approval and post-apply checks remain mandatory for future synchronization.

BF016.5 remains OPEN for the minimum one-primary-property-image foundation; the generic UI placeholder is not a persisted image. BF016.6 remains OPEN for final closure; BF016 parent remains OPEN. Media, galleries/video, Franchise logo, documents, Listing, Marketplace, Requests, Deals, Commissions, completeness and Listing Ready remain deferred. No next unit is selected. [Exact verification and acceptance evidence](sprints/BF016-organization-and-property-setup-data-foundation.md#bf0164--verification-evidence).

## Historical BF016.5 — Minimal Primary Property Image Foundation

Registry addition: migration `036_create_property_primary_images_table.sql` implements dedicated `property_primary_images` metadata (one private image per Property); the broader `property_media` library remains planned. Protected GET/POST/DELETE `/organization-properties/{id}/primary-image` and GET `/organization-properties/{id}/primary-image/content` reuse existing Property permissions and PRIVATE_ORGANIZATION.

**IMPLEMENTED AND VERIFIED / READY**, 2026-10-05. One private primary-image reference per Property now supports upload, replacement, removal and protected WebP delivery. The Property Data editor and read-only review hydrate this dedicated payload without changing Property/Profile revisions, code, address, price, typed values or Ownership. Existing properties.view/properties.manage and PRIVATE_ORGANIZATION remain authoritative; parent Franchise access does not grant child Partner image access.

CLI and Apache PHP loaded GD/WebP from C:\xampp\php\php.ini. Focused acceptance passed, including actual Apache multipart upload/binary delivery, EXIF/GPS stripping and orientation, invalid type/content/size/reference rejection, atomic persistence-failure cleanup and private scope. The full 52-entry PHP matrix passed (three BF016 focus tests including BF016.4 reuse of BF016.3, Operational Activation, three BF013, 28 BF014, 12 additional BF014 service/concurrency, five BF015); all 147 process cleanup checks passed. PHP syntax passed for 193 files. Frontend typecheck, lint, expanded image/proxy contract acceptance and production build including final trace passed; the isolated build copy matched all 122 source hashes. UTF-8 and diff checks passed; the 14 pre-existing BF014 fixture mojibake markers remain unchanged.

Migrations 001–036 are required by exact ordered filename equality; migration 036 alone adds the dedicated primary-image metadata table and has no seed impact. Migrations 034/035/036 remain unapplied to directors_resale_platform. Read-only schema/row-count/data fingerprints matched across all 30 real tables. Disposable databases, processed/source files and temporary Apache acceptance endpoints were removed; pre-existing e1_test_9740 stayed untouched. Future real synchronization requires reviewed preflight, verified backup, exact ordered approval and post-apply checks; no test/acceptance/demo/fixture data or files may be imported.

BF016.6 and BF016 parent remain OPEN. Galleries/multiple images, video, documents, Franchise logo, full media processing/platform, Listing media management, Marketplace, Requests, Deals, Commissions, completeness and Listing Ready remain deferred. No next unit is selected. [Bounded contract and full evidence](sprints/BF016-organization-and-property-setup-data-foundation.md#bf0165--implementation-and-verification-evidence).

## BF016.6 — Final Acceptance and Closure

**BF016 parent and BF016.2, BF016.3, BF016.4, BF016.5, BF016.6: IMPLEMENTED AND VERIFIED / CLOSED**, 2026-10-05. This final record supersedes historical open/pending checkpoints below or above; their evidence is preserved. BF016.3 remains the administrative-details implementation and BF016.4 its verification/formal acceptance. No duplicate implementation or migration was created. No next internal unit is selected; official-branch merge remains human-owner controlled.

Final integrated real-MariaDB acceptance composes the existing Organization basic profile, Property administrative details, primary-image, BF013 ownership HTTP and BF015 Profile HTTP contracts. It covers canonical geography/address, persisted generated Property Code and duplicate display names, positive asking price/currency, preserved BF015 measurements/attributes and Ownership, private image upload/replacement/removal/validation/metadata-free WebP, Organization authorization/privacy and absence of future workflow/readiness side effects.

All 53 unique PHP matrix entry points passed: one final BF016 integrated runner, three BF016 focused acceptances (BF016.4 reuses BF016.3), Operational Franchise Admin Activation, three BF013, 28 BF014 entry/integrated suites, 12 additional BF014 service/concurrency tests and five BF015. All 153 nested/process disposable database cleanup checks passed. Temporary source/processed images and Apache acceptance endpoints were removed. Frontend typecheck, lint and contract tests passed. Production build completed final trace and 33 static pages in an isolated copy matching all 122 source hashes, leaving the running development server untouched. PHP syntax passed for 194 files. Strict UTF-8 and replacement-character validation passed; changed-file mojibake and git diff checks passed.

Encoding baseline: the unchanged historical BF014 sprint document contains 4,852 pre-existing scan matches; worktree and HEAD Git blob hashes both equal `02f651068658df167ca9f62d2a55e8eeca7f98ed`. Per explicit owner instruction these are baseline findings, not BF016 defects; the document was not rewritten. The known 14 pre-existing fixture sequences remain unchanged (eight in BF014RepositoryDatabaseAcceptanceTest.php, six in BF014SchemaAcceptanceTest.php). No new mojibake or replacement characters were introduced in BF016 or changed files.

MariaDB 10.4.32 was available at 127.0.0.1:3306 on DESKTOP-5EI3DP1; .env targets exactly directors_resale_platform. All 30 real-table schema, row-count and data fingerprints remained unchanged. Migrations 034, 035 and 036 remain unapplied. No test/acceptance/demo/fixture data or images were imported. The pre-existing directors_resale_platform_e1_test_9740 was left untouched. Schema synchronization remains a separate handoff requiring renewed preflight, verified backup and explicit owner approval; there is no canonical seed impact.

Deferred: full media system, galleries/multiple images, videos, Franchise logo/media, private documents/contracts, Listing, Marketplace, Requests, Deals, Commissions, completeness, Listing Ready and other future business workflows. Property setup is not Listing readiness.

[Complete individual matrix and schema synchronization handoff](sprints/BF016-organization-and-property-setup-data-foundation.md#bf0166--final-acceptance-and-closure).

## BF017 — Listing MVP Foundation — 2026-10-06

**IMPLEMENTED AND VERIFIED / READY.** Owner-approved bounded sprint: [BF017 contract](sprints/BF017-listing-mvp-foundation.md). New canonical listings.view/listings.manage; direct draft -> published -> archived; Property remains the source of truth for identity, address, initial price/currency, primary image and current Ownership. Listing routes use authorized own/direct-child hierarchy and a limited presentation projection; existing PRIVATE_ORGANIZATION routes and Owner privacy remain unchanged. Partner Agency full/operational onboarding remains deferred because existing Organization context is sufficient. BF016 and BF016.2–BF016.6 stay CLOSED.

Repository-only migration 037_create_listings_table_and_permissions.sql adds Listing lifecycle/revision storage, active-Property uniqueness, composite owning-Property foreign key, lifecycle/revision checks and exactly two canonical permissions. No Position assignments or business/demo data. No real database migration or seed is authorized. Internal/Owner approval, moderation, broad Listing Ready/completeness, Marketplace/search, Requests, Deals, Commissions, payments, notifications, galleries and other future workflows remain deferred. Actual environment synchronization requires separate preflight, verified backup and explicit approval.

### BF017 final verification evidence

All 55 unique PHP matrix entry points passed, including focused BF017, existing Franchise Admin hierarchy security, Operational Activation and the complete established BF013–BF016 entry/integrated/service/concurrency matrix. Latest BF017 acceptance passed 103 assertions plus guarded database/file cleanup checks. All 55 matrix entries passed post-process database inventory and temporary public HTTP-file cleanup; internal suites also execute their existing cleanup. The pre-existing e1_test_9740 database remained untouched. No new disposable database or uploaded file remained.

PHP syntax: 199 files PASS. Frontend typecheck, lint, BF016 contract suite and new BF017 component hydration/capability/error/API suite PASS. Production build PASS through final trace collection and 35 static pages in an isolated copy matching all 134 frontend source hashes; the running development server was left intact. Strict UTF-8 passed for all 480 repository text files; all 38 changed-file mojibake/replacement and git diff checks PASS. Historical BF014 document retains HEAD blob 02f651068658df167ca9f62d2a55e8eeca7f98ed and its 4,852 known baseline matches; the two BF014 fixtures retain exactly eight plus six baseline sequences, with no new findings.

MariaDB is available at 127.0.0.1:3306; .env resolves exactly directors_resale_platform via C:\xampp\php\php.exe and C:\xampp\php\php.ini. Before/after all 32 real table definitions, row counts and data fingerprints match. Aggregate schema SHA256: 8d19ef5feaed9c7205d404aeec18cde6cac8402e1eab84065cde2d60ad537b45. Aggregate data/row-count SHA256: 2f77070383a15a16719b4153779299e45ab15cc14eb5303fd6d71964a92f0311. No real login/token insertion, Position grant, seed, migration, business/demo/test data or image change occurred. Migration 037 remains unapplied, and neither new Listing capability is granted to real Positions by this task.

Two proven stale regression expectations were reconciled: BF016 no-side-effect checks now require an empty Listing table while preserving deferred-domain table absence; BF014 excludes future domains but permits the six owner-approved Listing routes, whose exact registered surface is verified by BF017. Exact ordered migration manifests extend 001–036 to 001–037 without accepting arbitrary extras. Historical BF014 catalog count checks still require exactly 32 pre-Listing codes; focused BF017 requires exactly 34 total codes and the exact two active Listing capabilities. An initial new-test float/int strict comparison was corrected; latest acceptance and the complete final matrix pass. No security boundary was relaxed.

READY means bounded implementation/verification ready for Architect review. Actual demo use against the real database requires separate explicit schema synchronization and grant approval, then eligible existing Property data. No next implementation unit is selected; Partner onboarding remains deferred. BF016 stays CLOSED.

## BF018 - Published Listings Catalog & Request Flow - 2026-10-07

**IMPLEMENTED AND VERIFIED / READY FOR OWNER REVIEW. Commit/push approval pending.** [Bounded BF018 contract and complete verification matrix](sprints/BF018-published-listings-catalog-and-request-flow.md). This current entry supersedes earlier next-unit/deferred-Request statements only for the owner-approved expression-of-interest slice. BF016 and BF017 remain CLOSED; no next implementation unit is selected.

Authenticated catalog/details/private image delivery show only currently eligible published Listings in the existing authorized Organization hierarchy. Existing Property remains the presentation source. Three explicitly approved canonical capabilities: `published_listings.view`, `requests.create`, `requests.view`. Submission requires catalog view plus create, derives identity/references server-side and permits one submitted Request per user/Listing, with concurrent duplicate protection. Receipts are requester-only, including for System actors, and remain readable after archival subject to current scope. No anonymous, peer-Franchise or unrelated-System access is added; existing private Property/Owner/Ownership boundaries and BF017 administration remain unchanged.

Repository-only migration `038_create_listing_interest_requests_and_permissions.sql` adds `listing_interest_requests`, composite reference foreign keys, lookup/unique indexes, submitted-only check and the three canonical Permission rows. Additional unique reference indexes on existing `listings` and `users` support composite integrity. No Position assignments or business fixtures are included. Migration 038 remains UNAPPLIED to `127.0.0.1:3306/directors_resale_platform`; separate environment preflight, verified backup and explicit owner synchronization approval are required before actual use.

Final gate: all 56 PHP matrix entries PASS, including BF018 (119 focused assertions), BF017, BF013-BF016, Franchise hierarchy security, Operational Activation and service/concurrency/integrated suites. Disposable database and public-file cleanup PASS after every entry. PHP syntax 203 files PASS; frontend typecheck, lint and BF016/BF017/BF018 contracts PASS. Production build PASS through final trace generation and 39 generated pages, from an isolated copy matching 148 frontend source hashes; copied build output and dependency junction cleaned. UTF-8 and changed-file mojibake/replacement scans PASS; no new findings. Historical BF014 document retains HEAD blob `02f651068658df167ca9f62d2a55e8eeca7f98ed` and 4,852 baseline matches; the two fixtures retain exactly 8 + 6 known sequences unchanged. Exact migration manifests now require ordered 001-038; historical catalog assertions exclude only explicitly approved later codes, retaining exact BF014/BF017 counts. One stale BF014 permission-count assertion was proven and corrected before the complete successful rerun.

Real database remains unchanged across all 33 tables, schemas, row counts and data fingerprints. Schema SHA256: `e9d22d56629026c8074976d9ba7a36de00dac1aec1a15c8c9d76a8bdd9e6dfa9`. Data/row-count SHA256: `1d29c8505207501d51def46d901d76f0a18c3073f722d5f18d1f79b217174bf6`. No real login/token, Request, Listing, migration, seed, permission grant, business/demo change or test import occurred. The pre-existing `directors_resale_platform_e1_test_9740` database was excluded and untouched. All new acceptance fixtures were isolated and cleaned.

Deferred: anonymous/global Marketplace/search, Request administration/cancellation/review, Holds, Deals, negotiation, viewing, Contracts, Sale Approval, Commissions, payments, transfers, notifications, full media/galleries/videos/Franchise logos/private documents, completeness/Listing Ready, approval/moderation workflows and full Partner onboarding. Requests never reserve or mutate Listing/Property/Ownership/image data or create downstream business records. Before Git operations, owner approval of the concrete verified handoff is still required by the BF018 request.
