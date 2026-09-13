CREATE TABLE unit_type_attribute_rules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    configuration_version_id BIGINT UNSIGNED NOT NULL,
    attribute_definition_id BIGINT UNSIGNED NOT NULL,
    requirement VARCHAR(50) NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_by_user_id BIGINT UNSIGNED NULL,
    updated_by_user_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_unit_type_attribute_rules_configuration_version_id (configuration_version_id),
    INDEX idx_unit_type_attribute_rules_attribute_definition_id (attribute_definition_id),
    UNIQUE KEY uq_unit_type_attribute_rules_config_definition (configuration_version_id, attribute_definition_id),
    INDEX idx_unit_type_attribute_rules_selection_order (configuration_version_id, sort_order),
    INDEX idx_unit_type_attribute_rules_created_by_user_id (created_by_user_id),
    INDEX idx_unit_type_attribute_rules_updated_by_user_id (updated_by_user_id),
    CONSTRAINT chk_unit_type_attribute_rules_requirement
        CHECK (requirement IN ('REQUIRED', 'OPTIONAL')),
    CONSTRAINT fk_unit_type_attribute_rules_configuration_version
        FOREIGN KEY (configuration_version_id) REFERENCES unit_type_configuration_versions(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_unit_type_attribute_rules_attribute_definition
        FOREIGN KEY (attribute_definition_id) REFERENCES attribute_definitions(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_unit_type_attribute_rules_created_by_user
        FOREIGN KEY (created_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_unit_type_attribute_rules_updated_by_user
        FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
