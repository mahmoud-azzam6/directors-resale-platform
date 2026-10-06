CREATE TABLE listings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ulid CHAR(26) NOT NULL,
    organization_property_id BIGINT UNSIGNED NOT NULL,
    organization_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'draft',
    revision INT UNSIGNED NOT NULL DEFAULT 1,
    published_at TIMESTAMP NULL,
    archived_at TIMESTAMP NULL,
    created_by_user_id BIGINT UNSIGNED NOT NULL,
    updated_by_user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    active_property_guard BIGINT UNSIGNED AS (CASE WHEN status <> 'archived' THEN organization_property_id ELSE NULL END) PERSISTENT,
    UNIQUE KEY uq_listings_ulid (ulid),
    UNIQUE KEY uq_listings_active_property (active_property_guard),
    INDEX idx_listings_organization_status (organization_id, status),
    CONSTRAINT fk_listing_property_organization FOREIGN KEY (organization_property_id, organization_id)
        REFERENCES organization_properties(id, organization_id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_listing_created_by FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_listing_updated_by FOREIGN KEY (updated_by_user_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT chk_listing_revision CHECK (revision > 0),
    CONSTRAINT chk_listing_lifecycle CHECK (
        (status = 'draft' AND published_at IS NULL AND archived_at IS NULL)
        OR (status = 'published' AND published_at IS NOT NULL AND archived_at IS NULL)
        OR (status = 'archived' AND archived_at IS NOT NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (code, name, status) VALUES
('listings.view', 'View Listings', 'active'),
('listings.manage', 'Manage Listings', 'active');
