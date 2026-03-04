-- Coupons table for mobile app (Node backend)
-- Run this only if the Node/Prisma migrations have not created the table yet.
-- Normally: npx prisma migrate dev (in backend/) creates this.

CREATE TABLE IF NOT EXISTS `coupons` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL,
  `description` TEXT NULL,
  `discount_type` VARCHAR(50) NOT NULL DEFAULT 'percentage',
  `discount_value` DECIMAL(12, 2) NOT NULL DEFAULT 0,
  `min_purchase` DECIMAL(12, 2) NULL,
  `max_discount` DECIMAL(12, 2) NULL,
  `valid_from` DATETIME(0) NOT NULL,
  `valid_until` DATETIME(0) NOT NULL,
  `usage_limit` INT NULL,
  `used_count` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME(0) NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `coupons_code_key` (`code`),
  INDEX `idx_coupon_code` (`code`),
  INDEX `idx_coupon_is_active` (`is_active`),
  INDEX `idx_coupon_valid_until` (`valid_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
