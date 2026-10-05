CREATE TABLE property_primary_images (
    organization_property_id BIGINT UNSIGNED PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    storage_key CHAR(32) NOT NULL,
    width INT UNSIGNED NOT NULL,
    height INT UNSIGNED NOT NULL,
    byte_size INT UNSIGNED NOT NULL,
    updated_by_user_id BIGINT UNSIGNED NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_property_primary_image_storage (storage_key),
    CONSTRAINT fk_primary_image_property FOREIGN KEY (organization_property_id, organization_id)
        REFERENCES organization_properties(id, organization_id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_primary_image_actor FOREIGN KEY (updated_by_user_id)
        REFERENCES users(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT chk_primary_image_dimensions CHECK (width >= 600 AND height >= 600 AND width <= 8192 AND height <= 8192 AND width * height <= 4000000 AND byte_size BETWEEN 1 AND 10485760)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
