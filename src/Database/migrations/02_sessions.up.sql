CREATE TABLE IF NOT EXISTS `sessions` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` int NOT NULL,
  `access_token` varchar(512) NOT NULL UNIQUE,
  `refresh_token` varchar(512) NOT NULL UNIQUE,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_access_token` (`access_token`),
  INDEX `idx_refresh_token` (`refresh_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
