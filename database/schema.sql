-- =============================================================================
-- Moal General Suppliers - Truck Inventory & Customer Inquiry Management System
-- Database Schema Definition
-- Target DBMS: MySQL 8.0+ / MariaDB 10.4+
-- Charset: utf8mb4 (Full Unicode support including emojis and special characters)
-- Collation: utf8mb4_unicode_ci
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `moal_truck_db` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `moal_truck_db`;

-- -----------------------------------------------------------------------------
-- 1. Table: admins
-- Stores dealership staff and administrator accounts for dashboard access
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `role` VARCHAR(30) NOT NULL DEFAULT 'administrator',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `last_login` DATETIME NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_admin_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. Table: trucks
-- Stores primary inventory details, specifications, pricing, and availability
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `trucks` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `truck_code` VARCHAR(30) NOT NULL UNIQUE COMMENT 'Unique stock identifier e.g. MOAL-TRK-001',
    `title` VARCHAR(150) NOT NULL COMMENT 'Display headline e.g. Mercedes-Benz Actros 3340 6x4 Tipper',
    `brand` VARCHAR(50) NOT NULL COMMENT 'Manufacturer brand e.g. Mercedes-Benz, Scania, HOWO, MAN',
    `model` VARCHAR(50) NOT NULL COMMENT 'Specific model designation',
    `year_of_manufacture` INT NOT NULL COMMENT 'Production year',
    `price` DECIMAL(12,2) NOT NULL COMMENT 'Price in local currency (e.g. NGN)',
    `mileage` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Mileage in kilometers (km)',
    `tonnage_capacity` DECIMAL(6,2) NOT NULL COMMENT 'Payload / Gross carrying capacity in Metric Tons',
    `transmission` ENUM('Manual', 'Automatic', 'Semi-Automatic') NOT NULL DEFAULT 'Manual',
    `fuel_type` ENUM('Diesel', 'Electric', 'Hybrid', 'Other') NOT NULL DEFAULT 'Diesel',
    `engine_power_hp` INT UNSIGNED NULL COMMENT 'Engine output in horsepower',
    `wheel_configuration` VARCHAR(20) NOT NULL DEFAULT '6x4' COMMENT 'e.g. 4x2, 6x4, 8x4, 6x2',
    `condition_type` ENUM('Brand New', 'Foreign Used', 'Locally Used') NOT NULL DEFAULT 'Foreign Used',
    `purpose_category` ENUM('Heavy Haulage', 'Construction & Mining', 'Distribution & Logistics', 'Agriculture & Farming', 'Specialized Transport') NOT NULL,
    `availability_status` ENUM('Available', 'Reserved', 'Sold', 'Maintenance') NOT NULL DEFAULT 'Available',
    `featured` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = Featured on homepage showcase',
    `description` TEXT NULL COMMENT 'Detailed overview and condition notes',
    `specifications_json` JSON NULL COMMENT 'Key-value pairs for technical specs',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_brand` (`brand`),
    INDEX `idx_purpose` (`purpose_category`),
    INDEX `idx_availability` (`availability_status`),
    INDEX `idx_price` (`price`),
    INDEX `idx_tonnage` (`tonnage_capacity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. Table: truck_images
-- Stores photographs associated with each truck in inventory
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `truck_images` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `truck_id` INT UNSIGNED NOT NULL,
    `image_path` VARCHAR(255) NOT NULL COMMENT 'Relative path within assets/images/trucks/',
    `caption` VARCHAR(150) NULL,
    `is_primary` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = Main thumbnail for catalogue cards',
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_truck_images_truck_id` 
        FOREIGN KEY (`truck_id`) REFERENCES `trucks` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_truck_primary` (`truck_id`, `is_primary`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. Table: inquiries
-- Tracks customer general inquiries, specific truck inquiries, and custom requests
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `inquiries` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `inquiry_code` VARCHAR(30) NOT NULL UNIQUE COMMENT 'Tracking code e.g. INQ-2026-0001',
    `truck_id` INT UNSIGNED NULL COMMENT 'Linked truck if inquired from specific truck page',
    `inquiry_type` ENUM('Specific Truck', 'Custom Request', 'Recommendation Followup', 'General Inquiry') NOT NULL DEFAULT 'General Inquiry',
    `customer_name` VARCHAR(100) NOT NULL,
    `customer_email` VARCHAR(100) NOT NULL,
    `customer_phone` VARCHAR(30) NOT NULL,
    `preferred_budget_min` DECIMAL(12,2) NULL,
    `preferred_budget_max` DECIMAL(12,2) NULL,
    `preferred_tonnage` DECIMAL(6,2) NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('Pending', 'In Review', 'Contacted', 'Resolved', 'Cancelled') NOT NULL DEFAULT 'Pending',
    `admin_notes` TEXT NULL COMMENT 'Internal notes by dealership staff',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_inquiries_truck_id` 
        FOREIGN KEY (`truck_id`) REFERENCES `trucks` (`id`) 
        ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_inquiry_status` (`status`),
    INDEX `idx_inquiry_type` (`inquiry_type`),
    INDEX `idx_customer_email` (`customer_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
