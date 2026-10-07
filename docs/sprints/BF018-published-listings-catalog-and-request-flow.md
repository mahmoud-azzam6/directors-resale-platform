# BF018 — Published Listings Catalog & Request Flow

Status: IMPLEMENTED AND VERIFIED / READY FOR OWNER REVIEW, 2026-10-07. Commit/push approval pending.

## Inspection and bounded objective

BF018 is unused in the repository/roadmap. Inspection started on clean `codex-review` at `c76e003d956a920f81bba2f03954c4c6ef5eb7eb`. BF013–BF017 remain closed. No interest-Request module, routes, migration, or real-database table exists. Planned viewing requests and approval requests are different future concepts. The HTTP Request abstraction is infrastructure, not the business Request domain.

Implement an authenticated, organization/hierarchy-scoped published Listing catalog and expression-of-interest Request. This is not anonymous/public or cross-Franchise Marketplace exposure. Use existing authenticated Organization-owned Users and Positions; no new customer identity, customer Organization type, role engine, registration or Partner onboarding.

## Capability separation — explicitly approved by owner

- `published_listings.view`: catalog, eligible published details and private image delivery. Does not grant BF017 administrative Listing reads or writes.
- `requests.create`: submit interest for a currently eligible Listing within existing authorized hierarchy scope.
- `requests.view`: retrieve the authenticated requester's own receipts within current authorized scope. Does not grant access to another requester's records or future Request administration.

Existing `listings.view` and `listings.manage` remain unchanged. Giving customers `listings.view` would also expose administrative Draft/Archived reads; the separate published capability avoids that. Use canonical Permission/Position assignment and existing capability-subset/hierarchy delegation. No real Permission rows or Position links are authorized by this implementation task.

## Catalog and availability contract

Only `published` Listings whose owning Franchise/Partner Organization and Property remain active, accessible and currently valid appear. Current availability reuses BF017's authoritative publication prerequisites: generated code, valid canonical geography/address, positive price/supported currency, readable private primary WebP, and current ownership with an active same-Organization Owner. Do not add a completeness score, new BF017 lifecycle state, availability flag, Hold, approval or moderation workflow. Existing BF017 endpoints and administrative lifecycle behavior remain compatible.

Read presentation data from Property/Profile/image relations, not copied Listing or Request identity snapshots. Hide Draft/Archived/inaccessible/invalid entries server-side; enforce the same eligibility on details and image delivery. Stale links return a safe unavailable/not-found result. Anonymous access is denied. Existing BF013 private Property/Owner/Ownership scope is not broadened.

## Request contract and decisions

An interest Request records the authenticated requester and references the existing Listing and its underlying Property. Derive all Organization/Property/user references server-side; accept no client-owned requester, Organization, Property, status or audit fields. Serialize creation with BF017's Property-then-Listing lock order, reauthorize current scope and recheck publication/availability before insertion. Preserve database referential integrity and concurrent duplicate protection.

Duplicate decision: at most one submitted Request per authenticated user per Listing. Different users may request the same Listing. This unit has only the `submitted` receipt state; cancellation, review, negotiation and further Request lifecycle are deferred. A duplicate attempt returns an explicit already-requested response and creates no additional row. Requests do not reserve or mutate the Listing/Property and never create Holds, Deals, Sales, Contracts, Commissions, transfers or notifications.

Implemented new table: `listing_interest_requests`, with ULID, Listing/Property/owning Organization references, authenticated requester/user Organization references, submitted status and standard timestamps. No Property presentation data or invented customer fields. Repository migration: `038_create_listing_interest_requests_and_permissions.sql`; composite foreign keys and exact parent reference indexes were verified in disposable MariaDB acceptance. Migration applies only in newly created guarded disposable acceptance databases during this task, never to the real database.

Implemented protected surface: GET `/published-listings`, GET `/published-listings/{id}`, GET `/published-listings/{id}/primary-image/content`, POST `/published-listings/{id}/requests`, GET `/my-listing-requests`, GET `/my-listing-requests/{id}`. Creation accepts an empty payload and returns the stored receipt. Implemented UI: `/admin/available-listings`, `/admin/available-listings/[id]`, `/admin/my-requests`, using the existing protected shell, Arabic/RTL, responsive cards, generated Property Codes and clear confirmation/loading/success/duplicate/unavailable/error alerts. No visible numeric Property ID entry.

## Verification and handoff gate

Add disposable MariaDB acceptance for schema/FKs/indexes, published-only catalog/details/images, own/child hierarchy and peer/System denial, capability separation, every unavailable prerequisite, authenticated Request identity/reference integrity, duplicates including concurrency, own-receipt privacy, identifier tampering and no business-domain side effects. Keep exact ordered migration manifests; do not accept arbitrary extra migrations. Run the full established BF013–BF017/hierarchy/Operational Activation matrix plus BF018, PHP syntax, frontend typecheck/lint/all contract tests, build through final traces, strict UTF-8, changed-file mojibake/replacement scan and diff checks. Verify cleanup after each disposable suite and unchanged real table/schema/data fingerprints against a fresh read-only baseline.

Preserve historical encoding baselines (unchanged BF014 document and the known 14 fixture sequences). No migrations, seeds, test/demo imports, Requests, Listings, login tokens or permission grants on the real database. No official-branch merge/push.

Before committing, report exact changed files, migration, endpoints, capabilities, database impact, compatibility, checks and outstanding decisions. The owner must approve that concrete verified result before commit/push. Only then use `feat: implement published listings and request flow` on `codex-review` and push `origin/codex-review`.

## BF018 - Published Listings Catalog & Request Flow - 2026-10-07

**IMPLEMENTED AND VERIFIED / READY FOR OWNER REVIEW. Commit/push approval pending.** This current entry supersedes earlier next-unit/deferred-Request statements only for the owner-approved expression-of-interest slice. BF016 and BF017 remain CLOSED; no next implementation unit is selected.

Authenticated catalog/details/private image delivery show only currently eligible published Listings in the existing authorized Organization hierarchy. Existing Property remains the presentation source. Three explicitly approved canonical capabilities: `published_listings.view`, `requests.create`, `requests.view`. Submission requires catalog view plus create, derives identity/references server-side and permits one submitted Request per user/Listing, with concurrent duplicate protection. Receipts are requester-only, including for System actors, and remain readable after archival subject to current scope. No anonymous, peer-Franchise or unrelated-System access is added; existing private Property/Owner/Ownership boundaries and BF017 administration remain unchanged.

Repository-only migration `038_create_listing_interest_requests_and_permissions.sql` adds `listing_interest_requests`, composite reference foreign keys, lookup/unique indexes, submitted-only check and the three canonical Permission rows. Additional unique reference indexes on existing `listings` and `users` support composite integrity. No Position assignments or business fixtures are included. Migration 038 remains UNAPPLIED to `127.0.0.1:3306/directors_resale_platform`; separate environment preflight, verified backup and explicit owner synchronization approval are required before actual use.

Final gate: all 56 PHP matrix entries PASS, including BF018 (119 focused assertions), BF017, BF013-BF016, Franchise hierarchy security, Operational Activation and service/concurrency/integrated suites. Disposable database and public-file cleanup PASS after every entry. PHP syntax 203 files PASS; frontend typecheck, lint and BF016/BF017/BF018 contracts PASS. Production build PASS through final trace generation and 39 generated pages, from an isolated copy matching 148 frontend source hashes; copied build output and dependency junction cleaned. UTF-8 and changed-file mojibake/replacement scans PASS; no new findings. Historical BF014 document retains HEAD blob `02f651068658df167ca9f62d2a55e8eeca7f98ed` and 4,852 baseline matches; the two fixtures retain exactly 8 + 6 known sequences unchanged. Exact migration manifests now require ordered 001-038; historical catalog assertions exclude only explicitly approved later codes, retaining exact BF014/BF017 counts. One stale BF014 permission-count assertion was proven and corrected before the complete successful rerun.

Real database remains unchanged across all 33 tables, schemas, row counts and data fingerprints. Schema SHA256: `e9d22d56629026c8074976d9ba7a36de00dac1aec1a15c8c9d76a8bdd9e6dfa9`. Data/row-count SHA256: `1d29c8505207501d51def46d901d76f0a18c3073f722d5f18d1f79b217174bf6`. No real login/token, Request, Listing, migration, seed, permission grant, business/demo change or test import occurred. The pre-existing `directors_resale_platform_e1_test_9740` database was excluded and untouched. All new acceptance fixtures were isolated and cleaned.

Deferred: anonymous/global Marketplace/search, Request administration/cancellation/review, Holds, Deals, negotiation, viewing, Contracts, Sale Approval, Commissions, payments, transfers, notifications, full media/galleries/videos/Franchise logos/private documents, completeness/Listing Ready, approval/moderation workflows and full Partner onboarding. Requests never reserve or mutate Listing/Property/Ownership/image data or create downstream business records. Before Git operations, owner approval of the concrete verified handoff is still required by the BF018 request.

### Exact final PHP matrix

| Entry point (tests/Modules/) | Result | Disposable database/public-file cleanup |
|---|---|---|
| `BF018PublishedCatalogRequestAcceptanceTest.php` | PASS | PASS |
| `BF017ListingMvpAcceptanceTest.php` | PASS | PASS |
| `FranchiseAdminHierarchySecurityAcceptanceTest.php` | PASS | PASS |
| `BF016IntegratedAcceptanceTest.php` | PASS | PASS |
| `BF016PrimaryImageHttpIntegrationTest.php` | PASS | PASS |
| `BF016OrganizationBasicProfileHttpIntegrationTest.php` | PASS | PASS |
| `BF016PropertyAdministrativeDetailsHttpIntegrationTest.php` | PASS | PASS |
| `OperationalFranchiseAdminActivationTest.php` | PASS | PASS |
| `BF013AuthorizationTest.php` | PASS | PASS |
| `BF013HttpIntegrationTest.php` | PASS | PASS |
| `BF013DatabaseAcceptanceTest.php` | PASS | PASS |
| `BF014CatalogConfigurationIntegratedAcceptanceTest.php` | PASS | PASS |
| `BF014CatalogMutationHttpTest.php` | PASS | PASS |
| `BF014CatalogReadHttpTest.php` | PASS | PASS |
| `BF014ConfigurationC1HttpTest.php` | PASS | PASS |
| `BF014ConfigurationC2HttpTest.php` | PASS | PASS |
| `BF014ConfigurationC3HttpTest.php` | PASS | PASS |
| `BF014ConfigurationSeedProvenanceTest.php` | PASS | PASS |
| `BF014CoreFormProjectionServiceTest.php` | PASS | PASS |
| `BF014CrossServiceEdgeAcceptanceTest.php` | PASS | PASS |
| `BF014DevelopmentHttpTest.php` | PASS | PASS |
| `BF014DynamicFormProjectionHttpTest.php` | PASS | PASS |
| `BF014DynamicFormProjectionIntegratedAcceptanceTest.php` | PASS | PASS |
| `BF014GeographyDevelopmentHttpIntegratedTest.php` | PASS | PASS |
| `BF014GeographyDevelopmentIntegratedAcceptanceTest.php` | PASS | PASS |
| `BF014GeographyHttpTest.php` | PASS | PASS |
| `BF014HttpIntegratedAcceptanceTest.php` | PASS | PASS |
| `BF014ParentIntegratedAcceptanceTest.php` | PASS | PASS |
| `BF014PermissionFoundationTest.php` | PASS | PASS |
| `BF014ProjectionReadModelSupportTest.php` | PASS | PASS |
| `BF014PropertyFormProjectionServiceTest.php` | PASS | PASS |
| `BF014RepositoryDatabaseAcceptanceTest.php` | PASS | PASS |
| `BF014SchemaAcceptanceTest.php` | PASS | PASS |
| `BF014SeedIntegratedAcceptanceTest.php` | PASS | PASS |
| `BF014SeedPackageCConfigurationTest.php` | PASS | PASS |
| `BF014SeedPackageDGeographyTest.php` | PASS | PASS |
| `BF014SeedPackagesB1Test.php` | PASS | PASS |
| `BF014SeedPackagesB2Test.php` | PASS | PASS |
| `BF014SeedRunnerFoundationTest.php` | PASS | PASS |
| `BF015HttpIntegrationTest.php` | PASS | PASS |
| `BF015IntegratedAcceptanceTest.php` | PASS | PASS |
| `BF015PropertyProfileServiceTest.php` | PASS | PASS |
| `BF015RepositoryDatabaseAcceptanceTest.php` | PASS | PASS |
| `BF015SchemaAcceptanceTest.php` | PASS | PASS |
| `PropertyCatalogServiceDatabaseAcceptanceTest.php` | PASS | PASS |
| `UnitTypeConfigurationC4Test.php` | PASS | PASS |
| `GeographicLocationServiceD1Test.php` | PASS | PASS |
| `DevelopmentCatalogServiceD2Test.php` | PASS | PASS |
| `DevelopmentCatalogD3bConcurrencyTest.php` | PASS | PASS |
| `DevelopmentCatalogD3cConcurrencyTest.php` | PASS | PASS |
| `DevelopmentCatalogD3dConcurrencyTest.php` | PASS | PASS |
| `GeographicLocationD3aConcurrencyTest.php` | PASS | PASS |
| `UnitTypeConfigurationC5aConcurrencyTest.php` | PASS | PASS |
| `UnitTypeConfigurationC5bConcurrencyTest.php` | PASS | PASS |
| `UnitTypeConfigurationC5cConcurrencyTest.php` | PASS | PASS |
| `UnitTypeConfigurationC5dConcurrencyTest.php` | PASS | PASS |

### Exact intended file manifest

- `AI_CONTEXT.md`
- `app/Modules/Listing/Services/ListingService.php`
- `app/Modules/Request/Controllers/PublishedListingController.php`
- `app/Modules/Request/Repositories/ListingInterestRequestRepository.php`
- `app/Modules/Request/Services/ListingInterestRequestService.php`
- `database/migrations/038_create_listing_interest_requests_and_permissions.sql`
- `docs/development/CURRENT_SYSTEM_STATE.md`
- `docs/development/IMPLEMENTATION_PLAN.md`
- `docs/development/SPRINT_LOG.md`
- `docs/MASTER_INDEX.md`
- `docs/sprints/BF018-published-listings-catalog-and-request-flow.md`
- `frontend/app/admin/available-listings/[id]/page.tsx`
- `frontend/app/admin/available-listings/page.tsx`
- `frontend/app/admin/my-requests/page.tsx`
- `frontend/app/api/my-listing-requests/[id]/route.ts`
- `frontend/app/api/my-listing-requests/route.ts`
- `frontend/app/api/published-listings/[id]/primary-image/content/route.ts`
- `frontend/app/api/published-listings/[id]/requests/route.ts`
- `frontend/app/api/published-listings/[id]/route.ts`
- `frontend/app/api/published-listings/route.ts`
- `frontend/components/layout/sidebar.tsx`
- `frontend/features/requests/published-listing-pages.tsx`
- `frontend/lib/api/published-listing.ts`
- `frontend/scripts/bf018-contract-test.cjs`
- `PROJECT_STATUS.md`
- `routes/api.php`
- `tests/Modules/BF013DatabaseAcceptanceTest.php`
- `tests/Modules/BF014PermissionFoundationTest.php`
- `tests/Modules/BF014RepositoryDatabaseAcceptanceTest.php`
- `tests/Modules/BF014SchemaAcceptanceTest.php`
- `tests/Modules/BF015PropertyProfileServiceTest.php`
- `tests/Modules/BF015RepositoryDatabaseAcceptanceTest.php`
- `tests/Modules/BF015SchemaAcceptanceTest.php`
- `tests/Modules/BF017ListingMvpAcceptanceTest.php`
- `tests/Modules/BF018PublishedCatalogRequestAcceptanceTest.php`
