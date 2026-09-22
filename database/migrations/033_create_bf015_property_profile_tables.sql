CREATE TABLE organization_property_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_property_id BIGINT UNSIGNED NOT NULL,
    property_category_id BIGINT UNSIGNED NULL,
    unit_type_id BIGINT UNSIGNED NULL,
    accepted_configuration_version_id BIGINT UNSIGNED NULL,
    geographic_location_id BIGINT UNSIGNED NULL,
    development_reference_type VARCHAR(50) NULL,
    developer_id BIGINT UNSIGNED NULL,
    project_id BIGINT UNSIGNED NULL,
    project_phase_id BIGINT UNSIGNED NULL,
    revision INT UNSIGNED NOT NULL DEFAULT 1,
    created_by_user_id BIGINT UNSIGNED NOT NULL,
    updated_by_user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_organization_property_profiles_property (organization_property_id),
    INDEX idx_organization_property_profiles_category (property_category_id),
    INDEX idx_organization_property_profiles_unit_type (unit_type_id),
    INDEX idx_organization_property_profiles_configuration (accepted_configuration_version_id),
    INDEX idx_organization_property_profiles_geography (geographic_location_id),
    INDEX idx_organization_property_profiles_developer (developer_id),
    INDEX idx_organization_property_profiles_project (project_id),
    INDEX idx_organization_property_profiles_phase (project_phase_id),
    INDEX idx_organization_property_profiles_created_by_user (created_by_user_id),
    INDEX idx_organization_property_profiles_updated_by_user (updated_by_user_id),
    CONSTRAINT chk_organization_property_profiles_revision
        CHECK (revision > 0),
    CONSTRAINT chk_organization_property_profiles_development_reference
        CHECK (
            (development_reference_type IS NULL AND developer_id IS NULL AND project_id IS NULL AND project_phase_id IS NULL)
            OR (development_reference_type = 'DEVELOPER' AND developer_id IS NOT NULL AND project_id IS NULL AND project_phase_id IS NULL)
            OR (development_reference_type = 'PROJECT' AND developer_id IS NULL AND project_id IS NOT NULL AND project_phase_id IS NULL)
            OR (development_reference_type = 'PHASE' AND developer_id IS NULL AND project_id IS NULL AND project_phase_id IS NOT NULL)
        ),
    CONSTRAINT fk_organization_property_profiles_property
        FOREIGN KEY (organization_property_id) REFERENCES organization_properties(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_organization_property_profiles_category
        FOREIGN KEY (property_category_id) REFERENCES property_categories(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_organization_property_profiles_unit_type
        FOREIGN KEY (unit_type_id) REFERENCES unit_types(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_organization_property_profiles_configuration
        FOREIGN KEY (accepted_configuration_version_id) REFERENCES unit_type_configuration_versions(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_organization_property_profiles_geography
        FOREIGN KEY (geographic_location_id) REFERENCES geographic_locations(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_organization_property_profiles_developer
        FOREIGN KEY (developer_id) REFERENCES developers(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_organization_property_profiles_project
        FOREIGN KEY (project_id) REFERENCES projects(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_organization_property_profiles_phase
        FOREIGN KEY (project_phase_id) REFERENCES project_phases(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_organization_property_profiles_created_by_user
        FOREIGN KEY (created_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_organization_property_profiles_updated_by_user
        FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE property_measurements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_property_id BIGINT UNSIGNED NOT NULL,
    measurement_definition_id BIGINT UNSIGNED NOT NULL,
    value_decimal DECIMAL(18,4) NOT NULL,
    unit_code VARCHAR(20) NOT NULL,
    created_by_user_id BIGINT UNSIGNED NOT NULL,
    updated_by_user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_property_measurements_property_definition (organization_property_id, measurement_definition_id),
    INDEX idx_property_measurements_definition (measurement_definition_id),
    INDEX idx_property_measurements_created_by_user (created_by_user_id),
    INDEX idx_property_measurements_updated_by_user (updated_by_user_id),
    CONSTRAINT chk_property_measurements_positive_value
        CHECK (value_decimal > 0),
    CONSTRAINT chk_property_measurements_unit_code
        CHECK (CHAR_LENGTH(TRIM(unit_code)) > 0),
    CONSTRAINT fk_property_measurements_property
        FOREIGN KEY (organization_property_id) REFERENCES organization_properties(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_property_measurements_profile
        FOREIGN KEY (organization_property_id) REFERENCES organization_property_profiles(organization_property_id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_property_measurements_definition
        FOREIGN KEY (measurement_definition_id) REFERENCES measurement_definitions(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_property_measurements_created_by_user
        FOREIGN KEY (created_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_property_measurements_updated_by_user
        FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE property_attribute_values (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_property_id BIGINT UNSIGNED NOT NULL,
    attribute_definition_id BIGINT UNSIGNED NOT NULL,
    data_type VARCHAR(50) NOT NULL,
    value_integer BIGINT NULL,
    value_decimal DECIMAL(18,4) NULL,
    value_boolean TINYINT NULL,
    value_text TEXT NULL,
    value_date DATE NULL,
    attribute_option_id BIGINT UNSIGNED NULL,
    created_by_user_id BIGINT UNSIGNED NOT NULL,
    updated_by_user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_property_attribute_values_property_definition (organization_property_id, attribute_definition_id),
    INDEX idx_property_attribute_values_definition (attribute_definition_id),
    INDEX idx_property_attribute_values_option (attribute_option_id),
    INDEX idx_property_attribute_values_created_by_user (created_by_user_id),
    INDEX idx_property_attribute_values_updated_by_user (updated_by_user_id),
    CONSTRAINT chk_property_attribute_values_data_type
        CHECK (data_type IN ('INTEGER', 'DECIMAL', 'BOOLEAN', 'TEXT', 'ENUM', 'DATE')),
    CONSTRAINT chk_property_attribute_values_boolean
        CHECK (value_boolean IS NULL OR value_boolean IN (0, 1)),
    CONSTRAINT chk_property_attribute_values_typed_shape
        CHECK (
            (data_type = 'INTEGER' AND value_integer IS NOT NULL AND value_decimal IS NULL AND value_boolean IS NULL AND value_text IS NULL AND value_date IS NULL AND attribute_option_id IS NULL)
            OR (data_type = 'DECIMAL' AND value_integer IS NULL AND value_decimal IS NOT NULL AND value_boolean IS NULL AND value_text IS NULL AND value_date IS NULL AND attribute_option_id IS NULL)
            OR (data_type = 'BOOLEAN' AND value_integer IS NULL AND value_decimal IS NULL AND value_boolean IS NOT NULL AND value_text IS NULL AND value_date IS NULL AND attribute_option_id IS NULL)
            OR (data_type = 'TEXT' AND value_integer IS NULL AND value_decimal IS NULL AND value_boolean IS NULL AND value_text IS NOT NULL AND value_date IS NULL AND attribute_option_id IS NULL)
            OR (data_type = 'ENUM' AND value_integer IS NULL AND value_decimal IS NULL AND value_boolean IS NULL AND value_text IS NULL AND value_date IS NULL AND attribute_option_id IS NOT NULL)
            OR (data_type = 'DATE' AND value_integer IS NULL AND value_decimal IS NULL AND value_boolean IS NULL AND value_text IS NULL AND value_date IS NOT NULL AND attribute_option_id IS NULL)
        ),
    CONSTRAINT fk_property_attribute_values_property
        FOREIGN KEY (organization_property_id) REFERENCES organization_properties(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_property_attribute_values_profile
        FOREIGN KEY (organization_property_id) REFERENCES organization_property_profiles(organization_property_id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_property_attribute_values_definition
        FOREIGN KEY (attribute_definition_id) REFERENCES attribute_definitions(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_property_attribute_values_option
        FOREIGN KEY (attribute_option_id) REFERENCES attribute_options(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_property_attribute_values_created_by_user
        FOREIGN KEY (created_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_property_attribute_values_updated_by_user
        FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
