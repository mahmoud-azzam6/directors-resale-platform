CREATE TABLE organization_basic_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    geographic_location_id BIGINT UNSIGNED NULL,
    address_text VARCHAR(1000) NULL,
    created_by_user_id BIGINT UNSIGNED NOT NULL,
    updated_by_user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_organization_basic_profiles_organization (organization_id),
    INDEX idx_organization_basic_profiles_geography (geographic_location_id),
    CONSTRAINT chk_organization_basic_profiles_address CHECK (address_text IS NULL OR CHAR_LENGTH(TRIM(address_text)) > 0),
    CONSTRAINT fk_organization_basic_profiles_organization FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_organization_basic_profiles_geography FOREIGN KEY (geographic_location_id) REFERENCES geographic_locations(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_organization_basic_profiles_created_by FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_organization_basic_profiles_updated_by FOREIGN KEY (updated_by_user_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
