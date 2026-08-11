ALTER TABLE organizations
    ADD COLUMN parent_organization_id BIGINT UNSIGNED NULL AFTER id,
    ADD INDEX idx_organizations_parent_organization_id (parent_organization_id),
    ADD CONSTRAINT fk_organizations_parent_organization
        FOREIGN KEY (parent_organization_id) REFERENCES organizations(id)
        ON DELETE RESTRICT;
