CREATE TABLE developers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ulid CHAR(26) NOT NULL,
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
    UNIQUE KEY uq_developers_ulid (ulid),
    UNIQUE KEY uq_developers_code (code),
    INDEX idx_developers_selection_order (status, sort_order),
    INDEX idx_developers_created_by_user_id (created_by_user_id),
    INDEX idx_developers_updated_by_user_id (updated_by_user_id),
    CONSTRAINT chk_developers_code
        CHECK (CHAR_LENGTH(TRIM(code)) > 0),
    CONSTRAINT chk_developers_name_ar
        CHECK (CHAR_LENGTH(TRIM(name_ar)) > 0),
    CONSTRAINT chk_developers_name_en
        CHECK (CHAR_LENGTH(TRIM(name_en)) > 0),
    CONSTRAINT chk_developers_status
        CHECK (status IN ('active', 'inactive')),
    CONSTRAINT chk_developers_provenance
        CHECK (provenance IN ('SYSTEM_SEED', 'SYSTEM_ADMIN')),
    CONSTRAINT fk_developers_created_by_user
        FOREIGN KEY (created_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_developers_updated_by_user
        FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
