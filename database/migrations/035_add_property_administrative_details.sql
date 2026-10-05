ALTER TABLE organization_properties
    ADD COLUMN property_code VARCHAR(31) GENERATED ALWAYS AS (CONCAT('PROP-', ulid)) PERSISTENT,
    ADD UNIQUE KEY uq_organization_properties_code (organization_id, property_code);

ALTER TABLE organization_property_profiles
    ADD COLUMN address_text VARCHAR(1000) NULL,
    ADD COLUMN initial_asking_price DECIMAL(18,4) NULL,
    ADD COLUMN currency_code CHAR(3) NULL,
    ADD CONSTRAINT chk_property_profile_address CHECK (address_text IS NULL OR CHAR_LENGTH(TRIM(address_text)) > 0),
    ADD CONSTRAINT chk_property_profile_price_currency CHECK (
        (initial_asking_price IS NULL AND currency_code IS NULL)
        OR (initial_asking_price IS NOT NULL AND initial_asking_price > 0 AND currency_code IS NOT NULL
            AND BINARY currency_code IN ('EGP', 'USD', 'SAR', 'AED'))
    );
