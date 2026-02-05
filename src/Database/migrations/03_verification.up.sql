-- Migration: Add email verification fields
-- Up migration

ALTER TABLE `users` 
ADD COLUMN `verified` TINYINT(1) NOT NULL DEFAULT 0 AFTER `blocked`,
ADD COLUMN `verification_token` VARCHAR(64) NULL AFTER `verified`,
ADD COLUMN `token_expires_at` TIMESTAMP NULL AFTER `verification_token`;

-- Add index for faster token lookup
ALTER TABLE `users` ADD INDEX `idx_verification_token` (`verification_token`);
