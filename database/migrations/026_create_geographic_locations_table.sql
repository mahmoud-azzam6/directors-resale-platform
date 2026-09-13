CREATE TABLE geographic_locations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ulid CHAR(26) NOT NULL,
    parent_id BIGINT UNSIGNED NULL,
    code VARCHAR(100) NOT NULL,
    name_ar VARCHAR(255) NOT NULL,
    name_en VARCHAR(255) NOT NULL,
    status VARCHAR(50) NOT NULL,
    provenance VARCHAR(50) NOT NULL,
    type VARCHAR(50) NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_by_user_id BIGINT UNSIGNED NULL,
    updated_by_user_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_geographic_locations_ulid (ulid),
    INDEX idx_geographic_locations_parent_id (parent_id),
    UNIQUE KEY uq_geographic_locations_code (code),
    INDEX idx_geographic_locations_type_status (type, status),
    INDEX idx_geographic_locations_selection_order (parent_id, status, sort_order),
    INDEX idx_geographic_locations_created_by_user_id (created_by_user_id),
    INDEX idx_geographic_locations_updated_by_user_id (updated_by_user_id),
    CONSTRAINT chk_geographic_locations_code
        CHECK (CHAR_LENGTH(TRIM(code)) > 0),
    CONSTRAINT chk_geographic_locations_name_ar
        CHECK (CHAR_LENGTH(TRIM(name_ar)) > 0),
    CONSTRAINT chk_geographic_locations_name_en
        CHECK (CHAR_LENGTH(TRIM(name_en)) > 0),
    CONSTRAINT chk_geographic_locations_status
        CHECK (status IN ('active', 'inactive')),
    CONSTRAINT chk_geographic_locations_provenance
        CHECK (provenance IN ('SYSTEM_SEED', 'SYSTEM_ADMIN')),
    CONSTRAINT chk_geographic_locations_type
        CHECK (type IN ('COUNTRY', 'GOVERNORATE', 'CITY', 'AREA', 'DISTRICT')),
    CONSTRAINT chk_geographic_locations_root
        CHECK ((type = 'COUNTRY' AND parent_id IS NULL) OR (type <> 'COUNTRY' AND parent_id IS NOT NULL)),
    CONSTRAINT fk_geographic_locations_parent
        FOREIGN KEY (parent_id) REFERENCES geographic_locations(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_geographic_locations_created_by_user
        FOREIGN KEY (created_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_geographic_locations_updated_by_user
        FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
