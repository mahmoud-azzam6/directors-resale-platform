CREATE TABLE projects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ulid CHAR(26) NOT NULL,
    developer_id BIGINT UNSIGNED NULL,
    geographic_location_id BIGINT UNSIGNED NULL,
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
    UNIQUE KEY uq_projects_ulid (ulid),
    INDEX idx_projects_developer_id (developer_id),
    INDEX idx_projects_geographic_location_id (geographic_location_id),
    UNIQUE KEY uq_projects_code (code),
    INDEX idx_projects_selection_order (developer_id, status, sort_order),
    INDEX idx_projects_created_by_user_id (created_by_user_id),
    INDEX idx_projects_updated_by_user_id (updated_by_user_id),
    CONSTRAINT chk_projects_code
        CHECK (CHAR_LENGTH(TRIM(code)) > 0),
    CONSTRAINT chk_projects_name_ar
        CHECK (CHAR_LENGTH(TRIM(name_ar)) > 0),
    CONSTRAINT chk_projects_name_en
        CHECK (CHAR_LENGTH(TRIM(name_en)) > 0),
    CONSTRAINT chk_projects_status
        CHECK (status IN ('active', 'inactive')),
    CONSTRAINT chk_projects_provenance
        CHECK (provenance IN ('SYSTEM_SEED', 'SYSTEM_ADMIN')),
    CONSTRAINT fk_projects_developer
        FOREIGN KEY (developer_id) REFERENCES developers(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_projects_geographic_location
        FOREIGN KEY (geographic_location_id) REFERENCES geographic_locations(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_projects_created_by_user
        FOREIGN KEY (created_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_projects_updated_by_user
        FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
