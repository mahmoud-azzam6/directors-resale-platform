CREATE TABLE global_physical_property_identities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ulid CHAR(26) NOT NULL,
    created_by_user_id BIGINT UNSIGNED NOT NULL,
    updated_by_user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_global_physical_property_identities_ulid (ulid),
    INDEX idx_global_physical_identities_created_by_user_id (created_by_user_id),
    INDEX idx_global_physical_identities_updated_by_user_id (updated_by_user_id),
    CONSTRAINT fk_global_physical_identities_created_by_user
        FOREIGN KEY (created_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_global_physical_identities_updated_by_user
        FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
