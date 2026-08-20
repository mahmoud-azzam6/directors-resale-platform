# Directors Resale Platform

# MASTER_INDEX.md

Version: 2.0

Status: Active

Last Updated: 2026-08-20

---

# Project Information

| Item | Value |
|------|-------|
| Commercial Name | Directors Resale Hub |
| Internal Project Name | Directors Resale Platform |
| Project Type | Enterprise SaaS Platform |
| Industry | Real Estate Resale |
| Architecture Style | Modular + Event Driven |
| Current Phase | Admin Frontend Foundation |
| Current Sprint | AF001 - Admin UI Foundation (Implemented) |
| Next Selected Milestone | Not selected |
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

---

# Module Registry

| ID | Module | Status |
|----|-------------------------|------------|
| M00 | Reference Data | Planned |
| M01 | Core | Organization, Franchise, Partner Agency, User, Authentication, Position, Permission, and Authorization scope implemented (BF006-BF012) |
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
| Current Phase | Admin Frontend Foundation |
| Current Sprint | AF001 - Admin UI Foundation (Implemented) |
| Current Module | M01 - Core |
| Current Feature | Admin UI Foundation over implemented BF006-BF012 Core APIs |
| Current Database Contract | BF006-BF012 staged Core schema |
| Current Database Module | Core |
| Current Status | AF001 implemented; Admin Experience Architecture approved |
| Current Milestone | Milestone 2 - Core Business |
| Next Selected Milestone | Not selected |
| Target Release | Not defined by the current canonical roadmap |

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
| Current Phase | AF001 implemented; Admin Experience Architecture approved |
| Current Module | M01 - Core |
| Current Document | ADMIN_EXPERIENCE_ARCHITECTURE.md |
| Current Database Contract | BF006-BF012 staged Core schema |
| Current ADR | ADR-001 Organization Hierarchy; Admin Experience Architecture |
| Current Milestone | AF001 - Admin UI Foundation (Implemented) |
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
| Backend Development | BF001-BF012 foundation and Core business/security scope implemented |
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
| Current Sprint | AF001 - Admin UI Foundation (Implemented) |
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

No implementation milestone is selected.

AF002 has an approved direction of Network Administration UI, centered initially on System-authorized
network administration. Its exact sprint contract is not approved, AF002 is not implemented, and no
future Listing, Request, Reporting, Commission, Team, or provisioning workflow is selected for implementation.

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
