# BF016 — Organization and Property Setup Data Foundation

**Status:** IN PROGRESS / OPEN — BF016.2/BF016.3/BF016.4 IMPLEMENTED AND VERIFIED
**Current internal unit:** BF016.4 — Property Administrative Details Verification and Formal Acceptance — IMPLEMENTED AND VERIFIED / READY
**Depends on:** BF013, BF014, BF015, AF004

**Last updated:** 2026-10-05. The BF016 parent remains open until BF016.5–BF016.6 are completed. No next internal unit is selected by this handoff.

## Purpose

BF016 was introduced from AF004 manual-testing findings and the real-development-database schema drift issue. It defines the smallest approved foundation for private Organization basic-profile data and later Property administrative details. It preserves the separation of Organization Property, Ownership, BF015 Profile, Listing, Offer, Commission, and media domains.

## BF016.1 — Franchise Basic Profile Contract

Architecture and contract only. A Franchise may have an optional canonical `geographic_locations` reference and optional address text held in a dedicated one-to-one Organization basic-profile aggregate. Location is canonical and active; address text is not a geography identity.

System Administrators may read and update a Franchise profile. A Franchise Administrator may read and update only its own Organization profile. The contract uses `organizations.view` and `organizations.update` only if their existing semantics remain appropriate, always with `PRIVATE_ORGANIZATION` scope. No new capability is approved by this contract.

No logo upload, documents, contracts, tax records, licenses, verification, expiry, or public Organization profile is included.

## BF016.2 — Franchise Basic Profile Implementation

**Status: IMPLEMENTED AND VERIFIED** — verification and documentation handoff recorded on 2026-10-05.

Delivered optional Franchise onboarding location/address input, a dedicated one-to-one Organization basic-profile store, private scoped read/update endpoints, and a minimal Franchise Administrator UI using the existing Admin foundation. Backend authorization remains authoritative. Organization profile address text does not implement Property address or price, and profile saves do not create Listing side effects.

### Verification evidence

All **51 required test/regression entry points passed** on real MariaDB 10.4.32, including nested integrated dependencies:

| Verification | Result |
|---|---|
| BF016 focused real-MariaDB acceptance | PASS (2/2): Organization profile and Property administrative details, atomicity, validation, scope, privacy and rollback |
| Operational Franchise Admin Activation | PASS (1/1) |
| BF013 authorization, HTTP and database acceptance | PASS (3/3) |
| BF014 entry points and integrated suites | PASS (28/28) |
| Additional BF014 service/concurrency tests | PASS (12/12) |
| BF015 entry points and integrated suite | PASS (5/5) |
| PHP syntax | PASS (190 files) |
| Frontend typecheck, lint, contract acceptance and build | PASS; lint reported no warnings/errors; production build completed final trace collection |
| git diff --check and strict UTF-8 validation | PASS |

Focused evidence: [BF016 Organization Basic Profile HTTP integration](../../tests/Modules/BF016OrganizationBasicProfileHttpIntegrationTest.php) and [Operational Franchise Admin Activation](../../tests/Modules/OperationalFranchiseAdminActivationTest.php). BF013/BF014/BF015 migration acceptance now requires the explicit ordered repository filenames 001–035, with strict list equality and no arbitrary extra migrations accepted. Migration execution remains in repository order.

All 147 PHP-process cleanup checks passed, including nested suites, seed CLI processes and concurrency workers. Disposable databases remained isolated; no newly created test database remained. The pre-existing `directors_resale_platform_e1_test_9740` was left untouched. No real development database schema or data was modified: read-only before/after schema, row-count and data fingerprints matched across all 30 existing real tables. Migrations 034 and 035 remain unapplied to the real database, where `organization_basic_profiles` remains absent.

The encoding scan found **14 pre-existing mojibake sequences in two BF014 fixtures**: eight in `BF014RepositoryDatabaseAcceptanceTest.php` and six in `BF014SchemaAcceptanceTest.php`. These fixture sequences match `HEAD` and were not changed by this work. No invalid UTF-8 or replacement characters were found, and no new mojibake was introduced. This is not a claim that the two existing fixtures are free of mojibake.

### Migration 034 synchronization plan — not executed

- Exact target: `directors_resale_platform` on `127.0.0.1:3306`. Reconfirm `.env`, resolved configuration, server identity and `SELECT DATABASE()` before synchronization.
- Apply **migration 034 only**: `database/migrations/034_create_organization_basic_profiles_table.sql`, once and only if still missing. Do not rerun already-applied migrations. There is **no canonical seed impact** and no seed package to apply.
- Preflight must reconcile migrations 001–033 with the real schema and any existing migration ledger, confirm the target table is absent, inspect referenced Organization/User/Geography keys and engines, check privileges and application boot, and record the reviewed SQL/checksum and baseline schema/data. Stop on conflicts, collisions or uncertainty.
- A **verified backup is required** before any apply: record the absolute backup path, successful exit status, size and SHA-256; verify recovery by restoring to a separately named isolated recovery database and comparing schema/data. No backup has been taken or verified by this handoff.
- **Explicit user approval is required** for the exact database synchronization operation after the preflight report and verified backup evidence are available. Implementation verification does not authorize synchronization.
- Exclude all test, acceptance, demo and fixture data, temporary users/properties/owners/tokens, test-generated business records and disposable databases. Do not copy any such records into the real database.
- Post-apply checks must verify columns/types/nullability/defaults, the primary key, unique Organization key, Geography index, nonblank-address CHECK, four restricted foreign keys, InnoDB/utf8mb4 settings, an empty new table, unchanged existing data/canonical references, application boot, and authenticated profile reads plus unauthorized/out-of-scope denial using approved existing accounts. Keep mutation acceptance isolated.
- Produce a before/after report with the target database, verified backup path/evidence, migration applied, no seeds, excluded records, verification results and remaining differences. MariaDB DDL may implicitly commit; transaction rollback cannot undo completed DDL.

**Synchronization status: NOT EXECUTED / migrations 034 and 035 UNAPPLIED to `directors_resale_platform`.** The migration-034 plan above remains limited to that migration. A subsequent explicitly approved combined synchronization must apply 034 then 035, never seeds or test data. For 035, preflight must verify the existing Property/Profile tables, immutable ULIDs, code uniqueness and price/currency constraints against existing rows. Post-apply must verify the generated persisted code and scoped unique index, nullable address/price/currency columns and both CHECK constraints, preserved Profile revisions/typed values and unchanged existing business data. The same verified-backup, explicit-approval and stop-on-conflict requirements apply. No database synchronization is authorized by this review handoff.

## BF016.3 — Property Administrative Details Implementation

**Status: IMPLEMENTED AND VERIFIED.** The bounded contract below was recorded before implementation. It delivers an immutable backend-generated Property Code, canonical geographic selection plus separate address text, and an initial asking price with currency through the existing BF015 aggregate. These are not typed Profile values, Listing price, Offer, Commission input, or Listing Ready evidence. No image upload is included.

### Bounded execution contract — 2026-10-05

The user-authorized unit completed the BF016.2 company address experience and these BF016.3 administrative details. All required verification passed; this selection does not close BF016.5–BF016.6 or the BF016 parent.

- Migration 035 extends existing aggregates only. `organization_properties.property_code` is the persisted generated value `PROP-` plus the immutable existing ULID, with an Organization-scoped unique index. Existing and new properties receive deterministic codes; display labels remain nonunique. Clients cannot supply or change codes. ULID uniqueness prevents collisions without truncated names, race-prone counters or retries.
- Extend the existing BF015 Profile GET/PUT contract with optional `address_text`, `initial_asking_price` and `currency_code`; reuse its canonical `geographic_location_id`, property lock, atomic transaction and `expected_revision`. Omitted fields preserve saved data and typed values. Explicit null clears optional fields; price and currency must both be null or both supplied. No second Property aggregate or parallel revision is introduced.
- Address text is trimmed, nonblank when supplied, and at most 1000 Unicode characters. Canonical geography must have an active COUNTRY root and active, linked ancestors with strictly increasing COUNTRY/GOVERNORATE/CITY/AREA/DISTRICT ranks. Legitimate skipped levels are allowed. Fractional identifiers, missing/inactive nodes, cycles and non-root/wrong ancestry are rejected.
- Asking price uses exact `DECIMAL(18,4)` semantics (up to 14 integer digits, four fractional digits), accepts integer or decimal-string input, and must be positive. Supported currency codes are the explicit uppercase allowlist `EGP`, `USD`, `SAR`, `AED`; EGP is the initial UI default. No conversion, Commission input, Listing price or commercial workflow is implemented.
- Reuse `properties.view`/`properties.manage` and PRIVATE_ORGANIZATION Profile scope. Parent Franchise access does not grant child Partner private Profile access. System access follows existing authorization. No permission is added.
- Extend the AF004 Property Data and read-only review UI with the generated code, canonical dependent selector and ancestry hydration, street address, price and currency. Preserve BF015 patch behavior and local input on conflicts; retry is explicit after fetching the latest revision. No numeric-ID address input is offered.
- The only image behavior is a labelled generic UI placeholder with no persisted record, reference, URL or upload. Full media, galleries/video and private documents remain deferred. Property setup is not completeness or Listing Ready.
- For BF016.2, System users choose a Franchise through the existing authorized Organization catalog; Franchise users edit their own profile. The existing Organization name is updated only through the private basic-profile transaction and existing `organizations.update` permission. Empty reads retain `{ profile: null }`; no new legal-name model is invented.
- Migration acceptance must require the exact repository filenames 001–035, sorted and executed in repository order. Migrations 034/035 may run only on isolated disposable test databases. Real `directors_resale_platform` synchronization remains prohibited in this unit.

## BF016.4 — Property Administrative Details Verification and Formal Acceptance

**IMPLEMENTED AND VERIFIED / READY.** User-authorized reconciliation defines BF016.4 as verification and formal acceptance of the existing BF016.3 implementation. The complete rerun and acceptance evidence are recorded below; no duplicate implementation or migration was created.

## BF016.5 — Private Media Foundation Architecture

**OPEN.** The minimum one-primary-property-image foundation remains deferred to this unit. It defines future authorized logo and primary-image storage, including the distinction between `USER_UPLOAD` and `SYSTEM_PLACEHOLDER`. It does not implement storage, uploads, delivery, media records, or placeholder persistence.

## BF016.6 — Acceptance and Closure

**OPEN.** Final BF016 closure requires the remaining approved foundation and final cross-module acceptance; the current BF016.4 evidence does not close BF016.6.

## Deferred boundaries

BF016.2 does not implement Organization logo/media, persisted Property images, documents/contracts, galleries/videos, Listing, Marketplace, Requests, Deals, Commissions, completeness or Listing Ready. Commercial registration, tax records, licenses, verification, public profiles, Offers and transfers also remain deferred. Property setup remains separate from Listing readiness. BF016.5–BF016.6 remain pending; the BF016 parent is not closed.

### Historical BF016.2/BF016.3 review evidence — 2026-10-05

Company profile now supports existing Organization name, System Franchise selection, private own-Franchise editing, optional street address and protected five-level canonical geography with legitimate skips, safe empty reads and ancestry hydration. Property Data/review now expose an immutable persisted `PROP-<ULID>` code, canonical address, optional positive DECIMAL(18,4) asking price with explicit EGP/USD/SAR/AED currency, and a labelled generic image placeholder without storage. Existing BF015 typed-value patches, atomic revisions and explicit conflict retry are preserved. Authorization remains backend-owned; no permission codes or Listing side effects were added.

Verification: 51/51 PHP matrix entry points passed (two focused BF016 acceptances, Operational Franchise Admin Activation, three BF013, 28 BF014 entry/integrated suites, 12 additional BF014 service/concurrency tests, five BF015). All 147 process cleanup checks passed. PHP syntax passed for 190 files. Frontend typecheck, lint, the BF016 canonical-geography/empty-profile/hydration/error contract acceptance and production build passed. The build completed final trace collection in an identical isolated source copy with shared installed dependencies because the running development server held the original `.next` trace; the development server was left running. Strict UTF-8 and diff checks passed. The 14 pre-existing mojibake sequences in the two BF014 fixtures match HEAD and were not changed; no new mojibake or replacement characters were introduced.

MariaDB remained available at 127.0.0.1:3306; configuration targeted exactly directors_resale_platform. Read-only before/after schema, row-count and data fingerprints matched for all 30 real tables. All newly created disposable databases were removed; the pre-existing directors_resale_platform_e1_test_9740 remained untouched. Repository migrations 001–035 are verified by exact ordered filename equality. Migrations 034 and 035 remain unapplied to the real database. Future synchronization requires a reviewed preflight, verified backup, explicit user approval, 034 then 035 only, no canonical seed impact, exclusion of all test/acceptance/demo/fixture data, and post-apply schema/index/constraint, application and unchanged-business-data checks. No real schema/data operation was performed.

BF016.2 and BF016.3 are IMPLEMENTED AND VERIFIED / READY for review on codex-review. BF016 parent remains OPEN; BF016.4–BF016.6 remain OPEN / PENDING and no next unit is selected. Full media/logo/image persistence, galleries/videos, private documents/contracts, Listing, Marketplace, Requests, Deals, Commissions, completeness and Listing Ready remain deferred. Official-branch merge remains controlled by the human owner.

## BF016.4 — Verification Evidence

**Status: IMPLEMENTED AND VERIFIED / READY**, verified 2026-10-05 on codex-review against implementation commit `c06aa78600aabf22aaeefef017c10ba3f3695e84`.

The user reconciled BF016.3 as implementation and BF016.4 as verification, proven-gap fixing, regression evidence and formal acceptance. Existing Property Code, canonical address/geography and initial asking price/currency were inspected and accepted through the existing BF013/BF015 backend and AF004 frontend. No implementation gap was proven; no application code, test, permission, field or migration was added or changed by BF016.4.

| Verification | Result |
|---|---|
| BF016.2 focused MariaDB acceptance | PASS (1/1) |
| BF016.3 Property administrative details MariaDB acceptance, reused for BF016.4 | PASS (1/1) |
| Operational Franchise Admin Activation | PASS (1/1) |
| BF013 authorization, HTTP and database | PASS (3/3) |
| BF014 full entry points and integrated suites | PASS (28/28) |
| Additional BF014 service/concurrency tests | PASS (12/12) |
| BF015 full entry points and integrated suite | PASS (5/5) |
| PHP syntax | PASS (190 files) |
| Frontend typecheck / lint / existing BF016 contract acceptance | PASS / PASS / PASS |
| Production build including final trace collection | PASS; 33 static pages; isolated source-identical copy, 118 matching source hashes |
| Strict UTF-8 / replacement scan / git diff --check | PASS; no introduced mojibake |
| Disposable database process cleanup | PASS (146/146), including nested suites and workers; no new residue |
| Real database schema, row-count and data fingerprints | PASS; unchanged across all 30 tables |

The focused acceptance proves persisted immutable `PROP-<ULID>` codes, the Organization/code unique index and duplicate display names; five-level canonical address round-trip and valid skips; invalid, inactive, cyclic and wrong hierarchy rejection; positive exact-decimal price/currency and invalid-input atomic rejection; BF015 typed-value preservation; stale-revision denial; own/System success and anonymous, unrelated and parent-to-child private Profile denial. Existing schema foreign keys and root CHECK constraints prevent broken/non-Country-root records; the validator rejects ancestry that does not terminate at an active COUNTRY root. Existing frontend contract acceptance exercises canonical saved ancestry and error states. Direct Property Data/review inspection confirms editable address/price/currency, immutable code display, effect-based saved-value hydration, dirty-state preservation, typed-value patch guards and explicit retry using the fetched current revision. This evidence does not claim an interactive browser automation run.

MariaDB 10.4.32 was available at 127.0.0.1:3306 and .env targeted exactly directors_resale_platform. Migration setup used isolated disposable targets only, never the real database. Migrations 034 and 035 remain unapplied to the real database; organization_basic_profiles remains absent. No real schema/data changes or fixture imports occurred. The only remaining disposable-name database is the pre-existing directors_resale_platform_e1_test_9740, left untouched. Synchronization still requires the recorded preflight, verified backup, exact migration list and explicit user approval. There is no canonical seed impact.

The 14 pre-existing mojibake sequences remain unchanged: eight in BF014RepositoryDatabaseAcceptanceTest.php and six in BF014SchemaAcceptanceTest.php. No invalid UTF-8 or replacement characters were found in the 44 implementation/handoff files or the updated canonical documentation.

BF016.3 retains the implementation record. BF016.4 is formally accepted / READY. BF016.5 remains OPEN for the minimum one-primary-property-image foundation; the current generic UI placeholder is not a persisted image and does not meet that future requirement. BF016.6 remains OPEN for final BF016 closure. BF016 parent remains OPEN. Media persistence, galleries/video, Franchise logo, documents, Listing, Marketplace, Requests, Deals, Commissions, completeness and Listing Ready remain deferred. No next unit is selected and no official-branch merge is performed.
