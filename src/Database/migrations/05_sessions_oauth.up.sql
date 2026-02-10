ALTER TABLE `sessions`
  ADD COLUMN `valid` TINYINT(1) NOT NULL DEFAULT 1 AFTER `expires_at`,
  ADD COLUMN `reference_token` VARCHAR(128) DEFAULT 'auth_grant' AFTER `valid`,
  ADD INDEX `idx_valid` (`valid`);
