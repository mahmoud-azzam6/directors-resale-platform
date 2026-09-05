CREATE TABLE organization_properties (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ulid CHAR(26) NOT NULL,
    organization_id BIGINT UNSIGNED NOT NULL,
    property_label VARCHAR(255) NOT NULL,
    status VARCHAR(50) NOT NULL,
    archived_at TIMESTAMP NULL,
    archived_by_user_id BIGINT UNSIGNED NULL,
    created_by_user_id BIGINT UNSIGNED NOT NULL,
    updated_by_user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_organization_properties_ulid (ulid),
    UNIQUE KEY uq_organization_properties_id_organization (id, organization_id),
    INDEX idx_organization_properties_organization_status (organization_id, status),
    INDEX idx_organization_properties_archived_by_user_id (archived_by_user_id),
    INDEX idx_organization_properties_created_by_user_id (created_by_user_id),
    INDEX idx_organization_properties_updated_by_user_id (updated_by_user_id),
    CONSTRAINT chk_organization_properties_status
        CHECK (status IN ('active', 'archived')),
    CONSTRAINT chk_organization_properties_archive_state
        CHECK (
            (status = 'active' AND archived_at IS NULL AND archived_by_user_id IS NULL)
            OR
            (status = 'archived' AND archived_at IS NOT NULL AND archived_by_user_id IS NOT NULL)
        ),
    CONSTRAINT fk_organization_properties_organization
        FOREIGN KEY (organization_id) REFERENCES organizations(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_organization_properties_archived_by_user
        FOREIGN KEY (archived_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_organization_properties_created_by_user
        FOREIGN KEY (created_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_organization_properties_updated_by_user
        FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
