ALTER TABLE `sessions`
  DROP INDEX `idx_valid`,
  DROP COLUMN `reference_token`,
  DROP COLUMN `valid`;
