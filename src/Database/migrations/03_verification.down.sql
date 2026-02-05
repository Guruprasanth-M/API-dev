-- Migration: Remove email verification fields
-- Down migration

ALTER TABLE `users` DROP INDEX IF EXISTS `idx_verification_token`;
ALTER TABLE `users` DROP COLUMN IF EXISTS `token_expires_at`;
ALTER TABLE `users` DROP COLUMN IF EXISTS `verification_token`;
ALTER TABLE `users` DROP COLUMN IF EXISTS `verified`;
