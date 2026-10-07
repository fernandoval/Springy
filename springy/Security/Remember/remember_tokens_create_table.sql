-- Table used by Springy\Security\Remember\DatabaseRememberTokenStorage (MySQL / MariaDB).
-- The validator is never stored, only its SHA-256 hash.
-- expires_at is an Unix timestamp to avoid time zone ambiguity between PHP and the database.
CREATE TABLE IF NOT EXISTS `_remember_tokens` (
    `selector` CHAR(24) NOT NULL,
    `identity_id` VARCHAR(255) NOT NULL,
    `validator_hash` CHAR(64) NOT NULL,
    `expires_at` BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`selector`),
    KEY `remember_tokens_identity_id_idx` (`identity_id`),
    KEY `remember_tokens_expires_at_idx` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
