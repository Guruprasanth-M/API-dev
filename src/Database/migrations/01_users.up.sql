-- Migration: Create users table
-- Up migration

CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `username` varchar(128) NOT NULL UNIQUE,
  `password` varchar(256) NOT NULL,
  `phone` varchar(10) NOT NULL,
  `email` varchar(64) NOT NULL UNIQUE,
  `blocked` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
