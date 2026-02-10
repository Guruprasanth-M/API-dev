-- Migration: Add OAuth fields to sessions table
ALTER TABLE `sessions`
ADD COLUMN `valid` TINYINT(1) NOT NULL DEFAULT 1 AFTER `expires_at`,
ADD COLUMN `reference_token` VARCHAR(512) NULL AFTER `valid`;
