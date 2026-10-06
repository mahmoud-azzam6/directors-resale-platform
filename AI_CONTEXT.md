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

# Current Implementation State

BF001-BF013 and AF001-AF003 are implemented. The current application includes Organization, Franchise,
Partner Agency, User, Authentication, Dynamic Position, Permission/Authorization, the BF013 Property and
Ownership backend foundation, and Admin UI foundation functionality.

The implemented Organization hierarchy is System Organization -> Franchise -> Partner Agency. Users
belong to an Organization in that hierarchy and receive capabilities from dynamic Positions and
effective Permissions. Backend authorization remains authoritative and combines capability with
applicable Organization/resource scope. Position names are not authorization rules.

The BF006-BF013 database remains intentionally staged. Franchise and Partner Agency records reuse
`organizations`; Users, authentication tokens, Positions, Permissions, and Position-Permission
assignments use the staged tables introduced by their approved sprints. BF013 adds the implemented
Organization Property shell, Owner/Ownership history, and System-only Global Identity foundation.

# Approved Admin Experience Architecture

`docs/architecture/ADMIN_EXPERIENCE_ARCHITECTURE.md` is the approved source for the shared Admin
experience, provisioning direction, Global Marketplace Visibility, Administrative Scope, and scoped
reporting principles.

- The platform has one authenticated Admin Application.
- Experience is determined by Organization, Position, Permissions, and applicable resource scope.
- System-authorized administration provisions Franchises; permitted Franchise administration may
  provision Partner Agencies; Organization administrators manage Users only within authorized scope.
- The System controls the Permission capability catalog. Organization administrators cannot create
  unrestricted capabilities or escalate privileges.
- Future marketplace-eligible available Listings are globally visible to authenticated platform Users.
  This Global Marketplace Visibility does not grant Administrative Scope over another Organization's
  Listings, Users, reports, commissions, or operations.
- Cross-Organization Requests against marketplace-visible Listings are approved in principle, subject
  to a future Request workflow and business rules.
- Reporting is scope-sensitive. Exact Listing Permission codes and Team scope remain deferred.

# Approved Listing Domain Architecture

`docs/architecture/LISTING_DOMAIN_ARCHITECTURE.md` is the canonical source for the approved, not-yet-implemented Listing domain. It defines Residential Resale MVP scope with future category extensibility; Property/Ownership/Listing separation; provenance and assignment; approval, lifecycle, hold, sold, withdrawal, Owner privacy, marketplace eligibility, and future integration boundaries.

`docs/architecture/LISTING_PHYSICAL_DATA_MODEL.md` is the canonical source for the approved, not-implemented conceptual physical model. It distinguishes System-only Global Physical Property Identity from independent Organization Property records and defines Owner/Ownership history, material versions, media/document separation, Sale Closing, Ownership Transfer boundaries, and logical constraints without approving SQL schema.

`docs/architecture/PROPERTY_PROFILE_ARCHITECTURE.md` is the canonical source for the **APPROVED / NOT IMPLEMENTED** Rich Property Profile. `docs/architecture/PROPERTY_CATALOG_ARCHITECTURE.md` is the canonical source for the **APPROVED** Property Catalog and Data Governance model, partially implemented through BF014.1 schema and BF014.2 repositories/read models. `docs/database/SEED_DATA.md` defines the approved baseline seed strategy; no catalog seed scripts exist.

Organization Property remains separate from Listing. Add Unit is future business/UI terminology only. Backend completeness derives `INCOMPLETE`, `PENDING_REVIEW`, or `COMPLETE` from authoritative Property data and applicable Unit Type configuration; it does not create a Listing, and price/commercial terms remain Listing data. Catalog Proposals and Owner/Ownership data remain Organization-private and must not leak through marketplace assumptions.

The architecture preserves global authenticated marketplace visibility separately from administrative authority. BF013 Property & Ownership Foundation is implemented, verified, and closed. It provides the Organization Property shell, Organization-scoped Owners, historical Ownership aggregates and Acting Owner designations, System-only Global Physical Property Identity links, migrations 009-017, eight BF013 Permission codes, and REST APIs while explicitly excluding Listing workflows and rich Property Profile behavior.

AF002 provides System Franchise network administration and atomic initial administrator onboarding.
AF003 provides Organization-scoped User and Position administration with capability-subset Permission delegation. BF013, BF014, BF015, AF001–AF003, and AF004 are **IMPLEMENTED AND VERIFIED / CLOSED**. AF004 delivered the private `/admin/properties` list, shell creation, Property Data, Owner and Ownership, read-only review, and Media/Private Document unavailable boundary through existing BF013/BF014/BF015 contracts. BF016 and BF016.2–BF016.6 are IMPLEMENTED AND VERIFIED / CLOSED, and no next internal unit is selected. Rich Property Profile beyond the BF015 bridge, frontend-owned completeness, full media and private-document persistence, Catalog Proposals, Listing, Marketplace, Requests, Deals, Commissions, and transfers remain deferred or not implemented. Property setup review is not Listing Ready.

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

## Historical BF016 — Organization and Property Setup Data Foundation

**BF016.2 — Franchise Basic Profile Implementation and BF016.3 — Property Administrative Details: IMPLEMENTED AND VERIFIED**, recorded 2026-10-05. The BF016 parent is **IN PROGRESS / OPEN** until BF016.6 are completed. No next internal unit is selected by this handoff. BF013, BF014, BF015 and AF004 remain closed.

Focused real-MariaDB acceptance and Operational Franchise Admin Activation passed. All 51 required BF016/BF013/BF014/BF015 test/regression entry points passed, including integrated suites and additional service/concurrency tests; all 147 process cleanup checks passed. PHP syntax and frontend typecheck, lint and build passed. No real development database schema/data was modified.

Organization logo/media, full media galleries, documents/contracts, galleries/videos, Listing, Marketplace, Requests, Deals, Commissions, completeness and Listing Ready remain deferred. Property setup is not Listing readiness.

Migration synchronization is planned only for `directors_resale_platform` on `127.0.0.1:3306`: migrations 034 then 035 only, in repository order, no canonical seed impact, verified backup and explicit user approval required, all test/acceptance/demo/fixture data excluded, and preflight/post-apply checks required. Migrations 034 and 035 remain unapplied to the real database; no synchronization was executed.

The 14 mojibake sequences are pre-existing in two BF014 fixtures (eight in `BF014RepositoryDatabaseAcceptanceTest.php`, six in `BF014SchemaAcceptanceTest.php`), match `HEAD`, and were not changed by this work. No invalid UTF-8, replacement characters or new mojibake were introduced.

Canonical implementation evidence and synchronization preflight/backup/post-apply requirements: [BF016 implementation record](docs/sprints/BF016-organization-and-property-setup-data-foundation.md). Implementation and its original handoff were committed and pushed on codex-review in c06aa786; official merge remains human-owner controlled.

### Historical BF016.2/BF016.3 review evidence — 2026-10-05

Company profile now supports existing Organization name, System Franchise selection, private own-Franchise editing, optional street address and protected five-level canonical geography with legitimate skips, safe empty reads and ancestry hydration. Property Data/review now expose an immutable persisted `PROP-<ULID>` code, canonical address, optional positive DECIMAL(18,4) asking price with explicit EGP/USD/SAR/AED currency, and a labelled generic image placeholder without storage. Existing BF015 typed-value patches, atomic revisions and explicit conflict retry are preserved. Authorization remains backend-owned; no permission codes or Listing side effects were added.

Verification: 51/51 PHP matrix entry points passed (two focused BF016 acceptances, Operational Franchise Admin Activation, three BF013, 28 BF014 entry/integrated suites, 12 additional BF014 service/concurrency tests, five BF015). All 147 process cleanup checks passed. PHP syntax passed for 190 files. Frontend typecheck, lint, the BF016 canonical-geography/empty-profile/hydration/error contract acceptance and production build passed. The build completed final trace collection in an identical isolated source copy with shared installed dependencies because the running development server held the original `.next` trace; the development server was left running. Strict UTF-8 and diff checks passed. The 14 pre-existing mojibake sequences in the two BF014 fixtures match HEAD and were not changed; no new mojibake or replacement characters were introduced.

MariaDB remained available at 127.0.0.1:3306; configuration targeted exactly directors_resale_platform. Read-only before/after schema, row-count and data fingerprints matched for all 30 real tables. All newly created disposable databases were removed; the pre-existing directors_resale_platform_e1_test_9740 remained untouched. Repository migrations 001–035 are verified by exact ordered filename equality. Migrations 034 and 035 remain unapplied to the real database. Future synchronization requires a reviewed preflight, verified backup, explicit user approval, 034 then 035 only, no canonical seed impact, exclusion of all test/acceptance/demo/fixture data, and post-apply schema/index/constraint, application and unchanged-business-data checks. No real schema/data operation was performed.

BF016.2 and BF016.3 are IMPLEMENTED AND VERIFIED / READY for review on codex-review. BF016 parent remains OPEN; BF016.4–BF016.6 remain OPEN / PENDING and no next unit is selected. Full media/logo/image persistence, galleries/videos, private documents/contracts, Listing, Marketplace, Requests, Deals, Commissions, completeness and Listing Ready remain deferred. Official-branch merge remains controlled by the human owner.

## Historical BF016.4 — Property Administrative Details Verification and Formal Acceptance

**IMPLEMENTED AND VERIFIED / READY**, 2026-10-05. User-authorized reconciliation keeps BF016.3 as implementation (commit c06aa786) and BF016.4 as verification, proven-gap fixing, regression evidence and formal acceptance. No gap was proven; no application code, tests, fields, permissions or migrations were changed or duplicated.

All 51 PHP matrix entries passed: BF016.2/BF016.3 focused acceptances, Operational Franchise Admin Activation, all BF013/BF014/BF015 entry points and integrated suites, and additional BF014 service/concurrency tests. PHP syntax passed for 190 files. Frontend typecheck, lint, existing contract acceptance and production build including final trace passed; the isolated build copy matched all 118 source hashes. Strict UTF-8, mojibake/replacement scans and diff checks passed; the 14 pre-existing BF014 fixture sequences remain unchanged.

All 146 process cleanup checks passed with no new disposable database residue. All 30 real-table schema/data/row-count fingerprints matched before/after. MariaDB 10.4.32 was available at 127.0.0.1:3306, with .env targeting exactly directors_resale_platform. No real schema/data changes or fixture imports occurred; migrations 034/035 remain unapplied there. The pre-existing e1_test_9740 database remained untouched. Verified backup, preflight, explicit approval and post-apply checks remain mandatory for future synchronization.

BF016.5 remains OPEN for the minimum one-primary-property-image foundation; the generic UI placeholder is not a persisted image. BF016.6 remains OPEN for final closure; BF016 parent remains OPEN. Media, galleries/video, Franchise logo, documents, Listing, Marketplace, Requests, Deals, Commissions, completeness and Listing Ready remain deferred. No next unit is selected. [Exact verification and acceptance evidence](docs/sprints/BF016-organization-and-property-setup-data-foundation.md#bf0164--verification-evidence).

## Historical BF016.5 — Minimal Primary Property Image Foundation

**IMPLEMENTED AND VERIFIED / READY**, 2026-10-05. One private primary-image reference per Property now supports upload, replacement, removal and protected WebP delivery. The Property Data editor and read-only review hydrate this dedicated payload without changing Property/Profile revisions, code, address, price, typed values or Ownership. Existing properties.view/properties.manage and PRIVATE_ORGANIZATION remain authoritative; parent Franchise access does not grant child Partner image access.

CLI and Apache PHP loaded GD/WebP from C:\xampp\php\php.ini. Focused acceptance passed, including actual Apache multipart upload/binary delivery, EXIF/GPS stripping and orientation, invalid type/content/size/reference rejection, atomic persistence-failure cleanup and private scope. The full 52-entry PHP matrix passed (three BF016 focus tests including BF016.4 reuse of BF016.3, Operational Activation, three BF013, 28 BF014, 12 additional BF014 service/concurrency, five BF015); all 147 process cleanup checks passed. PHP syntax passed for 193 files. Frontend typecheck, lint, expanded image/proxy contract acceptance and production build including final trace passed; the isolated build copy matched all 122 source hashes. UTF-8 and diff checks passed; the 14 pre-existing BF014 fixture mojibake markers remain unchanged.

Migrations 001–036 are required by exact ordered filename equality; migration 036 alone adds the dedicated primary-image metadata table and has no seed impact. Migrations 034/035/036 remain unapplied to directors_resale_platform. Read-only schema/row-count/data fingerprints matched across all 30 real tables. Disposable databases, processed/source files and temporary Apache acceptance endpoints were removed; pre-existing e1_test_9740 stayed untouched. Future real synchronization requires reviewed preflight, verified backup, exact ordered approval and post-apply checks; no test/acceptance/demo/fixture data or files may be imported.

BF016.6 and BF016 parent remain OPEN. Galleries/multiple images, video, documents, Franchise logo, full media processing/platform, Listing media management, Marketplace, Requests, Deals, Commissions, completeness and Listing Ready remain deferred. No next unit is selected. [Bounded contract and full evidence](docs/sprints/BF016-organization-and-property-setup-data-foundation.md#bf0165--implementation-and-verification-evidence).

## BF016.6 — Final Acceptance and Closure

**BF016 parent and BF016.2, BF016.3, BF016.4, BF016.5, BF016.6: IMPLEMENTED AND VERIFIED / CLOSED**, 2026-10-05. This final record supersedes historical open/pending checkpoints below or above; their evidence is preserved. BF016.3 remains the administrative-details implementation and BF016.4 its verification/formal acceptance. No duplicate implementation or migration was created. No next internal unit is selected; official-branch merge remains human-owner controlled.

Final integrated real-MariaDB acceptance composes the existing Organization basic profile, Property administrative details, primary-image, BF013 ownership HTTP and BF015 Profile HTTP contracts. It covers canonical geography/address, persisted generated Property Code and duplicate display names, positive asking price/currency, preserved BF015 measurements/attributes and Ownership, private image upload/replacement/removal/validation/metadata-free WebP, Organization authorization/privacy and absence of future workflow/readiness side effects.

All 53 unique PHP matrix entry points passed: one final BF016 integrated runner, three BF016 focused acceptances (BF016.4 reuses BF016.3), Operational Franchise Admin Activation, three BF013, 28 BF014 entry/integrated suites, 12 additional BF014 service/concurrency tests and five BF015. All 153 nested/process disposable database cleanup checks passed. Temporary source/processed images and Apache acceptance endpoints were removed. Frontend typecheck, lint and contract tests passed. Production build completed final trace and 33 static pages in an isolated copy matching all 122 source hashes, leaving the running development server untouched. PHP syntax passed for 194 files. Strict UTF-8 and replacement-character validation passed; changed-file mojibake and git diff checks passed.

Encoding baseline: the unchanged historical BF014 sprint document contains 4,852 pre-existing scan matches; worktree and HEAD Git blob hashes both equal `02f651068658df167ca9f62d2a55e8eeca7f98ed`. Per explicit owner instruction these are baseline findings, not BF016 defects; the document was not rewritten. The known 14 pre-existing fixture sequences remain unchanged (eight in BF014RepositoryDatabaseAcceptanceTest.php, six in BF014SchemaAcceptanceTest.php). No new mojibake or replacement characters were introduced in BF016 or changed files.

MariaDB 10.4.32 was available at 127.0.0.1:3306 on DESKTOP-5EI3DP1; .env targets exactly directors_resale_platform. All 30 real-table schema, row-count and data fingerprints remained unchanged. Migrations 034, 035 and 036 remain unapplied. No test/acceptance/demo/fixture data or images were imported. The pre-existing directors_resale_platform_e1_test_9740 was left untouched. Schema synchronization remains a separate handoff requiring renewed preflight, verified backup and explicit owner approval; there is no canonical seed impact.

Deferred: full media system, galleries/multiple images, videos, Franchise logo/media, private documents/contracts, Listing, Marketplace, Requests, Deals, Commissions, completeness, Listing Ready and other future business workflows. Property setup is not Listing readiness.

[Complete individual matrix and schema synchronization handoff](docs/sprints/BF016-organization-and-property-setup-data-foundation.md#bf0166--final-acceptance-and-closure).

## BF017 — Listing MVP Foundation — 2026-10-06

**IMPLEMENTED AND VERIFIED / READY.** Owner-approved bounded sprint: [BF017 contract](docs/sprints/BF017-listing-mvp-foundation.md). New canonical listings.view/listings.manage; direct draft -> published -> archived; Property remains the source of truth for identity, address, initial price/currency, primary image and current Ownership. Listing routes use authorized own/direct-child hierarchy and a limited presentation projection; existing PRIVATE_ORGANIZATION routes and Owner privacy remain unchanged. Partner Agency full/operational onboarding remains deferred because existing Organization context is sufficient. BF016 and BF016.2–BF016.6 stay CLOSED.

Repository-only migration 037_create_listings_table_and_permissions.sql adds Listing lifecycle/revision storage, active-Property uniqueness, composite owning-Property foreign key, lifecycle/revision checks and exactly two canonical permissions. No Position assignments or business/demo data. No real database migration or seed is authorized. Internal/Owner approval, moderation, broad Listing Ready/completeness, Marketplace/search, Requests, Deals, Commissions, payments, notifications, galleries and other future workflows remain deferred. Actual environment synchronization requires separate preflight, verified backup and explicit approval.

### BF017 final verification evidence

All 55 unique PHP matrix entry points passed, including focused BF017, existing Franchise Admin hierarchy security, Operational Activation and the complete established BF013–BF016 entry/integrated/service/concurrency matrix. Latest BF017 acceptance passed 103 assertions plus guarded database/file cleanup checks. All 55 matrix entries passed post-process database inventory and temporary public HTTP-file cleanup; internal suites also execute their existing cleanup. The pre-existing e1_test_9740 database remained untouched. No new disposable database or uploaded file remained.

PHP syntax: 199 files PASS. Frontend typecheck, lint, BF016 contract suite and new BF017 component hydration/capability/error/API suite PASS. Production build PASS through final trace collection and 35 static pages in an isolated copy matching all 134 frontend source hashes; the running development server was left intact. Strict UTF-8 passed for all 480 repository text files; all 38 changed-file mojibake/replacement and git diff checks PASS. Historical BF014 document retains HEAD blob 02f651068658df167ca9f62d2a55e8eeca7f98ed and its 4,852 known baseline matches; the two BF014 fixtures retain exactly eight plus six baseline sequences, with no new findings.

MariaDB is available at 127.0.0.1:3306; .env resolves exactly directors_resale_platform via C:\xampp\php\php.exe and C:\xampp\php\php.ini. Before/after all 32 real table definitions, row counts and data fingerprints match. Aggregate schema SHA256: 8d19ef5feaed9c7205d404aeec18cde6cac8402e1eab84065cde2d60ad537b45. Aggregate data/row-count SHA256: 2f77070383a15a16719b4153779299e45ab15cc14eb5303fd6d71964a92f0311. No real login/token insertion, Position grant, seed, migration, business/demo/test data or image change occurred. Migration 037 remains unapplied, and neither new Listing capability is granted to real Positions by this task.

Two proven stale regression expectations were reconciled: BF016 no-side-effect checks now require an empty Listing table while preserving deferred-domain table absence; BF014 excludes future domains but permits the six owner-approved Listing routes, whose exact registered surface is verified by BF017. Exact ordered migration manifests extend 001–036 to 001–037 without accepting arbitrary extras. Historical BF014 catalog count checks still require exactly 32 pre-Listing codes; focused BF017 requires exactly 34 total codes and the exact two active Listing capabilities. An initial new-test float/int strict comparison was corrected; latest acceptance and the complete final matrix pass. No security boundary was relaxed.

READY means bounded implementation/verification ready for Architect review. Actual demo use against the real database requires separate explicit schema synchronization and grant approval, then eligible existing Property data. No next implementation unit is selected; Partner onboarding remains deferred. BF016 stays CLOSED.
