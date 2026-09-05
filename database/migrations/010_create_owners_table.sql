CREATE TABLE owners (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ulid CHAR(26) NOT NULL,
    organization_id BIGINT UNSIGNED NOT NULL,
    party_type VARCHAR(50) NOT NULL,
    display_name VARCHAR(255) NOT NULL,
    mobile VARCHAR(50) NULL,
    mobile_normalized VARCHAR(50) NULL,
    email VARCHAR(255) NULL,
    email_normalized VARCHAR(255) NULL,
    preferred_contact_method VARCHAR(50) NULL,
    contact_person_name VARCHAR(255) NULL,
    contact_person_mobile VARCHAR(50) NULL,
    contact_person_email VARCHAR(255) NULL,
    status VARCHAR(50) NOT NULL,
    deactivated_at TIMESTAMP NULL,
    deactivated_by_user_id BIGINT UNSIGNED NULL,
    created_by_user_id BIGINT UNSIGNED NOT NULL,
    updated_by_user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_owners_ulid (ulid),
    UNIQUE KEY uq_owners_id_organization (id, organization_id),
    INDEX idx_owners_organization_status (organization_id, status),
    INDEX idx_owners_organization_mobile_normalized (organization_id, mobile_normalized),
    INDEX idx_owners_organization_email_normalized (organization_id, email_normalized),
    INDEX idx_owners_deactivated_by_user_id (deactivated_by_user_id),
    INDEX idx_owners_created_by_user_id (created_by_user_id),
    INDEX idx_owners_updated_by_user_id (updated_by_user_id),
    CONSTRAINT chk_owners_party_type
        CHECK (party_type IN ('individual', 'legal_entity')),
    CONSTRAINT chk_owners_status
        CHECK (status IN ('active', 'inactive')),
    CONSTRAINT chk_owners_preferred_contact_method
        CHECK (preferred_contact_method IS NULL OR preferred_contact_method IN ('phone', 'whatsapp', 'email')),
    CONSTRAINT chk_owners_deactivation_state
        CHECK (
            (status = 'active' AND deactivated_at IS NULL AND deactivated_by_user_id IS NULL)
            OR
            (status = 'inactive' AND deactivated_at IS NOT NULL AND deactivated_by_user_id IS NOT NULL)
        ),
    CONSTRAINT fk_owners_organization
        FOREIGN KEY (organization_id) REFERENCES organizations(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_owners_deactivated_by_user
        FOREIGN KEY (deactivated_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_owners_created_by_user
        FOREIGN KEY (created_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_owners_updated_by_user
        FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
