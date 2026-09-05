CREATE TABLE ownerships (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ulid CHAR(26) NOT NULL,
    organization_property_id BIGINT UNSIGNED NOT NULL,
    organization_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(50) NOT NULL,
    closed_at TIMESTAMP NULL,
    closed_by_user_id BIGINT UNSIGNED NULL,
    created_by_user_id BIGINT UNSIGNED NOT NULL,
    updated_by_user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    current_property_guard BIGINT UNSIGNED
        AS (CASE WHEN status = 'current' THEN organization_property_id ELSE NULL END) PERSISTENT,
    UNIQUE KEY uq_ownerships_ulid (ulid),
    UNIQUE KEY uq_ownerships_id_organization (id, organization_id),
    UNIQUE KEY uq_ownerships_current_property_guard (current_property_guard),
    INDEX idx_ownerships_property_created_at (organization_property_id, created_at),
    INDEX idx_ownerships_closed_by_user_id (closed_by_user_id),
    INDEX idx_ownerships_created_by_user_id (created_by_user_id),
    INDEX idx_ownerships_updated_by_user_id (updated_by_user_id),
    CONSTRAINT chk_ownerships_status
        CHECK (status IN ('current', 'closed')),
    CONSTRAINT chk_ownerships_closed_state
        CHECK (
            (status = 'current' AND closed_at IS NULL AND closed_by_user_id IS NULL)
            OR
            (status = 'closed' AND closed_at IS NOT NULL AND closed_by_user_id IS NOT NULL)
        ),
    CONSTRAINT fk_ownerships_property_organization
        FOREIGN KEY (organization_property_id, organization_id)
        REFERENCES organization_properties(id, organization_id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_ownerships_closed_by_user
        FOREIGN KEY (closed_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_ownerships_created_by_user
        FOREIGN KEY (created_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_ownerships_updated_by_user
        FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
