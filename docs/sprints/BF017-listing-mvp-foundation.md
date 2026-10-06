# BF017 — Listing MVP Foundation

**Status: IMPLEMENTED AND VERIFIED / CLOSED — READY for review**, 2026-10-06. The contract below was recorded before implementation.

## Approved bounded decisions

Owner clarification explicitly approves canonical `listings.view` and `listings.manage`, independent of Property capabilities, and direct `draft -> published`. Internal approval, Owner approval, moderation and versioned publication workflows remain deferred. This scoped administrative MVP does not implement the broader Listing architecture or a complete Listing Ready/completeness engine. BF016 and its units remain CLOSED. Partner Agency operational/full onboarding is deferred; existing Organizations provide sufficient Listing ownership context.

## Identity, scope and persistence

Listing references one existing Organization Property with an immutable owning Organization derived from that Property. No Property identity, code, address, price, image or ownership data is copied into Listing storage. Use existing HIERARCHY authorization: System with the appropriate capability retains platform-wide support, Franchise own plus direct authorized children, Partner own only. Peer/System resources remain denied to Franchise and Partner actors. A narrowly scoped Listing projection/image endpoint exposes required presentation data within this authorized Listing scope; existing PRIVATE_ORGANIZATION Property/Profile/Owner/Ownership/image endpoints remain unchanged. No Owner contact data is returned.

One non-archived Listing per Organization Property (including draft) is enforced by a generated unique database guard and Property lock. Duplicate Property display names remain valid. Listing stores ULID, Property/Organization reference, status, revision, lifecycle/audit timestamps and actors only. Archived Listings retain history; no reactivation or hard deletion in this MVP. Listing transitions lock Property then Listing and validate the supplied revision. Conflicts return 409; client retains local choice and requires explicit reload/retry, never silently resubmits.

## Publication gate

Draft creation requires an active Property in an active owning Franchise/Partner Organization. Publication requires that Property, generated non-empty code, non-empty address text and valid active canonical geography ancestry (existing validator with valid skips), positive initial asking price and supported EGP/USD/SAR/AED currency, an existing readable private primary WebP image, and current Ownership with at least one non-archived same-Organization Owner party. Validate under the Property aggregate lock. Gate results are current checks, not permanent completeness truth; later Property changes remain authoritative and do not automatically change Listing state. Publication/archive must never mutate Property, Profile, Ownership, image or future workflow records.

## HTTP and frontend

Protected GET/POST `/listings`, GET `/listings/{id}`, POST `/listings/{id}/publish`, POST `/listings/{id}/archive`, GET `/listings/{id}/primary-image/content`. `listings.view` protects reads including images; `listings.manage` protects writes. Creation accepts `organization_property_id` only; lifecycle requests require `revision`. Actor/Organization/status/audit fields are server-owned. List optional Organization filter is authorized and all results filtered at persistence boundary. Lists/details return live Property projection and owning Organization, never storage keys or Owner personal information.

Protected Arabic-first `/admin/listings` and `/admin/listings/[id]` compose the existing admin layout/UI primitives and server cookie API bridge. List supports draft creation by existing Property id without requiring Property permissions. Details shows Property code, label, primary image, canonical address, price/currency, owning Organization, status and revision. Show loading, empty, 401/403, validation and explicit conflict retry states. Backend remains authoritative.

## Migration and verification gate

Repository migration 037 only: `037_create_listings_table_and_permissions.sql`, dedicated Listing table/indexes/constraints and exactly the two approved catalog codes. No Position grants, business/demo data, or other seeds. Update regression exact ordered filename expectations to 001–037 without accepting arbitrary extras; historical catalog checks isolate expected pre-Listing codes. Do not apply 037 or any other migration to directors_resale_platform. Test setup must use explicitly guarded disposable MariaDB names; cleanup verified after each process. Record read-only real fingerprints before/after.

Required acceptance: separate capability/scope denial, Franchise and Partner draft creation, child hierarchy, every missing/invalid publication prerequisite, readable image gate, current valid ownership, direct publish/archive, duplicate-active prevention including database constraint, duplicate names, live Property projection, revision conflict with no changes, no aggregate/future workflow side effects, schema/index/FK/check verification and cleanup. Run complete BF013–BF016 regression matrix, existing hierarchy security acceptance, PHP syntax, frontend typecheck/lint/contracts/production build through final trace, diff/strict UTF-8/changed-file mojibake scans and baseline preservation.

No real migrations, data changes, credentials/token insertion, or real login are required. No Marketplace/search, Requests, Deals, Commissions, payments, notifications, galleries/media platform, approvals or Partner onboarding. Thursday demo target is 2026-10-08; passing implementation does not authorize real schema synchronization.

### BF017 final verification evidence

All 55 unique PHP matrix entry points passed, including focused BF017, existing Franchise Admin hierarchy security, Operational Activation and the complete established BF013–BF016 entry/integrated/service/concurrency matrix. Latest BF017 acceptance passed 103 assertions plus guarded database/file cleanup checks. All 55 matrix entries passed post-process database inventory and temporary public HTTP-file cleanup; internal suites also execute their existing cleanup. The pre-existing e1_test_9740 database remained untouched. No new disposable database or uploaded file remained.

PHP syntax: 199 files PASS. Frontend typecheck, lint, BF016 contract suite and new BF017 component hydration/capability/error/API suite PASS. Production build PASS through final trace collection and 35 static pages in an isolated copy matching all 134 frontend source hashes; the running development server was left intact. Strict UTF-8 passed for all 480 repository text files; all 38 changed-file mojibake/replacement and git diff checks PASS. Historical BF014 document retains HEAD blob 02f651068658df167ca9f62d2a55e8eeca7f98ed and its 4,852 known baseline matches; the two BF014 fixtures retain exactly eight plus six baseline sequences, with no new findings.

MariaDB is available at 127.0.0.1:3306; .env resolves exactly directors_resale_platform via C:\xampp\php\php.exe and C:\xampp\php\php.ini. Before/after all 32 real table definitions, row counts and data fingerprints match. Aggregate schema SHA256: 8d19ef5feaed9c7205d404aeec18cde6cac8402e1eab84065cde2d60ad537b45. Aggregate data/row-count SHA256: 2f77070383a15a16719b4153779299e45ab15cc14eb5303fd6d71964a92f0311. No real login/token insertion, Position grant, seed, migration, business/demo/test data or image change occurred. Migration 037 remains unapplied, and neither new Listing capability is granted to real Positions by this task.

Two proven stale regression expectations were reconciled: BF016 no-side-effect checks now require an empty Listing table while preserving deferred-domain table absence; BF014 excludes future domains but permits the six owner-approved Listing routes, whose exact registered surface is verified by BF017. Exact ordered migration manifests extend 001–036 to 001–037 without accepting arbitrary extras. Historical BF014 catalog count checks still require exactly 32 pre-Listing codes; focused BF017 requires exactly 34 total codes and the exact two active Listing capabilities. An initial new-test float/int strict comparison was corrected; latest acceptance and the complete final matrix pass. No security boundary was relaxed.

READY means bounded implementation/verification ready for Architect review. Actual demo use against the real database requires separate explicit schema synchronization and grant approval, then eligible existing Property data. No next implementation unit is selected; Partner onboarding remains deferred. BF016 stays CLOSED.

## Complete PHP matrix

- BF017ListingMvpAcceptanceTest.php — PASS
- FranchiseAdminHierarchySecurityAcceptanceTest.php — PASS
- BF016IntegratedAcceptanceTest.php — PASS
- BF016PrimaryImageHttpIntegrationTest.php — PASS
- BF016OrganizationBasicProfileHttpIntegrationTest.php — PASS
- BF016PropertyAdministrativeDetailsHttpIntegrationTest.php — PASS
- OperationalFranchiseAdminActivationTest.php — PASS
- BF013AuthorizationTest.php — PASS
- BF013HttpIntegrationTest.php — PASS
- BF013DatabaseAcceptanceTest.php — PASS
- BF014CatalogConfigurationIntegratedAcceptanceTest.php — PASS
- BF014CatalogMutationHttpTest.php — PASS
- BF014CatalogReadHttpTest.php — PASS
- BF014ConfigurationC1HttpTest.php — PASS
- BF014ConfigurationC2HttpTest.php — PASS
- BF014ConfigurationC3HttpTest.php — PASS
- BF014ConfigurationSeedProvenanceTest.php — PASS
- BF014CoreFormProjectionServiceTest.php — PASS
- BF014CrossServiceEdgeAcceptanceTest.php — PASS
- BF014DevelopmentHttpTest.php — PASS
- BF014DynamicFormProjectionHttpTest.php — PASS
- BF014DynamicFormProjectionIntegratedAcceptanceTest.php — PASS
- BF014GeographyDevelopmentHttpIntegratedTest.php — PASS
- BF014GeographyDevelopmentIntegratedAcceptanceTest.php — PASS
- BF014GeographyHttpTest.php — PASS
- BF014HttpIntegratedAcceptanceTest.php — PASS
- BF014ParentIntegratedAcceptanceTest.php — PASS
- BF014PermissionFoundationTest.php — PASS
- BF014ProjectionReadModelSupportTest.php — PASS
- BF014PropertyFormProjectionServiceTest.php — PASS
- BF014RepositoryDatabaseAcceptanceTest.php — PASS
- BF014SchemaAcceptanceTest.php — PASS
- BF014SeedIntegratedAcceptanceTest.php — PASS
- BF014SeedPackageCConfigurationTest.php — PASS
- BF014SeedPackageDGeographyTest.php — PASS
- BF014SeedPackagesB1Test.php — PASS
- BF014SeedPackagesB2Test.php — PASS
- BF014SeedRunnerFoundationTest.php — PASS
- BF015HttpIntegrationTest.php — PASS
- BF015IntegratedAcceptanceTest.php — PASS
- BF015PropertyProfileServiceTest.php — PASS
- BF015RepositoryDatabaseAcceptanceTest.php — PASS
- BF015SchemaAcceptanceTest.php — PASS
- PropertyCatalogServiceDatabaseAcceptanceTest.php — PASS
- UnitTypeConfigurationC4Test.php — PASS
- GeographicLocationServiceD1Test.php — PASS
- DevelopmentCatalogServiceD2Test.php — PASS
- DevelopmentCatalogD3bConcurrencyTest.php — PASS
- DevelopmentCatalogD3cConcurrencyTest.php — PASS
- DevelopmentCatalogD3dConcurrencyTest.php — PASS
- GeographicLocationD3aConcurrencyTest.php — PASS
- UnitTypeConfigurationC5aConcurrencyTest.php — PASS
- UnitTypeConfigurationC5bConcurrencyTest.php — PASS
- UnitTypeConfigurationC5cConcurrencyTest.php — PASS
- UnitTypeConfigurationC5dConcurrencyTest.php — PASS

## Changed files

- `AI_CONTEXT.md`
- `app/Modules/Listing/Controllers/ListingController.php`
- `app/Modules/Listing/Repositories/ListingRepository.php`
- `app/Modules/Listing/Services/ListingService.php`
- `app/Providers/AppServiceProvider.php`
- `database/migrations/037_create_listings_table_and_permissions.sql`
- `docs/architecture/LISTING_DOMAIN_ARCHITECTURE.md`
- `docs/architecture/LISTING_PHYSICAL_DATA_MODEL.md`
- `docs/database/DATABASE_CHANGELOG.md`
- `docs/development/CURRENT_SYSTEM_STATE.md`
- `docs/development/IMPLEMENTATION_PLAN.md`
- `docs/development/SPRINT_LOG.md`
- `docs/MASTER_INDEX.md`
- `docs/sprints/BF017-listing-mvp-foundation.md`
- `frontend/app/admin/listings/[id]/page.tsx`
- `frontend/app/admin/listings/page.tsx`
- `frontend/app/api/listings/[id]/archive/route.ts`
- `frontend/app/api/listings/[id]/primary-image/content/route.ts`
- `frontend/app/api/listings/[id]/publish/route.ts`
- `frontend/app/api/listings/[id]/route.ts`
- `frontend/app/api/listings/route.ts`
- `frontend/components/layout/sidebar.tsx`
- `frontend/features/listings/listing-pages.tsx`
- `frontend/lib/api/listing.ts`
- `frontend/scripts/bf017-contract-test.cjs`
- `PROJECT_STATUS.md`
- `routes/api.php`
- `tests/Modules/BF013DatabaseAcceptanceTest.php`
- `tests/Modules/BF014HttpIntegratedAcceptanceTest.php`
- `tests/Modules/BF014PermissionFoundationTest.php`
- `tests/Modules/BF014RepositoryDatabaseAcceptanceTest.php`
- `tests/Modules/BF014SchemaAcceptanceTest.php`
- `tests/Modules/BF015PropertyProfileServiceTest.php`
- `tests/Modules/BF015RepositoryDatabaseAcceptanceTest.php`
- `tests/Modules/BF015SchemaAcceptanceTest.php`
- `tests/Modules/BF016PrimaryImageHttpIntegrationTest.php`
- `tests/Modules/BF016PropertyAdministrativeDetailsHttpIntegrationTest.php`
- `tests/Modules/BF017ListingMvpAcceptanceTest.php`
