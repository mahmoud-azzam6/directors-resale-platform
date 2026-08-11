# Database Changelog

## BF006 - Organization Module

Status: Implemented and verified

- Migration: `database/migrations/001_create_organizations_table.sql`
- Live table: `organizations`
- Verified columns: `id`, `name`, `code`, `organization_type`, `status`, `created_at`, `updated_at`
- Verified constraints: primary key on `id`; unique constraint on `code`
- No foreign keys are present in this BF006 staged table.

## Staged Schema Note

The BF006 table intentionally implements only the seven fields specified by the BF006 sprint.
The broader DB101 Organization architecture remains preserved in `docs/database/schema/core.md`;
it is not silently applied to the BF006 implementation.

## Known Documentation and Architecture Discrepancies

- The broader DB101 schema defines additional organization, ULID, relationship, and audit fields.
- Database standards require ULID and audit fields; these are absent from the intentional BF006 staged schema.
- The global no-hard-delete policy conflicts with the current Organization physical DELETE behavior.
- No automated Organization test suite exists.
