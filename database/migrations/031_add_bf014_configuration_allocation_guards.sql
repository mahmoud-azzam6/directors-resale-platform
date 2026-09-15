ALTER TABLE unit_types
    ADD COLUMN last_allocated_configuration_version INT UNSIGNED NOT NULL DEFAULT 0,
    ADD CONSTRAINT chk_unit_types_last_allocated_configuration_version
        CHECK (last_allocated_configuration_version >= 0);

-- This upgrade backfill preserves already-persisted version history. Runtime
-- allocation remains Service-owned and must not use MAX(version_number) + 1.
UPDATE unit_types AS unit_type
LEFT JOIN (
    SELECT unit_type_id, MAX(version_number) AS highest_version_number
    FROM unit_type_configuration_versions
    GROUP BY unit_type_id
) AS configuration_versions ON configuration_versions.unit_type_id = unit_type.id
SET unit_type.last_allocated_configuration_version =
    COALESCE(configuration_versions.highest_version_number, 0);

ALTER TABLE unit_type_configuration_versions
    ADD COLUMN draft_unit_type_guard BIGINT UNSIGNED
        AS (CASE WHEN status = 'draft' THEN unit_type_id ELSE NULL END) PERSISTENT,
    ADD UNIQUE KEY uq_unit_type_configs_draft_guard (draft_unit_type_guard);
