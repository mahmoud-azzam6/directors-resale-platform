CREATE TABLE ownership_parties (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ulid CHAR(26) NOT NULL,
    ownership_id BIGINT UNSIGNED NOT NULL,
    owner_id BIGINT UNSIGNED NOT NULL,
    organization_id BIGINT UNSIGNED NOT NULL,
    share_percentage DECIMAL(7,4) NULL,
    created_by_user_id BIGINT UNSIGNED NOT NULL,
    updated_by_user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ownership_parties_ulid (ulid),
    UNIQUE KEY uq_ownership_parties_ownership_owner (ownership_id, owner_id),
    UNIQUE KEY uq_ownership_parties_id_ownership (id, ownership_id),
    INDEX idx_ownership_parties_ownership_organization (ownership_id, organization_id),
    INDEX idx_ownership_parties_owner_organization (owner_id, organization_id),
    INDEX idx_ownership_parties_created_by_user_id (created_by_user_id),
    INDEX idx_ownership_parties_updated_by_user_id (updated_by_user_id),
    CONSTRAINT chk_ownership_parties_share_percentage
        CHECK (
            share_percentage IS NULL
            OR (share_percentage > 0 AND share_percentage <= 100)
        ),
    CONSTRAINT fk_ownership_parties_ownership_organization
        FOREIGN KEY (ownership_id, organization_id)
        REFERENCES ownerships(id, organization_id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_ownership_parties_owner_organization
        FOREIGN KEY (owner_id, organization_id)
        REFERENCES owners(id, organization_id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_ownership_parties_created_by_user
        FOREIGN KEY (created_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_ownership_parties_updated_by_user
        FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
