ALTER TABLE users
    ADD COLUMN position_id BIGINT UNSIGNED NULL AFTER organization_id,
    ADD INDEX idx_users_position_id (position_id),
    ADD CONSTRAINT fk_users_position
        FOREIGN KEY (position_id) REFERENCES positions(id)
        ON DELETE RESTRICT;