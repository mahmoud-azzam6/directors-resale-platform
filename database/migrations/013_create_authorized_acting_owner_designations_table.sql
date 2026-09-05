CREATE TABLE authorized_acting_owner_designations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ulid CHAR(26) NOT NULL,
    ownership_id BIGINT UNSIGNED NOT NULL,
    ownership_party_id BIGINT UNSIGNED NOT NULL,
    basis_source VARCHAR(255) NOT NULL,
    notes TEXT NULL,
    started_at TIMESTAMP NOT NULL,
    ended_at TIMESTAMP NULL,
    ended_by_user_id BIGINT UNSIGNED NULL,
    created_by_user_id BIGINT UNSIGNED NOT NULL,
    updated_by_user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    current_ownership_guard BIGINT UNSIGNED
        AS (CASE WHEN ended_at IS NULL THEN ownership_id ELSE NULL END) PERSISTENT,
    UNIQUE KEY uq_acting_owner_designations_ulid (ulid),
    UNIQUE KEY uq_acting_owner_designations_current_guard (current_ownership_guard),
    INDEX idx_acting_owner_designations_party_ownership (ownership_party_id, ownership_id),
    INDEX idx_acting_owner_designations_ownership_started_at (ownership_id, started_at),
    INDEX idx_acting_owner_designations_ended_by_user_id (ended_by_user_id),
    INDEX idx_acting_owner_designations_created_by_user_id (created_by_user_id),
    INDEX idx_acting_owner_designations_updated_by_user_id (updated_by_user_id),
    CONSTRAINT chk_acting_owner_designations_ended_state
        CHECK (
            (ended_at IS NULL AND ended_by_user_id IS NULL)
            OR
            (ended_at IS NOT NULL AND ended_by_user_id IS NOT NULL)
        ),
    CONSTRAINT fk_acting_owner_designations_party_ownership
        FOREIGN KEY (ownership_party_id, ownership_id)
        REFERENCES ownership_parties(id, ownership_id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_acting_owner_designations_ended_by_user
        FOREIGN KEY (ended_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_acting_owner_designations_created_by_user
        FOREIGN KEY (created_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_acting_owner_designations_updated_by_user
        FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
