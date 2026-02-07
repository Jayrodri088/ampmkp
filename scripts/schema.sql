-- Angel Marketplace Database Schema
-- MySQL Migration from JSON file storage
-- Created: 2026-02-05

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables if they exist (for clean migration)
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `order_addresses`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `ratings`;
DROP TABLE IF EXISTS `product_images`;
DROP TABLE IF EXISTS `product_features`;
DROP TABLE IF EXISTS `product_colors`;
DROP TABLE IF EXISTS `product_sizes`;
DROP TABLE IF EXISTS `product_prices`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `settings`;

-- ===========================================
-- Categories Table
-- ===========================================
CREATE TABLE `categories` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `image` VARCHAR(500) DEFAULT 'categories/default.jpg',
    `parent_id` INT UNSIGNED DEFAULT 0,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `featured` TINYINT(1) NOT NULL DEFAULT 0,
    `sort_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_category_slug` (`slug`),
    KEY `idx_category_parent` (`parent_id`),
    KEY `idx_category_active` (`active`),
    KEY `idx_category_featured` (`featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================
-- Products Table
-- ===========================================
CREATE TABLE `products` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `category_id` INT UNSIGNED NOT NULL,
    `description` TEXT,
    `image` VARCHAR(500) DEFAULT 'products/placeholder.jpg',
    `stock` INT NOT NULL DEFAULT 0,
    `featured` TINYINT(1) NOT NULL DEFAULT 0,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `has_sizes` TINYINT(1) NOT NULL DEFAULT 0,
    `has_colors` TINYINT(1) NOT NULL DEFAULT 0,
    `sort_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_product_slug` (`slug`),
    KEY `idx_product_category` (`category_id`),
    KEY `idx_product_active` (`active`),
    KEY `idx_product_featured` (`featured`),
    KEY `idx_product_active_featured` (`active`, `featured`),
    CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`)
        REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================
-- Product Prices (Multi-currency support)
-- ===========================================
CREATE TABLE `product_prices` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT UNSIGNED NOT NULL,
    `currency_code` VARCHAR(10) NOT NULL,
    `price` DECIMAL(12,2) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_product_currency` (`product_id`, `currency_code`),
    CONSTRAINT `fk_price_product` FOREIGN KEY (`product_id`)
        REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================
-- Product Sizes
-- ===========================================
CREATE TABLE `product_sizes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT UNSIGNED NOT NULL,
    `size` VARCHAR(50) NOT NULL,
    `sort_order` INT DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_size_product` (`product_id`),
    CONSTRAINT `fk_size_product` FOREIGN KEY (`product_id`)
        REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================
-- Product Colors
-- ===========================================
CREATE TABLE `product_colors` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT UNSIGNED NOT NULL,
    `color` VARCHAR(100) NOT NULL,
    `sort_order` INT DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_color_product` (`product_id`),
    CONSTRAINT `fk_color_product` FOREIGN KEY (`product_id`)
        REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================
-- Product Features
-- ===========================================
CREATE TABLE `product_features` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT UNSIGNED NOT NULL,
    `feature_name` VARCHAR(255) NOT NULL,
    `feature_value` VARCHAR(500) NOT NULL,
    `sort_order` INT DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_feature_product` (`product_id`),
    CONSTRAINT `fk_feature_product` FOREIGN KEY (`product_id`)
        REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================
-- Product Images (Additional images)
-- ===========================================
CREATE TABLE `product_images` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT UNSIGNED NOT NULL,
    `image_path` VARCHAR(500) NOT NULL,
    `sort_order` INT DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_image_product` (`product_id`),
    CONSTRAINT `fk_image_product` FOREIGN KEY (`product_id`)
        REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================
-- Orders Table
-- ===========================================
CREATE TABLE `orders` (
    `id` VARCHAR(50) NOT NULL,
    `customer_name` VARCHAR(255) NOT NULL,
    `customer_email` VARCHAR(255) NOT NULL,
    `customer_phone` VARCHAR(50),
    `customer_country_code` VARCHAR(10),
    `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `shipping_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `tax` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `currency_code` VARCHAR(10) DEFAULT 'GBP',
    `status` ENUM('pending', 'processing', 'shipped', 'completed', 'cancelled', 'refunded') DEFAULT 'pending',
    `payment_method` VARCHAR(50),
    `payment_status` ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    `stripe_payment_intent` VARCHAR(255),
    `shipping_method` ENUM('delivery', 'pickup') DEFAULT 'delivery',
    `notes` TEXT,
    `special_instructions` TEXT,
    `payment_confirmed_by_customer` TINYINT(1) DEFAULT 0,
    `account_holder` VARCHAR(255),
    `date` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_order_status` (`status`),
    KEY `idx_order_payment_status` (`payment_status`),
    KEY `idx_order_date` (`date`),
    KEY `idx_order_email` (`customer_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================
-- Order Addresses
-- ===========================================
CREATE TABLE `order_addresses` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` VARCHAR(50) NOT NULL,
    `address_type` ENUM('shipping', 'billing') NOT NULL,
    `line1` VARCHAR(255),
    `line2` VARCHAR(255),
    `city` VARCHAR(100),
    `state` VARCHAR(100),
    `postcode` VARCHAR(20),
    `country` VARCHAR(100),
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_order_address_type` (`order_id`, `address_type`),
    CONSTRAINT `fk_address_order` FOREIGN KEY (`order_id`)
        REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================
-- Order Items
-- ===========================================
CREATE TABLE `order_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` VARCHAR(50) NOT NULL,
    `product_id` INT UNSIGNED,
    `product_name` VARCHAR(255) NOT NULL,
    `product_slug` VARCHAR(255),
    `product_image` VARCHAR(500),
    `quantity` INT NOT NULL DEFAULT 1,
    `price` DECIMAL(12,2) NOT NULL,
    `subtotal` DECIMAL(12,2) NOT NULL,
    `size` VARCHAR(50),
    `color` VARCHAR(100),
    PRIMARY KEY (`id`),
    KEY `idx_item_order` (`order_id`),
    KEY `idx_item_product` (`product_id`),
    CONSTRAINT `fk_item_order` FOREIGN KEY (`order_id`)
        REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================
-- Ratings / Reviews
-- ===========================================
CREATE TABLE `ratings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT UNSIGNED NOT NULL,
    `rating` TINYINT NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
    `review` TEXT,
    `reviewer_name` VARCHAR(255) NOT NULL,
    `reviewer_email` VARCHAR(255) NOT NULL,
    `date` DATE NOT NULL,
    `verified_purchase` TINYINT(1) DEFAULT 0,
    `approved` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_rating_product` (`product_id`),
    KEY `idx_rating_approved` (`approved`),
    CONSTRAINT `fk_rating_product` FOREIGN KEY (`product_id`)
        REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================
-- Settings (Key-Value store with JSON support)
-- ===========================================
CREATE TABLE `settings` (
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` JSON,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================
-- Ads Table (for advertisements)
-- ===========================================
CREATE TABLE `ads` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `image` VARCHAR(500) NOT NULL,
    `destination_type` ENUM('product', 'category', 'search', 'custom') DEFAULT 'product',
    `product_id` INT UNSIGNED,
    `category_id` INT UNSIGNED,
    `search_query` VARCHAR(255),
    `custom_url` VARCHAR(500),
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ad_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================
-- Newsletter Subscribers
-- ===========================================
CREATE TABLE `newsletter_subscribers` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `subscribed_at` DATETIME NOT NULL,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `active` TINYINT(1) DEFAULT 1,
    `unsubscribed_at` DATETIME,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_newsletter_email` (`email`),
    KEY `idx_newsletter_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ===========================================
-- Create useful views for common queries
-- ===========================================

-- View for products with their primary price (GBP)
CREATE OR REPLACE VIEW `v_products_with_price` AS
SELECT
    p.*,
    COALESCE(pp.price, p.price) as display_price,
    c.name as category_name,
    c.slug as category_slug
FROM products p
LEFT JOIN product_prices pp ON p.id = pp.product_id AND pp.currency_code = 'GBP'
LEFT JOIN categories c ON p.category_id = c.id
WHERE p.active = 1;

-- View for category product counts
CREATE OR REPLACE VIEW `v_category_counts` AS
SELECT
    c.id,
    c.name,
    c.slug,
    c.parent_id,
    COUNT(DISTINCT p.id) as product_count
FROM categories c
LEFT JOIN products p ON c.id = p.category_id AND p.active = 1
WHERE c.active = 1
GROUP BY c.id;

-- View for product rating statistics
CREATE OR REPLACE VIEW `v_product_ratings` AS
SELECT
    product_id,
    COUNT(*) as rating_count,
    ROUND(AVG(rating), 1) as average_rating,
    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
FROM ratings
WHERE approved = 1
GROUP BY product_id;

-- View for order statistics
CREATE OR REPLACE VIEW `v_order_stats` AS
SELECT
    DATE(date) as order_date,
    COUNT(*) as total_orders,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
    SUM(CASE WHEN payment_status = 'completed' THEN total ELSE 0 END) as revenue
FROM orders
GROUP BY DATE(date);
