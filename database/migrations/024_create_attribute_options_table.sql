CREATE TABLE attribute_options (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ulid CHAR(26) NOT NULL,
    attribute_definition_id BIGINT UNSIGNED NOT NULL,
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
    UNIQUE KEY uq_attribute_options_ulid (ulid),
    INDEX idx_attribute_options_attribute_definition_id (attribute_definition_id),
    UNIQUE KEY uq_attribute_options_code (attribute_definition_id, code),
    INDEX idx_attribute_options_selection_order (attribute_definition_id, status, sort_order),
    INDEX idx_attribute_options_created_by_user_id (created_by_user_id),
    INDEX idx_attribute_options_updated_by_user_id (updated_by_user_id),
    CONSTRAINT chk_attribute_options_code
        CHECK (CHAR_LENGTH(TRIM(code)) > 0),
    CONSTRAINT chk_attribute_options_name_ar
        CHECK (CHAR_LENGTH(TRIM(name_ar)) > 0),
    CONSTRAINT chk_attribute_options_name_en
        CHECK (CHAR_LENGTH(TRIM(name_en)) > 0),
    CONSTRAINT chk_attribute_options_status
        CHECK (status IN ('active', 'inactive')),
    CONSTRAINT chk_attribute_options_provenance
        CHECK (provenance IN ('SYSTEM_SEED', 'SYSTEM_ADMIN')),
    CONSTRAINT fk_attribute_options_attribute_definition
        FOREIGN KEY (attribute_definition_id) REFERENCES attribute_definitions(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_attribute_options_created_by_user
        FOREIGN KEY (created_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_attribute_options_updated_by_user
        FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
