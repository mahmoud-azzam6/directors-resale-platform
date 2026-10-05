# Franchise Admin Hierarchy Permissions — Security and Grant Handoff

Status: **IMPLEMENTED AND VERIFIED / READY** for review on codex-review, 2026-10-05. This bounded unit closes the two approved authorization gaps and applies the approved existing permission links. It does not introduce a new sprint, permission code, migration, or full Partner Agency administrator onboarding. Official-branch merge remains human-owner controlled.

## Approved operational decision

Franchise Admin manages its own Franchise Organization and Basic Profile, its users/positions, and its private Properties/Owners/Ownerships. It may create and administer direct child Partner Agency Organizations and their users/positions/delegable operational permissions. It cannot create/manage peer Franchises, operate on System or unrelated Organizations, delegate outside its hierarchy, or delegate capabilities it does not hold. Existing child-private Profile/Property/Owner/Ownership/image boundaries remain unchanged; administrative hierarchy does not grant private child data access. Existing scope includes own Organization plus immediate children, not an arbitrary nested tree.

## Security fixes

`AuthorizationService::authorizeOrganizationType` gates generic Organization POST/PUT before persistence. System creation and structural type changes require an actor whose persisted Organization has System scope, in addition to the existing route capability and target authorization. Non-System users may retain the existing type and edit ordinary fields, but may not change type to System or transform a child into a Franchise through generic update. Trimming/case variants of System are denied. System-authorized behavior remains available. Own Basic Profile uses the existing organizations.view/update and PRIVATE_ORGANIZATION routes.

Partner Agency PUT retains authorization on the existing child, and separately authorizes a supplied valid parent id before persistence. A new parent outside actor hierarchy returns 403; service validation still requires a valid Franchise parent and rejects malformed/self/System parents. Valid same-parent updates remain available; System-authorized moves between valid Franchise parents remain available. Bare POST /partner-agencies stays unchanged. No peer Franchise onboarding permission is granted.

## Approved real permission change

Target: 127.0.0.1:3306/directors_resale_platform, Position 35 in Organization 80, inherited by existing User 39. All security/regression checks passed before grant execution. A fresh SQL backup of this target was verified: 118,579 bytes, SHA256 `b340475712707f4e430c15bd89913817bd6f7839cdde2d4b9d78a2cb56b4be83`, exact 32 structures and matching INSERT row counts; external filename `directors_resale_platform-before-position35-grants.sql`. No restore rehearsal occurred.

Seven existing assignment rows were preserved verbatim, including ids/timestamps. Sixteen missing links were inserted in one transaction; no replace/delete/recreate operation was used. Global position_permissions rows changed 51 → 67; Position 35 changed 7 → 23. User 39 and Organization 80 rows were not updated.

Excluded: organizations.create/archive, every franchises.* code (especially franchises.create), global_physical_identities.view/manage and property_catalogs.manage. No new canonical catalog code or seed was introduced.

### Before: retained assignments

| Assignment ID | Permission ID | Code |
|---:|---:|---|
| 433 | 23 | properties.view |
| 434 | 24 | properties.manage |
| 436 | 25 | owners.view |
| 437 | 26 | owners.manage |
| 438 | 27 | ownerships.view |
| 439 | 28 | ownerships.manage |
| 435 | 31 | property_catalogs.view |

### After: exact effective assignments

| Assignment ID | Permission ID | Code | Change |
|---:|---:|---|---|
| 440 | 1 | organizations.view | Approved addition |
| 441 | 3 | organizations.update | Approved addition |
| 452 | 9 | partner_agencies.view | Approved addition |
| 453 | 10 | partner_agencies.create | Approved addition |
| 454 | 11 | partner_agencies.update | Approved addition |
| 455 | 12 | partner_agencies.archive | Approved addition |
| 442 | 13 | users.view | Approved addition |
| 443 | 14 | users.create | Approved addition |
| 444 | 15 | users.update | Approved addition |
| 445 | 16 | users.archive | Approved addition |
| 446 | 17 | positions.view | Approved addition |
| 447 | 18 | positions.create | Approved addition |
| 448 | 19 | positions.update | Approved addition |
| 449 | 20 | positions.archive | Approved addition |
| 450 | 21 | permissions.view | Approved addition |
| 451 | 22 | permissions.assign | Approved addition |
| 433 | 23 | properties.view | Retained unchanged |
| 434 | 24 | properties.manage | Retained unchanged |
| 436 | 25 | owners.view | Retained unchanged |
| 437 | 26 | owners.manage | Retained unchanged |
| 438 | 27 | ownerships.view | Retained unchanged |
| 439 | 28 | ownerships.manage | Retained unchanged |
| 435 | 31 | property_catalogs.view | Retained unchanged |

## Verification and database safety

- New focused real-MariaDB security acceptance: PASS, 55 assertions. Own/child type escalation denied atomically; valid Organization/Basic Profile updates succeed; System-authorized create/type changes retain behavior; own-child edits succeed; peer/System/outside/self/malformed parent rejected; System valid reparenting works; bare own-child creation works; private child data remains denied; own-child user/position administration and capability-subset delegation succeed; outside/System-only delegation denied. Fixtures exist only in the guarded disposable target and are removed with verified cleanup.
- Full PHP matrix: PASS, 54 unique entries (listed below), including all BF016 focused/integrated, Operational Franchise Admin Activation, all BF013/BF014/BF015 entry/integrated suites and additional BF014 service/concurrency acceptance. All 154 nested/process cleanup checks passed. Real schema/data fingerprints remained unchanged throughout the test gate. Disposable migrations initialize fresh test databases only; no real migration was rerun.
- PHP syntax: PASS, 195 files. Frontend typecheck, lint and existing BF016 contract tests: PASS. No frontend code changed. Changed-file strict UTF-8, mojibake/replacement scans and git diff checks: PASS. Historical 4,852 BF014-document matches and 14 fixture matches remain untouched baseline findings.
- Live authenticated Apache HTTP: PASS, 48 expected responses (listed below). Normal /auth/login uses the exact ignored local credentials file; credentials, bearer token and token hash are not exposed. /auth/context returns exactly the 23 approved codes. Own Basic Profile GET now returns 200 with null profile. Own Organization/User/Position/Property/Profile/image/Owner/Ownership reads succeed. Anonymous requests return 401; peer/System reads/mutations, peer Franchise creation and outside delegation return 403; invalid authorized edits/child-create requests return 422 without persistence; non-delegable System-only grant returns 422.
- Successful live operational mutations were not exercised. Real Organization 80 currently has no children; successful child creation/update/delegation and private-child denials are proven in disposable acceptance rather than by creating real test records.
- Real structural schema definitions remain unchanged. Thirty unaffected tables match before/after schema/row/data fingerprints. Only approved changes: 16 Position 35 links and one normal-login auth_tokens row (ID 61, User 39), token rows 15 → 16. Every pre-existing assignment/token row remains unchanged. Raw SHOW CREATE TABLE hashes change only from position_permissions AUTO_INCREMENT 440 → 456 and auth_tokens 61 → 62; reconstructed prior DDL hashes match exactly. Retained Organization 58/User 34 and Organization 80/Position 35/User 39/Property 1, profiles/attributes and other business data remain unchanged. No seeds, real migration replay, private image operations or cleanup of retained data occurred.

### Live HTTP results

The mutation requests below contain invalid data or forbidden targets and were verified to persist nothing. Only /auth/login succeeds as a state-changing HTTP request.

| Method | Path | Authenticated | Expected and actual status |
|---|---|---|---:|
| POST | /auth/login | No | 200 |
| GET | /auth/context | Yes | 200 |
| GET | /organizations/80/basic-profile | Yes | 200 |
| GET | /organization-properties/1 | Yes | 200 |
| GET | /organization-properties/1/profile | Yes | 200 |
| GET | /organization-properties/1/primary-image | Yes | 200 |
| GET | /organization-properties/1/primary-image/content | Yes | 404 |
| GET | /organization-properties?organization_id=80 | Yes | 200 |
| GET | /organization-properties?organization_id=58 | Yes | 403 |
| GET | /organization-properties?organization_id=51 | Yes | 403 |
| GET | /organizations/58/basic-profile | Yes | 403 |
| GET | /auth/context | No | 401 |
| GET | /organizations/80/basic-profile | No | 401 |
| GET | /organization-properties/1 | No | 401 |
| GET | /organization-properties/1/profile | No | 401 |
| GET | /organization-properties/1/primary-image | No | 401 |
| GET | /organizations/80 | Yes | 200 |
| GET | /users/39 | Yes | 200 |
| GET | /positions/35 | Yes | 200 |
| GET | /positions/35/permissions | Yes | 200 |
| GET | /users | Yes | 200 |
| GET | /positions | Yes | 200 |
| GET | /partner-agencies | Yes | 200 |
| GET | /permissions | Yes | 200 |
| GET | /owners?organization_id=80 | Yes | 200 |
| GET | /organization-properties/1/ownerships | Yes | 200 |
| PUT | /organizations/80 | Yes | 403 |
| PUT | /organizations/80 | Yes | 422 |
| PUT | /organizations/80/basic-profile | Yes | 422 |
| POST | /partner-agencies | Yes | 422 |
| POST | /partner-agencies | Yes | 403 |
| POST | /partner-agencies | Yes | 403 |
| POST | /franchises | Yes | 403 |
| POST | /organizations | Yes | 403 |
| POST | /network/franchises/onboard | Yes | 403 |
| PUT | /positions/35/permissions | Yes | 422 |
| PUT | /positions/26/permissions | Yes | 403 |
| PUT | /positions/23/permissions | Yes | 403 |
| POST | /property-categories | Yes | 403 |
| GET | /organizations/51 | Yes | 403 |
| PUT | /organizations/51 | Yes | 403 |
| POST | /users | Yes | 403 |
| POST | /positions | Yes | 403 |
| GET | /organizations/58 | Yes | 403 |
| PUT | /organizations/58 | Yes | 403 |
| POST | /users | Yes | 403 |
| POST | /positions | Yes | 403 |
| GET | /users/32 | Yes | 403 |

### Complete regression matrix

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

## Future bounded unit — Partner Agency administrator onboarding

Prepared only; not selected, implemented or included in this security change. Existing POST /partner-agencies creates a bare Organization. A separately approved unit may add atomic creation of one child Partner Agency, its initial Position, an existing-catalog capability subset and one active administrator using the existing password activation service.

Require actor capabilities and parent scope explicitly; accept only the actor's own Franchise parent, never peer/System parents; constrain initial grants to the actor's operational subset and exclude System-only/Franchise-creation capabilities. Reuse existing Organization/Partner/User/Position/Permission services and authentication infrastructure; do not reuse peer Franchise onboarding or introduce a parallel architecture. Preserve private child data boundaries. Validate password/confirmation and never return plaintext/hash; rollback the whole provisioning aggregate on failure.

Separate acceptance must prove authorized complete provisioning/login, outside-parent and non-delegable-grant rejection, no partial records on validation/persistence failure, credential privacy, and existing hierarchy/private-scope regressions using isolated data. API/UI details and any canonical documentation reconciliation belong to that separately approved unit. No Partner administrator onboarding route or implementation is added here.
