CREATE TABLE unit_type_configuration_versions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ulid CHAR(26) NOT NULL,
    unit_type_id BIGINT UNSIGNED NOT NULL,
    version_number INT UNSIGNED NOT NULL,
    status VARCHAR(50) NOT NULL,
    provenance VARCHAR(50) NOT NULL,
    created_by_user_id BIGINT UNSIGNED NULL,
    updated_by_user_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    active_unit_type_guard BIGINT UNSIGNED
        AS (CASE WHEN status = 'active' THEN unit_type_id ELSE NULL END) PERSISTENT,
    UNIQUE KEY uq_unit_type_configs_ulid (ulid),
    INDEX idx_unit_type_configs_unit_type_id (unit_type_id),
    UNIQUE KEY uq_unit_type_configs_unit_type_version (unit_type_id, version_number),
    INDEX idx_unit_type_configs_created_by_user_id (created_by_user_id),
    INDEX idx_unit_type_configs_updated_by_user_id (updated_by_user_id),
    UNIQUE KEY uq_unit_type_configs_active_guard (active_unit_type_guard),
    INDEX idx_unit_type_configs_unit_type_status (unit_type_id, status),
    CONSTRAINT chk_unit_type_configs_version_number
        CHECK (version_number > 0),
    CONSTRAINT chk_unit_type_configs_status
        CHECK (status IN ('draft', 'active', 'historical')),
    CONSTRAINT chk_unit_type_configs_provenance
        CHECK (provenance IN ('SYSTEM_SEED', 'SYSTEM_ADMIN')),
    CONSTRAINT fk_unit_type_configs_unit_type
        FOREIGN KEY (unit_type_id) REFERENCES unit_types(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_unit_type_configs_created_by_user
        FOREIGN KEY (created_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_unit_type_configs_updated_by_user
        FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
