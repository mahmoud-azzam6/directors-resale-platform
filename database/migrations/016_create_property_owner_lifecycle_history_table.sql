CREATE TABLE property_owner_lifecycle_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ulid CHAR(26) NOT NULL,
    organization_id BIGINT UNSIGNED NOT NULL,
    organization_property_id BIGINT UNSIGNED NULL,
    owner_id BIGINT UNSIGNED NULL,
    action VARCHAR(50) NOT NULL,
    from_status VARCHAR(50) NOT NULL,
    to_status VARCHAR(50) NOT NULL,
    reason TEXT NULL,
    created_by_user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_property_owner_lifecycle_history_ulid (ulid),
    INDEX idx_property_lifecycle_history_order (organization_property_id, created_at),
    INDEX idx_owner_lifecycle_history_order (owner_id, created_at),
    INDEX idx_organization_lifecycle_history_order (organization_id, created_at),
    INDEX idx_property_lifecycle_history_property_organization (organization_property_id, organization_id),
    INDEX idx_owner_lifecycle_history_owner_organization (owner_id, organization_id),
    INDEX idx_property_owner_lifecycle_created_by_user_id (created_by_user_id),
    CONSTRAINT chk_property_owner_lifecycle_resource
        CHECK (
            (organization_property_id IS NOT NULL AND owner_id IS NULL)
            OR
            (organization_property_id IS NULL AND owner_id IS NOT NULL)
        ),
    CONSTRAINT chk_property_owner_lifecycle_action
        CHECK (action IN ('property_archive', 'property_reactivate', 'owner_deactivate', 'owner_reactivate')),
    CONSTRAINT fk_property_lifecycle_history_property_organization
        FOREIGN KEY (organization_property_id, organization_id)
        REFERENCES organization_properties(id, organization_id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_owner_lifecycle_history_owner_organization
        FOREIGN KEY (owner_id, organization_id)
        REFERENCES owners(id, organization_id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_property_owner_lifecycle_created_by_user
        FOREIGN KEY (created_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
