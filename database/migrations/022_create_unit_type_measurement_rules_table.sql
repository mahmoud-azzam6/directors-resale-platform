CREATE TABLE unit_type_measurement_rules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    configuration_version_id BIGINT UNSIGNED NOT NULL,
    measurement_definition_id BIGINT UNSIGNED NOT NULL,
    requirement VARCHAR(50) NOT NULL,
    is_primary BOOLEAN NOT NULL DEFAULT FALSE,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_by_user_id BIGINT UNSIGNED NULL,
    updated_by_user_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    primary_configuration_guard BIGINT UNSIGNED
        AS (CASE WHEN is_primary = 1 THEN configuration_version_id ELSE NULL END) PERSISTENT,
    INDEX idx_unit_type_measure_rules_configuration_version_id (configuration_version_id),
    INDEX idx_unit_type_measure_rules_measurement_definition_id (measurement_definition_id),
    UNIQUE KEY uq_unit_type_measure_rules_config_definition (configuration_version_id, measurement_definition_id),
    INDEX idx_unit_type_measure_rules_selection_order (configuration_version_id, sort_order),
    INDEX idx_unit_type_measure_rules_created_by_user_id (created_by_user_id),
    INDEX idx_unit_type_measure_rules_updated_by_user_id (updated_by_user_id),
    UNIQUE KEY uq_unit_type_measure_rules_primary_guard (primary_configuration_guard),
    CONSTRAINT chk_unit_type_measure_rules_requirement
        CHECK (requirement IN ('REQUIRED', 'OPTIONAL')),
    CONSTRAINT chk_unit_type_measure_rules_is_primary
        CHECK (is_primary IN (0, 1)),
    CONSTRAINT chk_unit_type_measure_rules_primary_required
        CHECK (is_primary = 0 OR requirement = 'REQUIRED'),
    CONSTRAINT fk_unit_type_measure_rules_configuration_version
        FOREIGN KEY (configuration_version_id) REFERENCES unit_type_configuration_versions(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_unit_type_measure_rules_measurement_definition
        FOREIGN KEY (measurement_definition_id) REFERENCES measurement_definitions(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_unit_type_measure_rules_created_by_user
        FOREIGN KEY (created_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_unit_type_measure_rules_updated_by_user
        FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
