# Database Changelog

## BF014.1 - Database Schema + Integrity Constraints

Status: Implemented and verified on 2026-09-13; BF014 remains in progress, not complete

- Additive migrations `018` through `030` create exactly `property_categories`, `unit_types`, `unit_type_configuration_versions`, `measurement_definitions`, `unit_type_measurement_rules`, `attribute_definitions`, `attribute_options`, `unit_type_attribute_rules`, `geographic_locations`, `developers`, `projects`, `project_phases`, and `property_catalog_seed_versions`.
- Migrations `001` through `017` remain unchanged. No baseline catalog rows or BF014 Permission capabilities are inserted.
- Ten public catalog/configuration entities use application-generated ULIDs. Internal Measurement/Attribute rule rows and the seed ledger omit ULIDs under the approved BF014 exception.
- Catalog/configuration/rule actor FKs are nullable: NULL denotes system/seed operations; non-NULL values reference human actors through `users.id`. The seed ledger follows the same rule for `applied_by_user_id`; no dedicated System User is required. This is the locked BF014 actor exception to generic audit guidance.
- Catalog statuses are `active`/`inactive`; configuration statuses are `draft`/`active`/`historical`. Catalogs and configurations distinguish `SYSTEM_SEED` from `SYSTEM_ADMIN`; rules inherit provenance from their configuration.
- Named unique indexes, CHECKs, and two nullable PERSISTENT guards enforce unique version/rule identities, at most one active configuration per Unit Type, at most one Primary Measurement per configuration, primary-implies-REQUIRED, permitted types/statuses, SQM-only V1 measurements, typed text limits, geographic root shape, scoped option/Phase codes, and seed-key/checksum validity.
- All 36 new foreign keys use `ON DELETE RESTRICT ON UPDATE RESTRICT`. Tables preserve InnoDB, utf8mb4, and utf8mb4_unicode_ci; code trim/uppercase normalization remains a future Service responsibility.
- Configuration transitions, published structural immutability, non-reused version numbers, seeded hard-delete prohibition, safe deletion, ENUM parent eligibility, and geography self-parent/cycle rejection remain future Service invariants, not claims of DB enforcement. Future Property measurements retain the DECIMAL(18,4), decimal-string contract without a value table in this unit.
- `tests/Modules/BF014SchemaAcceptanceTest.php` applied migrations 001-030 to an isolated MariaDB 10.4.32 database and verified new-table existence, zero initial rows, FK restrictions, invalid CHECK values, nullable/invalid actors, uniqueness guards on insert/update, rollback, duplicate rules/versions/codes, flexible geography, and seed-ledger constraints. All 13 new tables were empty after migration; the existing Permission count remained 30.
- The BF013 database acceptance migration manifest now includes 018-030 so its existing domain/HTTP/authorization/legacy regression assertions run against the extended schema. That suite and all eight other existing test scripts passed; new-test PHP syntax validation passed.
- The configured XAMPP data directory failed startup with an existing InnoDB corruption error. Verification used the same MariaDB 10.4.32 binaries with a fresh temporary data directory instead; no repair was attempted. Disposable acceptance databases were dropped after verification.
- BF014.2 and later units are not implemented. Catalog governance activity persistence remains deferred within BF014 until mutation/service integration is designed. Repositories, services, controllers, routes, permission implementation, seeds, Property values, proposals, completeness, media, frontend, and Listing are outside this unit. This entry is not BF014 documentation closure.

## Rich Property Profile and Property Catalog Architecture

Status at architecture approval: Approved; not implemented. BF014.1 schema implementation is recorded above.

- The conceptual Rich Property Profile and Property Catalog/Data Governance physical model is approved as additive future work over the implemented BF013 schema.
- No Rich Property Profile or Property Catalog tables, migrations, seed scripts, APIs, or frontend workflows have been created.
- Schema migration and seed execution are separate concerns. `docs/database/SEED_DATA.md` defines only the approved versioned, idempotent, non-destructive seed strategy.
- Proposed baseline canonical data includes common categories, Unit Types, Measurements, Attributes/configurations, Egypt, and its 27 governorates; authoritative Developer/Project data must come from a separately approved business dataset rather than being invented.

## BF013 - Property & Ownership Foundation

Status: Implemented and verified

- Migrations `009` through `016` add `organization_properties`, `owners`, `ownerships`, `ownership_parties`, `authorized_acting_owner_designations`, `global_physical_property_identities`, `global_physical_identity_links`, and `property_owner_lifecycle_history`.
- Migration `017_add_bf013_permissions.sql` adds eight active Permission codes: `properties.view`, `properties.manage`, `owners.view`, `owners.manage`, `ownerships.view`, `ownerships.manage`, `global_physical_identities.view`, and `global_physical_identities.manage`.
- The active Permission catalog contains 30 codes after migration 017.
- Application-generated canonical ULIDs use the reusable `UlidGeneratorInterface` backed by Symfony UID 5.4.
- Generated nullable uniqueness guards enforce at most one current Ownership per Organization Property, one current Acting Owner designation per Ownership, and one active Global Identity link per Organization Property.
- Foreign keys, `CHECK` constraints, share invariants, lifecycle history, historical designation/link preservation, and transactional rollback were verified on MariaDB 10.4.32.
- Migrations `001` through `017`, including PDO multi-statement execution, passed database-backed acceptance; isolated test cleanup reported `remaining_bf013_test_databases=0`.
- Listing schema, rich Property Profile/catalog data, automatic matching, transfer behavior, and frontend Property workflows remain deferred.

## BF012 - Permissions & Authorization

Status: Implemented and verified

- Migration: `database/migrations/008_create_permissions_tables.sql`
- Added globally unique, status-based `permissions` and composite-unique `position_permissions`.
- Seeded the original 22 approved active Permission codes for Organizations, Franchises, Partner Agencies, Users, Positions, and Permission administration; BF013 migration 017 later expands the active catalog to 30.
- Users inherit active Permissions only through an active same-Organization Position; no `user_permissions` table exists.
- Position-Permission replacement is atomic; relationship rows represent current assignments and may be synchronized without hard-deleting Position or Permission business records.
- Full audit/event history for Permission assignment remains deferred to the staged audit architecture.

## BF011 - Dynamic Positions

Status: Implemented and verified

- Migration: `database/migrations/006_create_positions_table.sql`
- Migration: `database/migrations/007_add_position_id_to_users_table.sql`
- Added staged `positions` with Organization ownership, active/inactive status, Organization index, and composite unique `(organization_id, code)`.
- Added nullable `users.position_id`, its index, and foreign key to `positions.id` so existing Users remain valid without a Position.
- Position DELETE uses `status = inactive`; assigned Users retain their `position_id` and Position rows remain present.
- User assignment requires an active Position in the same Organization; reassignment and clearing are supported.
- Authorization, Permissions, Roles, Position hierarchy, Team hierarchy, and future modules remain deferred.

## BF010 - Authentication Foundation

Status: Implemented and verified

- Migration: `database/migrations/004_add_password_hash_to_users_table.sql`
- Migration: `database/migrations/005_create_auth_tokens_table.sql`
- Added nullable `users.password_hash` for existing BF009 Users without introducing a plain password column.
- Added `auth_tokens` with User ownership, SHA-256 token hashes, `DATETIME` expiry, nullable revocation timestamp, creation timestamp, foreign key, and lookup indexes.
- Raw bearer tokens are returned only on successful login and are never persisted or exposed through APIs.
- Logout revokes tokens without deleting token history; inactive Users cannot log in or use existing tokens.
- Authentication is identity-only. Authorization, Roles, Permissions, Positions, User Profiles, Transfers, reset, registration, refresh tokens, and sessions remain deferred.

## BF009 - User Management

Status: Implemented and verified

- Migration: `database/migrations/003_create_users_table.sql`
- Added the staged `users` table with `id`, `organization_id`, `full_name`, `email`, `phone`, `status`, `created_at`, and `updated_at`.
- Added the restricted foreign key `fk_users_organization` to `organizations.id` and index `idx_users_organization_id`.
- Added global unique email behavior; no password, position, permission, profile, token, or session fields were introduced.
- DELETE uses `status = inactive`; the User row remains present.
- Normal User updates cannot change `organization_id`; Organization transfers remain deferred.
- Authentication, Authorization, Positions, Permissions, User Profiles, and full audit relationships remain deferred.

## BF008 - Partner Agency Management

Status: Implemented; no migration required

- Partner Agencies reuse `organizations` with `organization_type = partner_agency`.
- The BF007 `parent_organization_id` column already provides the required direct Franchise relationship.
- No `partner_agencies` table, new column, or BF008 migration was created.
- DELETE uses `status = inactive`; the database row remains present.
- ULID and extended audit requirements remain deferred.

## BF007 - Franchise Management

Status: Implemented and verified

- Migration: `database/migrations/002_add_parent_organization_id_to_organizations_table.sql`
- Added nullable `parent_organization_id BIGINT UNSIGNED` to support the staged System Organization → Franchise relationship.
- Added index `idx_organizations_parent_organization_id`.
- Added self-referencing foreign key `fk_organizations_parent_organization` to `organizations.id` with restricted parent deletion.
- No `franchises` table was created.
- ULID and extended audit requirements remain deferred.
- Franchise DELETE uses `status = inactive`; the database row remains present.

## BF006 - Organization Module

Status: Implemented and verified

- Migration: `database/migrations/001_create_organizations_table.sql`
- Live table: `organizations`
- Verified columns: `id`, `name`, `code`, `organization_type`, `status`, `created_at`, `updated_at`
- Verified constraints: primary key on `id`; unique constraint on `code`
- At BF006 completion, no foreign keys were present; BF007 adds the staged hierarchy foreign key documented above.

## Staged Schema Note

The BF006-BF008 table intentionally implements only the BF006 fields plus the BF007 hierarchy relationship reused by BF008.
The broader DB101 Organization architecture remains preserved in `docs/database/schema/core.md`;
it is not silently applied to the BF006 implementation.

## Known Documentation and Architecture Discrepancies

- The broader DB101 schema defines additional organization, ULID, relationship, and audit fields.
- Database standards require ULID and audit fields; these are absent from the intentional BF006-BF007 staged schema.
- The global no-hard-delete policy conflicts with the current Organization physical DELETE behavior.
- No automated Organization test suite exists.
