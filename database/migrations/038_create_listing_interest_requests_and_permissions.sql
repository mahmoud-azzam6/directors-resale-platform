ALTER TABLE listings ADD UNIQUE KEY uq_listings_request_reference (id, organization_property_id, organization_id);
ALTER TABLE users ADD UNIQUE KEY uq_users_request_identity (id, organization_id);

CREATE TABLE listing_interest_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ulid CHAR(26) NOT NULL,
    listing_id BIGINT UNSIGNED NOT NULL,
    organization_property_id BIGINT UNSIGNED NOT NULL,
    listing_organization_id BIGINT UNSIGNED NOT NULL,
    requester_user_id BIGINT UNSIGNED NOT NULL,
    requester_organization_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'submitted',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_interest_request_ulid (ulid),
    UNIQUE KEY uq_interest_request_user_listing (listing_id, requester_user_id),
    INDEX idx_interest_request_user (requester_user_id, created_at),
    INDEX idx_interest_request_scope (listing_organization_id, listing_id),
    CONSTRAINT fk_interest_request_listing FOREIGN KEY (listing_id, organization_property_id, listing_organization_id)
        REFERENCES listings(id, organization_property_id, organization_id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_interest_request_property FOREIGN KEY (organization_property_id, listing_organization_id)
        REFERENCES organization_properties(id, organization_id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_interest_request_user FOREIGN KEY (requester_user_id, requester_organization_id)
        REFERENCES users(id, organization_id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT chk_interest_request_status CHECK (status = 'submitted')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (code, name, status) VALUES
('published_listings.view', 'View Published Listings Catalog', 'active'),
('requests.create', 'Submit Listing Interest Requests', 'active'),
('requests.view', 'View Own Listing Interest Requests', 'active');
