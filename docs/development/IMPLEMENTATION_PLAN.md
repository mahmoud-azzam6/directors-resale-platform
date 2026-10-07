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

AF004 is closed. BF016 and BF016.2–BF016.6 are IMPLEMENTED AND VERIFIED / CLOSED. BF016.6 is closed; no next internal unit is selected by this handoff. AF004 remains limited to administrative composition of BF013/BF014/BF015; completeness, full media/private-document persistence, Listing, Marketplace, Requests, Deals, Commissions, and transfers remain deferred.

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

## Historical BF016 — Organization and Property Setup Data Foundation

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

## Historical BF016.5 — Minimal Primary Property Image Foundation

**IMPLEMENTED AND VERIFIED / READY**, 2026-10-05. One private primary-image reference per Property now supports upload, replacement, removal and protected WebP delivery. The Property Data editor and read-only review hydrate this dedicated payload without changing Property/Profile revisions, code, address, price, typed values or Ownership. Existing properties.view/properties.manage and PRIVATE_ORGANIZATION remain authoritative; parent Franchise access does not grant child Partner image access.

CLI and Apache PHP loaded GD/WebP from C:\xampp\php\php.ini. Focused acceptance passed, including actual Apache multipart upload/binary delivery, EXIF/GPS stripping and orientation, invalid type/content/size/reference rejection, atomic persistence-failure cleanup and private scope. The full 52-entry PHP matrix passed (three BF016 focus tests including BF016.4 reuse of BF016.3, Operational Activation, three BF013, 28 BF014, 12 additional BF014 service/concurrency, five BF015); all 147 process cleanup checks passed. PHP syntax passed for 193 files. Frontend typecheck, lint, expanded image/proxy contract acceptance and production build including final trace passed; the isolated build copy matched all 122 source hashes. UTF-8 and diff checks passed; the 14 pre-existing BF014 fixture mojibake markers remain unchanged.

Migrations 001–036 are required by exact ordered filename equality; migration 036 alone adds the dedicated primary-image metadata table and has no seed impact. Migrations 034/035/036 remain unapplied to directors_resale_platform. Read-only schema/row-count/data fingerprints matched across all 30 real tables. Disposable databases, processed/source files and temporary Apache acceptance endpoints were removed; pre-existing e1_test_9740 stayed untouched. Future real synchronization requires reviewed preflight, verified backup, exact ordered approval and post-apply checks; no test/acceptance/demo/fixture data or files may be imported.

BF016.6 and BF016 parent remain OPEN. Galleries/multiple images, video, documents, Franchise logo, full media processing/platform, Listing media management, Marketplace, Requests, Deals, Commissions, completeness and Listing Ready remain deferred. No next unit is selected. [Bounded contract and full evidence](../sprints/BF016-organization-and-property-setup-data-foundation.md#bf0165--implementation-and-verification-evidence).

## BF016.6 — Final Acceptance and Closure

**BF016 parent and BF016.2, BF016.3, BF016.4, BF016.5, BF016.6: IMPLEMENTED AND VERIFIED / CLOSED**, 2026-10-05. This final record supersedes historical open/pending checkpoints below or above; their evidence is preserved. BF016.3 remains the administrative-details implementation and BF016.4 its verification/formal acceptance. No duplicate implementation or migration was created. No next internal unit is selected; official-branch merge remains human-owner controlled.

Final integrated real-MariaDB acceptance composes the existing Organization basic profile, Property administrative details, primary-image, BF013 ownership HTTP and BF015 Profile HTTP contracts. It covers canonical geography/address, persisted generated Property Code and duplicate display names, positive asking price/currency, preserved BF015 measurements/attributes and Ownership, private image upload/replacement/removal/validation/metadata-free WebP, Organization authorization/privacy and absence of future workflow/readiness side effects.

All 53 unique PHP matrix entry points passed: one final BF016 integrated runner, three BF016 focused acceptances (BF016.4 reuses BF016.3), Operational Franchise Admin Activation, three BF013, 28 BF014 entry/integrated suites, 12 additional BF014 service/concurrency tests and five BF015. All 153 nested/process disposable database cleanup checks passed. Temporary source/processed images and Apache acceptance endpoints were removed. Frontend typecheck, lint and contract tests passed. Production build completed final trace and 33 static pages in an isolated copy matching all 122 source hashes, leaving the running development server untouched. PHP syntax passed for 194 files. Strict UTF-8 and replacement-character validation passed; changed-file mojibake and git diff checks passed.

Encoding baseline: the unchanged historical BF014 sprint document contains 4,852 pre-existing scan matches; worktree and HEAD Git blob hashes both equal `02f651068658df167ca9f62d2a55e8eeca7f98ed`. Per explicit owner instruction these are baseline findings, not BF016 defects; the document was not rewritten. The known 14 pre-existing fixture sequences remain unchanged (eight in BF014RepositoryDatabaseAcceptanceTest.php, six in BF014SchemaAcceptanceTest.php). No new mojibake or replacement characters were introduced in BF016 or changed files.

MariaDB 10.4.32 was available at 127.0.0.1:3306 on DESKTOP-5EI3DP1; .env targets exactly directors_resale_platform. All 30 real-table schema, row-count and data fingerprints remained unchanged. Migrations 034, 035 and 036 remain unapplied. No test/acceptance/demo/fixture data or images were imported. The pre-existing directors_resale_platform_e1_test_9740 was left untouched. Schema synchronization remains a separate handoff requiring renewed preflight, verified backup and explicit owner approval; there is no canonical seed impact.

Deferred: full media system, galleries/multiple images, videos, Franchise logo/media, private documents/contracts, Listing, Marketplace, Requests, Deals, Commissions, completeness, Listing Ready and other future business workflows. Property setup is not Listing readiness.

[Complete individual matrix and schema synchronization handoff](../sprints/BF016-organization-and-property-setup-data-foundation.md#bf0166--final-acceptance-and-closure).

## BF017 — Listing MVP Foundation — 2026-10-06

**IMPLEMENTED AND VERIFIED / READY.** Owner-approved bounded sprint: [BF017 contract](../sprints/BF017-listing-mvp-foundation.md). New canonical listings.view/listings.manage; direct draft -> published -> archived; Property remains the source of truth for identity, address, initial price/currency, primary image and current Ownership. Listing routes use authorized own/direct-child hierarchy and a limited presentation projection; existing PRIVATE_ORGANIZATION routes and Owner privacy remain unchanged. Partner Agency full/operational onboarding remains deferred because existing Organization context is sufficient. BF016 and BF016.2–BF016.6 stay CLOSED.

Repository-only migration 037_create_listings_table_and_permissions.sql adds Listing lifecycle/revision storage, active-Property uniqueness, composite owning-Property foreign key, lifecycle/revision checks and exactly two canonical permissions. No Position assignments or business/demo data. No real database migration or seed is authorized. Internal/Owner approval, moderation, broad Listing Ready/completeness, Marketplace/search, Requests, Deals, Commissions, payments, notifications, galleries and other future workflows remain deferred. Actual environment synchronization requires separate preflight, verified backup and explicit approval.

### BF017 final verification evidence

All 55 unique PHP matrix entry points passed, including focused BF017, existing Franchise Admin hierarchy security, Operational Activation and the complete established BF013–BF016 entry/integrated/service/concurrency matrix. Latest BF017 acceptance passed 103 assertions plus guarded database/file cleanup checks. All 55 matrix entries passed post-process database inventory and temporary public HTTP-file cleanup; internal suites also execute their existing cleanup. The pre-existing e1_test_9740 database remained untouched. No new disposable database or uploaded file remained.

PHP syntax: 199 files PASS. Frontend typecheck, lint, BF016 contract suite and new BF017 component hydration/capability/error/API suite PASS. Production build PASS through final trace collection and 35 static pages in an isolated copy matching all 134 frontend source hashes; the running development server was left intact. Strict UTF-8 passed for all 480 repository text files; all 38 changed-file mojibake/replacement and git diff checks PASS. Historical BF014 document retains HEAD blob 02f651068658df167ca9f62d2a55e8eeca7f98ed and its 4,852 known baseline matches; the two BF014 fixtures retain exactly eight plus six baseline sequences, with no new findings.

MariaDB is available at 127.0.0.1:3306; .env resolves exactly directors_resale_platform via C:\xampp\php\php.exe and C:\xampp\php\php.ini. Before/after all 32 real table definitions, row counts and data fingerprints match. Aggregate schema SHA256: 8d19ef5feaed9c7205d404aeec18cde6cac8402e1eab84065cde2d60ad537b45. Aggregate data/row-count SHA256: 2f77070383a15a16719b4153779299e45ab15cc14eb5303fd6d71964a92f0311. No real login/token insertion, Position grant, seed, migration, business/demo/test data or image change occurred. Migration 037 remains unapplied, and neither new Listing capability is granted to real Positions by this task.

Two proven stale regression expectations were reconciled: BF016 no-side-effect checks now require an empty Listing table while preserving deferred-domain table absence; BF014 excludes future domains but permits the six owner-approved Listing routes, whose exact registered surface is verified by BF017. Exact ordered migration manifests extend 001–036 to 001–037 without accepting arbitrary extras. Historical BF014 catalog count checks still require exactly 32 pre-Listing codes; focused BF017 requires exactly 34 total codes and the exact two active Listing capabilities. An initial new-test float/int strict comparison was corrected; latest acceptance and the complete final matrix pass. No security boundary was relaxed.

READY means bounded implementation/verification ready for Architect review. Actual demo use against the real database requires separate explicit schema synchronization and grant approval, then eligible existing Property data. No next implementation unit is selected; Partner onboarding remains deferred. BF016 stays CLOSED.

## BF018 - Published Listings Catalog & Request Flow - 2026-10-07

**IMPLEMENTED AND VERIFIED / READY FOR OWNER REVIEW. Commit/push approval pending.** [Bounded BF018 contract and complete verification matrix](../sprints/BF018-published-listings-catalog-and-request-flow.md). This current entry supersedes earlier next-unit/deferred-Request statements only for the owner-approved expression-of-interest slice. BF016 and BF017 remain CLOSED; no next implementation unit is selected.

Authenticated catalog/details/private image delivery show only currently eligible published Listings in the existing authorized Organization hierarchy. Existing Property remains the presentation source. Three explicitly approved canonical capabilities: `published_listings.view`, `requests.create`, `requests.view`. Submission requires catalog view plus create, derives identity/references server-side and permits one submitted Request per user/Listing, with concurrent duplicate protection. Receipts are requester-only, including for System actors, and remain readable after archival subject to current scope. No anonymous, peer-Franchise or unrelated-System access is added; existing private Property/Owner/Ownership boundaries and BF017 administration remain unchanged.

Repository-only migration `038_create_listing_interest_requests_and_permissions.sql` adds `listing_interest_requests`, composite reference foreign keys, lookup/unique indexes, submitted-only check and the three canonical Permission rows. Additional unique reference indexes on existing `listings` and `users` support composite integrity. No Position assignments or business fixtures are included. Migration 038 remains UNAPPLIED to `127.0.0.1:3306/directors_resale_platform`; separate environment preflight, verified backup and explicit owner synchronization approval are required before actual use.

Final gate: all 56 PHP matrix entries PASS, including BF018 (119 focused assertions), BF017, BF013-BF016, Franchise hierarchy security, Operational Activation and service/concurrency/integrated suites. Disposable database and public-file cleanup PASS after every entry. PHP syntax 203 files PASS; frontend typecheck, lint and BF016/BF017/BF018 contracts PASS. Production build PASS through final trace generation and 39 generated pages, from an isolated copy matching 148 frontend source hashes; copied build output and dependency junction cleaned. UTF-8 and changed-file mojibake/replacement scans PASS; no new findings. Historical BF014 document retains HEAD blob `02f651068658df167ca9f62d2a55e8eeca7f98ed` and 4,852 baseline matches; the two fixtures retain exactly 8 + 6 known sequences unchanged. Exact migration manifests now require ordered 001-038; historical catalog assertions exclude only explicitly approved later codes, retaining exact BF014/BF017 counts. One stale BF014 permission-count assertion was proven and corrected before the complete successful rerun.

Real database remains unchanged across all 33 tables, schemas, row counts and data fingerprints. Schema SHA256: `e9d22d56629026c8074976d9ba7a36de00dac1aec1a15c8c9d76a8bdd9e6dfa9`. Data/row-count SHA256: `1d29c8505207501d51def46d901d76f0a18c3073f722d5f18d1f79b217174bf6`. No real login/token, Request, Listing, migration, seed, permission grant, business/demo change or test import occurred. The pre-existing `directors_resale_platform_e1_test_9740` database was excluded and untouched. All new acceptance fixtures were isolated and cleaned.

Deferred: anonymous/global Marketplace/search, Request administration/cancellation/review, Holds, Deals, negotiation, viewing, Contracts, Sale Approval, Commissions, payments, transfers, notifications, full media/galleries/videos/Franchise logos/private documents, completeness/Listing Ready, approval/moderation workflows and full Partner onboarding. Requests never reserve or mutate Listing/Property/Ownership/image data or create downstream business records. Before Git operations, owner approval of the concrete verified handoff is still required by the BF018 request.
