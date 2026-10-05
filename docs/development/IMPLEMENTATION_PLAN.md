# Implementation Plan - Current Status

## Completed

| Milestone | Status | Verified State |
|---|---|---|
| BF001 | Completed | Backend bootstrap, container, router, response, exception handling, health endpoint |
| BF002 / BF002.1 / BF002.2 | Completed | Contracts and reusable database foundation |
| BF003 | Completed | Generic BaseRepository |
| BF004 | Completed | Service-container functionality present; no separate Git commit |
| BF005 | Completed | HTTP Kernel and Request abstraction |
| BF006 | Completed | Organization CRUD/API, staged `organizations` table, and routing fix `a017c94` |
| BF007 | Completed | Organization-backed Franchise CRUD/API, `parent_organization_id`, System-parent validation, and status-based archival |
| BF008 | Implemented | Organization-backed Partner Agency CRUD/API, Franchise-parent validation, isolation, and status-based archival |
| BF009 | Implemented | Staged User CRUD/API, active Organization ownership, global unique email, immutable ownership, and status-based deactivation |
| BF010 | Implemented | Secure password credentials, hashed expiring bearer tokens, login/logout/me, reusable authentication protection, and protected business routes |
| BF011 | Implemented | Dynamic Organization-owned Positions, scoped code uniqueness, nullable same-Organization User assignment, and status-based deactivation |
| BF012 | Implemented | Controlled Permission catalog, Position-Permission inheritance, reusable authorization, Organization scope enforcement, and isolated lists |
| BF013 | Completed | Property & Ownership backend foundation implemented, verified, and closed |
| AF001 | Implemented | Next.js Admin UI foundation, secure HttpOnly auth bridge, context, protected layout, permission-aware navigation, dashboard, and placeholders |
| AF002 | Implemented | Franchise network list/detail, Partner Agency overview, status-based deactivation, and atomic initial administrator onboarding |
| AF003 | Completed | Organization-scoped User and Position administration with safe Position ownership and capability-subset delegation; browser QA passed and branch committed/pushed |

BF006 smoke tests and BF007 manual acceptance/regression checks passed at their closure. BF014.1 verification also passed the existing standalone regression scripts and real MariaDB acceptance; no unified project-wide test runner exists.

## Current Planning State

BF013, BF014, BF015, AF001–AF003, and AF004 — Property Administration UI — are **IMPLEMENTED AND VERIFIED / CLOSED**. AF004.1 through AF004.6 are closed.

The closed BF014 contract records schema/integrity, repositories/read models, services, authorization/HTTP, seed runner/baseline, dynamic form projection, MariaDB acceptance/regressions, and documentation closure. BF015 records the bounded Property Profile persistence bridge.

## Approved Future Execution Order

AF004 is closed. BF016 is IN PROGRESS / OPEN; BF016.2–BF016.5 are IMPLEMENTED AND VERIFIED. BF016.6 remain pending; no next internal unit is selected by this handoff. AF004 remains limited to administrative composition of BF013/BF014/BF015; completeness, full media/private-document persistence, Listing, Marketplace, Requests, Deals, Commissions, and transfers remain deferred.

1. Architecture closure - complete
2. BF014 parent sprint contract - recorded and authoritative
3. BF014 - Canonical Property Catalog Foundation - IMPLEMENTED AND VERIFIED / CLOSED
4. BF015 - Property Profile Persistence Bridge - IMPLEMENTED AND VERIFIED / CLOSED
5. Rich Property Profile beyond the bridge
6. System Admin Property Data Management frontend
7. Initial canonical data verification and approved business additions
8. Admin Properties frontend: Active, Drafts, Add Unit, and Property Details
9. Listing implementation
10. Requests, Deals, Commissions, Notifications, and Reports

## Constraints for Future Planning

- Preserve the BF006-BF007 staged `organizations` schema unless an approved milestone changes it.
- Resolve the documented no-hard-delete versus Organization physical DELETE discrepancy before
  relying on deletion behavior for future business modules.
- Franchise DELETE is scoped to status-based archival (`inactive`); it does not create a general lifecycle framework.
- Authentication and authorization are implemented through BF010-BF012 and remain backend-authoritative.
- Preserve the approved System Organization -> Franchise -> Partner Agency hierarchy. Users belong to an
  Organization in this hierarchy; do not treat Users as another hierarchy level.
- Preserve the approved distinction between future Global Marketplace Visibility and Administrative
  Scope. Do not use Organization management scope to isolate marketplace-eligible available Listings.
- Use `docs/architecture/LISTING_DOMAIN_ARCHITECTURE.md` as the canonical source for approved Listing boundaries and behavior.
- Use `docs/architecture/LISTING_PHYSICAL_DATA_MODEL.md` as the canonical source for the approved conceptual physical model; do not treat it as a committed SQL schema.
- Use `docs/architecture/PROPERTY_PROFILE_ARCHITECTURE.md` for approved Rich Property Profile behavior and `docs/architecture/PROPERTY_CATALOG_ARCHITECTURE.md` for approved Catalog/Data Governance behavior; Rich Property Profile remains unimplemented, while Property Catalog is partially implemented through BF014.1 schema and BF014.2 repositories/read models.
- Use `docs/database/SEED_DATA.md` as the approved seed strategy. Schema migrations and seed execution remain separate, and no Property catalog seed scripts currently exist.
- Use `docs/sprints/BF013-property-ownership-foundation.md` as the authoritative closed BF013 implementation contract.
- Keep future Listing SQL schema, APIs, Listing Permission codes, Team scope, complete Request/Deal/Commission/Transfer workflows, publication field lists, and subsequent sprint scope deferred until separately approved.
- Do not infer implementation from broader planned architecture documents.
- Preserve Organization Property versus Listing separation, Organization-private Owner/Ownership/Proposal data, backend-authoritative completeness, and the Arabic-first/RTL-first frontend direction.

## BF016 — Organization and Property Setup Data Foundation

**BF016.2 — Franchise Basic Profile Implementation and BF016.3 — Property Administrative Details: IMPLEMENTED AND VERIFIED**, recorded 2026-10-05. The BF016 parent is **IN PROGRESS / OPEN** until BF016.6 are completed. No next internal unit is selected by this handoff. BF013, BF014, BF015 and AF004 remain closed.

Focused real-MariaDB acceptance and Operational Franchise Admin Activation passed. All 51 required BF016/BF013/BF014/BF015 test/regression entry points passed, including integrated suites and additional service/concurrency tests; all 147 process cleanup checks passed. PHP syntax and frontend typecheck, lint and build passed. No real development database schema/data was modified.

Organization logo/media, full media galleries, documents/contracts, galleries/videos, Listing, Marketplace, Requests, Deals, Commissions, completeness and Listing Ready remain deferred. Property setup is not Listing readiness.

Migration synchronization is planned only for `directors_resale_platform` on `127.0.0.1:3306`: migrations 034 then 035 only, in repository order, no canonical seed impact, verified backup and explicit user approval required, all test/acceptance/demo/fixture data excluded, and preflight/post-apply checks required. Migrations 034 and 035 remain unapplied to the real database; no synchronization was executed.

The 14 mojibake sequences are pre-existing in two BF014 fixtures (eight in `BF014RepositoryDatabaseAcceptanceTest.php`, six in `BF014SchemaAcceptanceTest.php`), match `HEAD`, and were not changed by this work. No invalid UTF-8, replacement characters or new mojibake were introduced.

Canonical implementation evidence and synchronization preflight/backup/post-apply requirements: [BF016 implementation record](../sprints/BF016-organization-and-property-setup-data-foundation.md). Implementation and its original handoff were committed and pushed on codex-review in c06aa786; official merge remains human-owner controlled.

### Historical BF016.2/BF016.3 review evidence — 2026-10-05

Company profile now supports existing Organization name, System Franchise selection, private own-Franchise editing, optional street address and protected five-level canonical geography with legitimate skips, safe empty reads and ancestry hydration. Property Data/review now expose an immutable persisted `PROP-<ULID>` code, canonical address, optional positive DECIMAL(18,4) asking price with explicit EGP/USD/SAR/AED currency, and a labelled generic image placeholder without storage. Existing BF015 typed-value patches, atomic revisions and explicit conflict retry are preserved. Authorization remains backend-owned; no permission codes or Listing side effects were added.

Verification: 51/51 PHP matrix entry points passed (two focused BF016 acceptances, Operational Franchise Admin Activation, three BF013, 28 BF014 entry/integrated suites, 12 additional BF014 service/concurrency tests, five BF015). All 147 process cleanup checks passed. PHP syntax passed for 190 files. Frontend typecheck, lint, the BF016 canonical-geography/empty-profile/hydration/error contract acceptance and production build passed. The build completed final trace collection in an identical isolated source copy with shared installed dependencies because the running development server held the original `.next` trace; the development server was left running. Strict UTF-8 and diff checks passed. The 14 pre-existing mojibake sequences in the two BF014 fixtures match HEAD and were not changed; no new mojibake or replacement characters were introduced.

MariaDB remained available at 127.0.0.1:3306; configuration targeted exactly directors_resale_platform. Read-only before/after schema, row-count and data fingerprints matched for all 30 real tables. All newly created disposable databases were removed; the pre-existing directors_resale_platform_e1_test_9740 remained untouched. Repository migrations 001–035 are verified by exact ordered filename equality. Migrations 034 and 035 remain unapplied to the real database. Future synchronization requires a reviewed preflight, verified backup, explicit user approval, 034 then 035 only, no canonical seed impact, exclusion of all test/acceptance/demo/fixture data, and post-apply schema/index/constraint, application and unchanged-business-data checks. No real schema/data operation was performed.

BF016.2 and BF016.3 are IMPLEMENTED AND VERIFIED / READY for review on codex-review. BF016 parent remains OPEN; BF016.4–BF016.6 remain OPEN / PENDING and no next unit is selected. Full media/logo/image persistence, galleries/videos, private documents/contracts, Listing, Marketplace, Requests, Deals, Commissions, completeness and Listing Ready remain deferred. Official-branch merge remains controlled by the human owner.

## Historical BF016.4 — Property Administrative Details Verification and Formal Acceptance

**IMPLEMENTED AND VERIFIED / READY**, 2026-10-05. User-authorized reconciliation keeps BF016.3 as implementation (commit c06aa786) and BF016.4 as verification, proven-gap fixing, regression evidence and formal acceptance. No gap was proven; no application code, tests, fields, permissions or migrations were changed or duplicated.

All 51 PHP matrix entries passed: BF016.2/BF016.3 focused acceptances, Operational Franchise Admin Activation, all BF013/BF014/BF015 entry points and integrated suites, and additional BF014 service/concurrency tests. PHP syntax passed for 190 files. Frontend typecheck, lint, existing contract acceptance and production build including final trace passed; the isolated build copy matched all 118 source hashes. Strict UTF-8, mojibake/replacement scans and diff checks passed; the 14 pre-existing BF014 fixture sequences remain unchanged.

All 146 process cleanup checks passed with no new disposable database residue. All 30 real-table schema/data/row-count fingerprints matched before/after. MariaDB 10.4.32 was available at 127.0.0.1:3306, with .env targeting exactly directors_resale_platform. No real schema/data changes or fixture imports occurred; migrations 034/035 remain unapplied there. The pre-existing e1_test_9740 database remained untouched. Verified backup, preflight, explicit approval and post-apply checks remain mandatory for future synchronization.

BF016.5 remains OPEN for the minimum one-primary-property-image foundation; the generic UI placeholder is not a persisted image. BF016.6 remains OPEN for final closure; BF016 parent remains OPEN. Media, galleries/video, Franchise logo, documents, Listing, Marketplace, Requests, Deals, Commissions, completeness and Listing Ready remain deferred. No next unit is selected. [Exact verification and acceptance evidence](../sprints/BF016-organization-and-property-setup-data-foundation.md#bf0164--verification-evidence).

## BF016.5 — Minimal Primary Property Image Foundation

**IMPLEMENTED AND VERIFIED / READY**, 2026-10-05. One private primary-image reference per Property now supports upload, replacement, removal and protected WebP delivery. The Property Data editor and read-only review hydrate this dedicated payload without changing Property/Profile revisions, code, address, price, typed values or Ownership. Existing properties.view/properties.manage and PRIVATE_ORGANIZATION remain authoritative; parent Franchise access does not grant child Partner image access.

CLI and Apache PHP loaded GD/WebP from C:\xampp\php\php.ini. Focused acceptance passed, including actual Apache multipart upload/binary delivery, EXIF/GPS stripping and orientation, invalid type/content/size/reference rejection, atomic persistence-failure cleanup and private scope. The full 52-entry PHP matrix passed (three BF016 focus tests including BF016.4 reuse of BF016.3, Operational Activation, three BF013, 28 BF014, 12 additional BF014 service/concurrency, five BF015); all 147 process cleanup checks passed. PHP syntax passed for 193 files. Frontend typecheck, lint, expanded image/proxy contract acceptance and production build including final trace passed; the isolated build copy matched all 122 source hashes. UTF-8 and diff checks passed; the 14 pre-existing BF014 fixture mojibake markers remain unchanged.

Migrations 001–036 are required by exact ordered filename equality; migration 036 alone adds the dedicated primary-image metadata table and has no seed impact. Migrations 034/035/036 remain unapplied to directors_resale_platform. Read-only schema/row-count/data fingerprints matched across all 30 real tables. Disposable databases, processed/source files and temporary Apache acceptance endpoints were removed; pre-existing e1_test_9740 stayed untouched. Future real synchronization requires reviewed preflight, verified backup, exact ordered approval and post-apply checks; no test/acceptance/demo/fixture data or files may be imported.

BF016.6 and BF016 parent remain OPEN. Galleries/multiple images, video, documents, Franchise logo, full media processing/platform, Listing media management, Marketplace, Requests, Deals, Commissions, completeness and Listing Ready remain deferred. No next unit is selected. [Bounded contract and full evidence](../sprints/BF016-organization-and-property-setup-data-foundation.md#bf0165--implementation-and-verification-evidence).
