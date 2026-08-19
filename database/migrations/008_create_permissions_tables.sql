CREATE TABLE permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(150) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    status VARCHAR(50) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE position_permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    position_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_position_permissions (position_id, permission_id),
    INDEX idx_position_permissions_position_id (position_id),
    INDEX idx_position_permissions_permission_id (permission_id),
    CONSTRAINT fk_position_permissions_position
        FOREIGN KEY (position_id) REFERENCES positions(id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_position_permissions_permission
        FOREIGN KEY (permission_id) REFERENCES permissions(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (code, name, status) VALUES
('organizations.view', 'View Organizations', 'active'),
('organizations.create', 'Create Organizations', 'active'),
('organizations.update', 'Update Organizations', 'active'),
('organizations.archive', 'Archive Organizations', 'active'),
('franchises.view', 'View Franchises', 'active'),
('franchises.create', 'Create Franchises', 'active'),
('franchises.update', 'Update Franchises', 'active'),
('franchises.archive', 'Archive Franchises', 'active'),
('partner_agencies.view', 'View Partner Agencies', 'active'),
('partner_agencies.create', 'Create Partner Agencies', 'active'),
('partner_agencies.update', 'Update Partner Agencies', 'active'),
('partner_agencies.archive', 'Archive Partner Agencies', 'active'),
('users.view', 'View Users', 'active'),
('users.create', 'Create Users', 'active'),
('users.update', 'Update Users', 'active'),
('users.archive', 'Archive Users', 'active'),
('positions.view', 'View Positions', 'active'),
('positions.create', 'Create Positions', 'active'),
('positions.update', 'Update Positions', 'active'),
('positions.archive', 'Archive Positions', 'active'),
('permissions.view', 'View Permissions', 'active'),
('permissions.assign', 'Assign Permissions', 'active');