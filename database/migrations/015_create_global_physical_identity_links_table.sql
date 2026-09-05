CREATE TABLE global_physical_identity_links (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ulid CHAR(26) NOT NULL,
    organization_property_id BIGINT UNSIGNED NOT NULL,
    global_physical_property_identity_id BIGINT UNSIGNED NOT NULL,
    link_method VARCHAR(50) NOT NULL,
    unlinked_at TIMESTAMP NULL,
    unlinked_by_user_id BIGINT UNSIGNED NULL,
    unlink_reason TEXT NULL,
    created_by_user_id BIGINT UNSIGNED NOT NULL,
    updated_by_user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    active_property_guard BIGINT UNSIGNED
        AS (CASE WHEN unlinked_at IS NULL THEN organization_property_id ELSE NULL END) PERSISTENT,
    UNIQUE KEY uq_global_identity_links_ulid (ulid),
    UNIQUE KEY uq_global_identity_links_active_property_guard (active_property_guard),
    INDEX idx_global_identity_links_property_created_at (organization_property_id, created_at),
    INDEX idx_global_identity_links_identity_unlinked_at (global_physical_property_identity_id, unlinked_at),
    INDEX idx_global_identity_links_unlinked_by_user_id (unlinked_by_user_id),
    INDEX idx_global_identity_links_created_by_user_id (created_by_user_id),
    INDEX idx_global_identity_links_updated_by_user_id (updated_by_user_id),
    CONSTRAINT chk_global_identity_links_method
        CHECK (link_method = 'manual'),
    CONSTRAINT chk_global_identity_links_unlinked_state
        CHECK (
            (unlinked_at IS NULL AND unlinked_by_user_id IS NULL AND unlink_reason IS NULL)
            OR
            (
                unlinked_at IS NOT NULL
                AND unlinked_by_user_id IS NOT NULL
                AND unlink_reason IS NOT NULL
                AND CHAR_LENGTH(TRIM(unlink_reason)) > 0
            )
        ),
    CONSTRAINT fk_global_identity_links_property
        FOREIGN KEY (organization_property_id) REFERENCES organization_properties(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_global_identity_links_identity
        FOREIGN KEY (global_physical_property_identity_id) REFERENCES global_physical_property_identities(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_global_identity_links_unlinked_by_user
        FOREIGN KEY (unlinked_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_global_identity_links_created_by_user
        FOREIGN KEY (created_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_global_identity_links_updated_by_user
        FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
