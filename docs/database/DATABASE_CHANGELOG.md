# Database Changelog

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
