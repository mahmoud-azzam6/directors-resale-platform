CREATE TABLE property_catalog_seed_versions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seed_key VARCHAR(150) NOT NULL,
    checksum CHAR(64) NOT NULL,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    applied_by_user_id BIGINT UNSIGNED NULL,
    UNIQUE KEY uq_property_catalog_seeds_seed_key (seed_key),
    INDEX idx_property_catalog_seeds_applied_by_user_id (applied_by_user_id),
    CONSTRAINT chk_property_catalog_seeds_seed_key
        CHECK (CHAR_LENGTH(TRIM(seed_key)) > 0),
    CONSTRAINT chk_property_catalog_seeds_checksum
        CHECK (CHAR_LENGTH(checksum) = 64 AND checksum REGEXP '^[0-9A-Fa-f]{64}$'),
    CONSTRAINT fk_property_catalog_seeds_applied_by_user
        FOREIGN KEY (applied_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
