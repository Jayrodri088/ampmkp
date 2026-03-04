-- RBAC Migration for Angel Marketplace Admin
-- This migration creates tables for Role-Based Access Control

-- Create roles table
CREATE TABLE IF NOT EXISTS `admin_roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `slug` VARCHAR(50) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `permissions` JSON NOT NULL COMMENT 'JSON object with permission flags',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create admins table (individual admin accounts)
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL COMMENT 'Hashed password',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `last_login` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`),
  INDEX `idx_role_id` (`role_id`),
  INDEX `idx_status` (`status`),
  FOREIGN KEY (`role_id`) REFERENCES `admin_roles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create admin_activity_log table for audit trail
CREATE TABLE IF NOT EXISTS `admin_activity_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` INT UNSIGNED NOT NULL,
  `action` VARCHAR(100) NOT NULL COMMENT 'Action performed (e.g., create_product, update_order)',
  `entity_type` VARCHAR(50) NULL COMMENT 'Type of entity (product, order, category, etc.)',
  `entity_id` VARCHAR(100) NULL COMMENT 'ID of affected entity',
  `details` JSON NULL COMMENT 'Additional details about the action',
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `platform` ENUM('web', 'mobile') NOT NULL DEFAULT 'web',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_admin_id` (`admin_id`),
  INDEX `idx_action` (`action`),
  INDEX `idx_entity` (`entity_type`, `entity_id`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default roles
INSERT INTO `admin_roles` (`name`, `slug`, `description`, `permissions`) VALUES
('Super Admin', 'super_admin', 'Full access to all resources including web and mobile', '{"web":{"products":true,"categories":true,"orders":true,"customers":true,"vendors":true,"ads":true,"settings":true},"mobile":{"products":true,"categories":true,"orders":true,"customers":true,"vendors":true,"ads":true,"settings":true,"api_access":true}}'),
('Website Admin', 'website_admin', 'Access to website management only', '{"web":{"products":true,"categories":true,"orders":true,"customers":true,"vendors":true,"ads":true,"settings":true},"mobile":{"products":false,"categories":false,"orders":false,"customers":false,"vendors":false,"ads":false,"settings":false,"api_access":false}}'),
('Mobile Admin', 'mobile_admin', 'Access to mobile app management only', '{"web":{"products":false,"categories":false,"orders":false,"customers":false,"vendors":false,"ads":false,"settings":false},"mobile":{"products":true,"categories":true,"orders":true,"customers":true,"vendors":true,"ads":true,"settings":true,"api_access":true}}');

-- Insert default super admin account (email: admin@angel.com, password: Admin@2025)
-- Password hash for 'Admin@2025'
INSERT INTO `admins` (`role_id`, `name`, `email`, `password`, `status`) VALUES
(1, 'Super Administrator', 'admin@angel.com', '$2y$12$hJ8G9vXxNq8pY9zK3wLxFuKaW8qJ5f5hN8mL9PqE3rT7sY6cK2v1e', 'active');
