CREATE TABLE unit_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ulid CHAR(26) NOT NULL,
    property_category_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(100) NOT NULL,
    name_ar VARCHAR(255) NOT NULL,
    name_en VARCHAR(255) NOT NULL,
    status VARCHAR(50) NOT NULL,
    provenance VARCHAR(50) NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_by_user_id BIGINT UNSIGNED NULL,
    updated_by_user_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_unit_types_ulid (ulid),
    INDEX idx_unit_types_property_category_id (property_category_id),
    UNIQUE KEY uq_unit_types_code (code),
    INDEX idx_unit_types_selection_order (property_category_id, status, sort_order),
    INDEX idx_unit_types_created_by_user_id (created_by_user_id),
    INDEX idx_unit_types_updated_by_user_id (updated_by_user_id),
    CONSTRAINT chk_unit_types_code
        CHECK (CHAR_LENGTH(TRIM(code)) > 0),
    CONSTRAINT chk_unit_types_name_ar
        CHECK (CHAR_LENGTH(TRIM(name_ar)) > 0),
    CONSTRAINT chk_unit_types_name_en
        CHECK (CHAR_LENGTH(TRIM(name_en)) > 0),
    CONSTRAINT chk_unit_types_status
        CHECK (status IN ('active', 'inactive')),
    CONSTRAINT chk_unit_types_provenance
        CHECK (provenance IN ('SYSTEM_SEED', 'SYSTEM_ADMIN')),
    CONSTRAINT fk_unit_types_property_category
        FOREIGN KEY (property_category_id) REFERENCES property_categories(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_unit_types_created_by_user
        FOREIGN KEY (created_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_unit_types_updated_by_user
        FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
