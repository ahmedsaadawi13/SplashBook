-- FILE: /database.sql
-- SplashBook - Multi-tenant Appointment Booking SaaS
-- Database Schema for MySQL/InnoDB
-- Compatible with MySQL 5.7+

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Create database (optional - uncomment if needed)
-- CREATE DATABASE IF NOT EXISTS splashbook DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE splashbook;

-- ====================================================================
-- SUBSCRIPTION PLANS
-- ====================================================================

CREATE TABLE IF NOT EXISTS `plans` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `price_monthly` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `price_yearly` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `currency` VARCHAR(3) NOT NULL DEFAULT 'USD',
  `max_staff` INT NOT NULL DEFAULT 5,
  `max_services` INT NOT NULL DEFAULT 20,
  `max_bookings_per_month` INT NOT NULL DEFAULT 100,
  `features` TEXT COMMENT 'JSON array of features',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- TENANTS (BUSINESSES)
-- ====================================================================

CREATE TABLE IF NOT EXISTS `tenants` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `business_name` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(200) NOT NULL UNIQUE COMMENT 'URL-friendly identifier',
  `logo` VARCHAR(255) DEFAULT NULL,
  `description` TEXT,
  `address` VARCHAR(255),
  `city` VARCHAR(100),
  `state` VARCHAR(100),
  `postal_code` VARCHAR(20),
  `country` VARCHAR(100) DEFAULT 'USA',
  `phone` VARCHAR(50),
  `email` VARCHAR(150),
  `website` VARCHAR(255),
  `default_timezone` VARCHAR(50) NOT NULL DEFAULT 'UTC',
  `default_currency` VARCHAR(3) NOT NULL DEFAULT 'USD',
  `status` ENUM('active', 'suspended', 'canceled') NOT NULL DEFAULT 'active',
  `api_key` VARCHAR(64) NOT NULL UNIQUE COMMENT 'For API authentication',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_slug` (`slug`),
  INDEX `idx_status` (`status`),
  INDEX `idx_api_key` (`api_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- TENANT SUBSCRIPTIONS
-- ====================================================================

CREATE TABLE IF NOT EXISTS `tenant_subscriptions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT UNSIGNED NOT NULL,
  `plan_id` INT UNSIGNED NOT NULL,
  `status` ENUM('trialing', 'active', 'past_due', 'canceled', 'expired') NOT NULL DEFAULT 'trialing',
  `trial_ends_at` TIMESTAMP NULL DEFAULT NULL,
  `current_period_start` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `current_period_end` TIMESTAMP NOT NULL,
  `canceled_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`plan_id`) REFERENCES `plans`(`id`) ON DELETE RESTRICT,
  INDEX `idx_tenant_status` (`tenant_id`, `status`),
  INDEX `idx_period_end` (`current_period_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- USAGE TRACKING
-- ====================================================================

CREATE TABLE IF NOT EXISTS `usage_tracking` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT UNSIGNED NOT NULL,
  `period_start` DATE NOT NULL,
  `period_end` DATE NOT NULL,
  `staff_count` INT NOT NULL DEFAULT 0,
  `service_count` INT NOT NULL DEFAULT 0,
  `booking_count` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_tenant_period` (`tenant_id`, `period_start`),
  INDEX `idx_period` (`period_start`, `period_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- INVOICES
-- ====================================================================

CREATE TABLE IF NOT EXISTS `invoices` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT UNSIGNED NOT NULL,
  `subscription_id` INT UNSIGNED NOT NULL,
  `invoice_number` VARCHAR(50) NOT NULL UNIQUE,
  `amount` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(3) NOT NULL DEFAULT 'USD',
  `status` ENUM('draft', 'pending', 'paid', 'overdue', 'canceled') NOT NULL DEFAULT 'pending',
  `issued_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `due_at` TIMESTAMP NOT NULL,
  `paid_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`subscription_id`) REFERENCES `tenant_subscriptions`(`id`) ON DELETE CASCADE,
  INDEX `idx_tenant_status` (`tenant_id`, `status`),
  INDEX `idx_invoice_number` (`invoice_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- PAYMENTS
-- ====================================================================

CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT UNSIGNED NOT NULL,
  `invoice_id` INT UNSIGNED NOT NULL,
  `payment_method` ENUM('credit_card', 'bank_transfer', 'paypal', 'other') NOT NULL DEFAULT 'credit_card',
  `amount` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(3) NOT NULL DEFAULT 'USD',
  `transaction_id` VARCHAR(100) COMMENT 'External payment gateway transaction ID',
  `status` ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
  `paid_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE,
  INDEX `idx_tenant` (`tenant_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_transaction` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- USERS
-- ====================================================================

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT UNSIGNED NULL COMMENT 'NULL for platform admins',
  `email` VARCHAR(150) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('platform_admin', 'tenant_admin', 'staff') NOT NULL DEFAULT 'staff',
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(50),
  `timezone` VARCHAR(50) NOT NULL DEFAULT 'UTC',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_email_tenant` (`email`, `tenant_id`),
  INDEX `idx_tenant_role` (`tenant_id`, `role`),
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- SERVICE CATEGORIES
-- ====================================================================

CREATE TABLE IF NOT EXISTS `service_categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `display_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  INDEX `idx_tenant_active` (`tenant_id`, `is_active`),
  INDEX `idx_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- SERVICES
-- ====================================================================

CREATE TABLE IF NOT EXISTS `services` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT UNSIGNED NOT NULL,
  `category_id` INT UNSIGNED NULL,
  `name` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `duration_minutes` INT NOT NULL DEFAULT 30,
  `base_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `currency` VARCHAR(3) NOT NULL DEFAULT 'USD',
  `color` VARCHAR(7) DEFAULT '#3498db' COMMENT 'Hex color for calendar',
  `is_online` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = virtual/online, 0 = in-person',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `service_categories`(`id`) ON DELETE SET NULL,
  INDEX `idx_tenant_active` (`tenant_id`, `is_active`),
  INDEX `idx_category` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- STAFF MEMBERS
-- ====================================================================

CREATE TABLE IF NOT EXISTS `staff` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NULL COMMENT 'Link to users table if staff has login',
  `title` VARCHAR(100) COMMENT 'Job title/role (e.g., Dentist, Stylist)',
  `profile_picture` VARCHAR(255),
  `bio` TEXT,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `accepts_bookings` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_tenant_active` (`tenant_id`, `is_active`),
  INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- STAFF SERVICES (which services a staff member can provide)
-- ====================================================================

CREATE TABLE IF NOT EXISTS `staff_services` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `staff_id` INT UNSIGNED NOT NULL,
  `service_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_staff_service` (`staff_id`, `service_id`),
  INDEX `idx_service` (`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- STAFF WORKING HOURS
-- ====================================================================

CREATE TABLE IF NOT EXISTS `staff_working_hours` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `staff_id` INT UNSIGNED NOT NULL,
  `day_of_week` TINYINT NOT NULL COMMENT '0=Sunday, 1=Monday, ..., 6=Saturday',
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE,
  INDEX `idx_staff_day` (`staff_id`, `day_of_week`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- STAFF BREAKS
-- ====================================================================

CREATE TABLE IF NOT EXISTS `staff_breaks` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `staff_id` INT UNSIGNED NOT NULL,
  `day_of_week` TINYINT NOT NULL COMMENT '0=Sunday, 1=Monday, ..., 6=Saturday',
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE,
  INDEX `idx_staff_day` (`staff_id`, `day_of_week`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- HOLIDAYS / BLACKOUT DATES
-- ====================================================================

CREATE TABLE IF NOT EXISTS `holidays` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `date` DATE NOT NULL,
  `is_recurring` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'If 1, recurs annually',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  INDEX `idx_tenant_date` (`tenant_id`, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- CLIENTS
-- ====================================================================

CREATE TABLE IF NOT EXISTS `clients` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT UNSIGNED NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150),
  `phone` VARCHAR(50),
  `timezone` VARCHAR(50) NOT NULL DEFAULT 'UTC',
  `notes` TEXT COMMENT 'Internal notes about client',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  INDEX `idx_tenant_email` (`tenant_id`, `email`),
  INDEX `idx_tenant_phone` (`tenant_id`, `phone`),
  INDEX `idx_tenant_name` (`tenant_id`, `last_name`, `first_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- BOOKINGS
-- ====================================================================

CREATE TABLE IF NOT EXISTS `bookings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT UNSIGNED NOT NULL,
  `service_id` INT UNSIGNED NOT NULL,
  `staff_id` INT UNSIGNED NULL COMMENT 'NULL if auto-assigned or any staff',
  `client_id` INT UNSIGNED NULL,
  `client_name` VARCHAR(200) COMMENT 'If client not in system',
  `client_email` VARCHAR(150),
  `client_phone` VARCHAR(50),
  `booking_date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `timezone` VARCHAR(50) NOT NULL DEFAULT 'UTC',
  `status` ENUM('pending', 'confirmed', 'completed', 'canceled', 'no_show') NOT NULL DEFAULT 'pending',
  `payment_status` ENUM('unpaid', 'paid', 'refunded') NOT NULL DEFAULT 'unpaid',
  `payment_method` ENUM('at_location', 'online') DEFAULT 'at_location',
  `internal_notes` TEXT COMMENT 'Staff-only notes',
  `public_notes` TEXT COMMENT 'Notes visible to client',
  `reference_number` VARCHAR(50) NOT NULL UNIQUE,
  `created_by_user_id` INT UNSIGNED NULL COMMENT 'User who created the booking',
  `canceled_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_tenant_date_status` (`tenant_id`, `booking_date`, `status`),
  INDEX `idx_staff_date` (`staff_id`, `booking_date`),
  INDEX `idx_client` (`client_id`),
  INDEX `idx_reference` (`reference_number`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- EMAIL LOGS (simulated emails)
-- ====================================================================

CREATE TABLE IF NOT EXISTS `email_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT UNSIGNED NULL,
  `to_email` VARCHAR(150) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `body` TEXT NOT NULL,
  `type` VARCHAR(50) COMMENT 'e.g., booking_confirmation, reminder, status_change',
  `related_booking_id` INT UNSIGNED NULL,
  `sent_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`related_booking_id`) REFERENCES `bookings`(`id`) ON DELETE SET NULL,
  INDEX `idx_tenant_type` (`tenant_id`, `type`),
  INDEX `idx_booking` (`related_booking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- BUSINESS SETTINGS
-- ====================================================================

CREATE TABLE IF NOT EXISTS `business_settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT UNSIGNED NOT NULL,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_tenant_key` (`tenant_id`, `setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- SEED DATA
-- ====================================================================

-- Insert Plans
INSERT INTO `plans` (`name`, `description`, `price_monthly`, `price_yearly`, `max_staff`, `max_services`, `max_bookings_per_month`, `features`) VALUES
('Starter', 'Perfect for solo practitioners and small teams', 29.00, 290.00, 3, 10, 50, '["Online booking page", "Email notifications", "Basic calendar", "Mobile responsive"]'),
('Professional', 'Ideal for growing businesses', 79.00, 790.00, 10, 50, 300, '["All Starter features", "Multiple staff schedules", "Advanced analytics", "API access", "Custom branding"]'),
('Enterprise', 'For large organizations with complex needs', 199.00, 1990.00, 50, 200, 2000, '["All Professional features", "Priority support", "Advanced integrations", "Custom domains", "Dedicated account manager"]');

-- Insert Tenants
INSERT INTO `tenants` (`business_name`, `slug`, `description`, `address`, `city`, `state`, `postal_code`, `phone`, `email`, `default_timezone`, `api_key`) VALUES
('Bella Beauty Salon', 'bella-beauty', 'Premier hair and beauty salon specializing in modern cuts and coloring', '123 Fashion Ave', 'New York', 'NY', '10001', '+1-212-555-0101', 'info@bellasalon.example', 'America/New_York', 'bb_live_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6'),
('Healthy Smiles Dental', 'healthy-smiles', 'Family dentistry with state-of-the-art technology and caring professionals', '456 Health Blvd', 'Los Angeles', 'CA', '90001', '+1-310-555-0202', 'contact@healthysmiles.example', 'America/Los_Angeles', 'hs_live_z6y5x4w3v2u1t0s9r8q7p6o5n4m3l2k1j0i9h8g7f6e5d4c3b2a1');

-- Insert Subscriptions
INSERT INTO `tenant_subscriptions` (`tenant_id`, `plan_id`, `status`, `trial_ends_at`, `current_period_start`, `current_period_end`) VALUES
(1, 2, 'active', NULL, DATE_SUB(NOW(), INTERVAL 15 DAY), DATE_ADD(NOW(), INTERVAL 15 DAY)),
(2, 3, 'active', NULL, DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_ADD(NOW(), INTERVAL 20 DAY));

-- Insert Usage Tracking
INSERT INTO `usage_tracking` (`tenant_id`, `period_start`, `period_end`, `staff_count`, `service_count`, `booking_count`) VALUES
(1, DATE_FORMAT(NOW(), '%Y-%m-01'), LAST_DAY(NOW()), 4, 12, 87),
(2, DATE_FORMAT(NOW(), '%Y-%m-01'), LAST_DAY(NOW()), 5, 15, 156);

-- Insert Invoices
INSERT INTO `invoices` (`tenant_id`, `subscription_id`, `invoice_number`, `amount`, `status`, `issued_at`, `due_at`, `paid_at`) VALUES
(1, 1, 'INV-2025-001', 79.00, 'paid', DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 25 DAY), DATE_SUB(NOW(), INTERVAL 24 DAY)),
(1, 1, 'INV-2025-002', 79.00, 'paid', NOW(), DATE_ADD(NOW(), INTERVAL 5 DAY), NOW()),
(2, 2, 'INV-2025-003', 199.00, 'paid', DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 25 DAY), DATE_SUB(NOW(), INTERVAL 23 DAY)),
(2, 2, 'INV-2025-004', 199.00, 'pending', NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), NULL);

-- Insert Payments
INSERT INTO `payments` (`tenant_id`, `invoice_id`, `payment_method`, `amount`, `transaction_id`, `status`, `paid_at`) VALUES
(1, 1, 'credit_card', 79.00, 'txn_sim_1a2b3c4d', 'completed', DATE_SUB(NOW(), INTERVAL 24 DAY)),
(1, 2, 'credit_card', 79.00, 'txn_sim_5e6f7g8h', 'completed', NOW()),
(2, 3, 'credit_card', 199.00, 'txn_sim_9i0j1k2l', 'completed', DATE_SUB(NOW(), INTERVAL 23 DAY));

-- Insert Platform Admin User
INSERT INTO `users` (`tenant_id`, `email`, `password_hash`, `role`, `first_name`, `last_name`, `phone`) VALUES
(NULL, 'admin@splashbook.example', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'platform_admin', 'System', 'Administrator', '+1-555-0000');
-- Password: password

-- Insert Tenant Admin Users
INSERT INTO `users` (`tenant_id`, `email`, `password_hash`, `role`, `first_name`, `last_name`, `phone`, `timezone`) VALUES
(1, 'admin@bellasalon.example', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tenant_admin', 'Isabella', 'Martinez', '+1-212-555-0101', 'America/New_York'),
(2, 'admin@healthysmiles.example', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tenant_admin', 'Dr. Michael', 'Chen', '+1-310-555-0202', 'America/Los_Angeles');
-- Password: password

-- Insert Staff Users
INSERT INTO `users` (`tenant_id`, `email`, `password_hash`, `role`, `first_name`, `last_name`, `phone`, `timezone`) VALUES
(1, 'sarah@bellasalon.example', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'Sarah', 'Johnson', '+1-212-555-0103', 'America/New_York'),
(1, 'marcus@bellasalon.example', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'Marcus', 'Williams', '+1-212-555-0104', 'America/New_York'),
(1, 'emily@bellasalon.example', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'Emily', 'Davis', '+1-212-555-0105', 'America/New_York'),
(2, 'dr.anderson@healthysmiles.example', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'Dr. Lisa', 'Anderson', '+1-310-555-0203', 'America/Los_Angeles'),
(2, 'dr.patel@healthysmiles.example', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'Dr. Raj', 'Patel', '+1-310-555-0204', 'America/Los_Angeles'),
(2, 'jessica@healthysmiles.example', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'Jessica', 'Taylor', '+1-310-555-0205', 'America/Los_Angeles');

-- Insert Service Categories - Bella Beauty Salon
INSERT INTO `service_categories` (`tenant_id`, `name`, `description`, `display_order`) VALUES
(1, 'Hair Services', 'Professional hair cutting, styling, and coloring', 1),
(1, 'Nail Services', 'Manicures, pedicures, and nail art', 2),
(1, 'Spa Services', 'Relaxing spa treatments and massages', 3);

-- Insert Service Categories - Healthy Smiles Dental
INSERT INTO `service_categories` (`tenant_id`, `name`, `description`, `display_order`) VALUES
(2, 'General Dentistry', 'Routine checkups and preventive care', 1),
(2, 'Cosmetic Dentistry', 'Enhance your smile with cosmetic procedures', 2),
(2, 'Specialized Treatments', 'Advanced dental procedures', 3);

-- Insert Services - Bella Beauty Salon
INSERT INTO `services` (`tenant_id`, `category_id`, `name`, `description`, `duration_minutes`, `base_price`, `color`) VALUES
(1, 1, 'Women\'s Haircut', 'Professional haircut with wash and blow-dry', 60, 75.00, '#e74c3c'),
(1, 1, 'Men\'s Haircut', 'Classic or modern men\'s haircut', 30, 35.00, '#3498db'),
(1, 1, 'Hair Coloring', 'Full color or highlights with professional products', 120, 150.00, '#9b59b6'),
(1, 1, 'Blowout', 'Wash and professional blow-dry styling', 45, 45.00, '#f39c12'),
(1, 2, 'Manicure', 'Classic manicure with polish', 45, 35.00, '#e91e63'),
(1, 2, 'Pedicure', 'Relaxing pedicure with polish', 60, 50.00, '#ff5722'),
(1, 2, 'Gel Nails', 'Long-lasting gel manicure', 60, 55.00, '#ec407a'),
(1, 3, 'Facial Treatment', 'Deep cleansing facial with massage', 60, 85.00, '#8bc34a'),
(1, 3, 'Back Massage', 'Therapeutic 30-minute back massage', 30, 60.00, '#00bcd4'),
(1, 3, 'Full Body Massage', 'Relaxing full body massage', 90, 120.00, '#009688');

-- Insert Services - Healthy Smiles Dental
INSERT INTO `services` (`tenant_id`, `category_id`, `name`, `description`, `duration_minutes`, `base_price`, `color`) VALUES
(2, 4, 'General Checkup', 'Comprehensive dental examination', 30, 95.00, '#2196f3'),
(2, 4, 'Teeth Cleaning', 'Professional cleaning and polishing', 45, 125.00, '#03a9f4'),
(2, 4, 'Dental X-Rays', 'Digital x-rays for diagnosis', 15, 75.00, '#00bcd4'),
(2, 4, 'Cavity Filling', 'Tooth-colored composite filling', 60, 185.00, '#4caf50'),
(2, 5, 'Teeth Whitening', 'Professional whitening treatment', 90, 395.00, '#ffeb3b'),
(2, 5, 'Veneers Consultation', 'Consultation for porcelain veneers', 30, 0.00, '#ffc107'),
(2, 6, 'Root Canal', 'Root canal therapy', 90, 895.00, '#ff5722'),
(2, 6, 'Crown Placement', 'Dental crown installation', 120, 1250.00, '#f44336'),
(2, 6, 'Tooth Extraction', 'Simple or surgical tooth extraction', 45, 225.00, '#e91e63');

-- Insert Staff - Bella Beauty Salon
INSERT INTO `staff` (`tenant_id`, `user_id`, `title`, `bio`) VALUES
(1, 3, 'Senior Hair Stylist', 'Award-winning stylist with 12 years of experience in cutting-edge hair design'),
(1, 4, 'Color Specialist', 'Expert in balayage, ombre, and modern coloring techniques'),
(1, 5, 'Nail Technician & Aesthetician', 'Certified in advanced nail art and skincare treatments');

-- Insert Staff - Healthy Smiles Dental
INSERT INTO `staff` (`tenant_id`, `user_id`, `title`, `bio`) VALUES
(2, 6, 'General Dentist', 'DDS from UCLA, specializing in family dentistry and preventive care'),
(2, 7, 'Cosmetic & Restorative Dentist', 'DMD with advanced training in cosmetic procedures and implants'),
(2, 8, 'Dental Hygienist', 'Registered dental hygienist providing thorough cleanings and patient education');

-- Insert Staff Services - Bella Beauty Salon
INSERT INTO `staff_services` (`staff_id`, `service_id`) VALUES
-- Sarah (Hair Stylist)
(1, 1), (1, 2), (1, 4),
-- Marcus (Color Specialist)
(2, 1), (2, 3), (2, 4),
-- Emily (Nail Tech & Aesthetician)
(3, 5), (3, 6), (3, 7), (3, 8), (3, 9), (3, 10);

-- Insert Staff Services - Healthy Smiles Dental
INSERT INTO `staff_services` (`staff_id`, `service_id`) VALUES
-- Dr. Anderson
(4, 11), (4, 12), (4, 13), (4, 14),
-- Dr. Patel
(5, 11), (5, 14), (5, 15), (5, 16), (5, 17), (5, 18), (5, 19),
-- Jessica (Hygienist)
(6, 12), (6, 13);

-- Insert Staff Working Hours - Bella Beauty Salon Staff
-- Sarah: Mon-Fri 9am-6pm, Sat 10am-4pm
INSERT INTO `staff_working_hours` (`staff_id`, `day_of_week`, `start_time`, `end_time`) VALUES
(1, 1, '09:00:00', '18:00:00'),
(1, 2, '09:00:00', '18:00:00'),
(1, 3, '09:00:00', '18:00:00'),
(1, 4, '09:00:00', '18:00:00'),
(1, 5, '09:00:00', '18:00:00'),
(1, 6, '10:00:00', '16:00:00');

-- Marcus: Tue-Sat 10am-7pm
INSERT INTO `staff_working_hours` (`staff_id`, `day_of_week`, `start_time`, `end_time`) VALUES
(2, 2, '10:00:00', '19:00:00'),
(2, 3, '10:00:00', '19:00:00'),
(2, 4, '10:00:00', '19:00:00'),
(2, 5, '10:00:00', '19:00:00'),
(2, 6, '10:00:00', '19:00:00');

-- Emily: Mon-Sat 9am-5pm
INSERT INTO `staff_working_hours` (`staff_id`, `day_of_week`, `start_time`, `end_time`) VALUES
(3, 1, '09:00:00', '17:00:00'),
(3, 2, '09:00:00', '17:00:00'),
(3, 3, '09:00:00', '17:00:00'),
(3, 4, '09:00:00', '17:00:00'),
(3, 5, '09:00:00', '17:00:00'),
(3, 6, '09:00:00', '17:00:00');

-- Insert Staff Working Hours - Healthy Smiles Dental Staff
-- Dr. Anderson: Mon-Fri 8am-5pm
INSERT INTO `staff_working_hours` (`staff_id`, `day_of_week`, `start_time`, `end_time`) VALUES
(4, 1, '08:00:00', '17:00:00'),
(4, 2, '08:00:00', '17:00:00'),
(4, 3, '08:00:00', '17:00:00'),
(4, 4, '08:00:00', '17:00:00'),
(4, 5, '08:00:00', '17:00:00');

-- Dr. Patel: Tue-Sat 9am-6pm
INSERT INTO `staff_working_hours` (`staff_id`, `day_of_week`, `start_time`, `end_time`) VALUES
(5, 2, '09:00:00', '18:00:00'),
(5, 3, '09:00:00', '18:00:00'),
(5, 4, '09:00:00', '18:00:00'),
(5, 5, '09:00:00', '18:00:00'),
(5, 6, '09:00:00', '18:00:00');

-- Jessica: Mon-Fri 8am-4pm
INSERT INTO `staff_working_hours` (`staff_id`, `day_of_week`, `start_time`, `end_time`) VALUES
(6, 1, '08:00:00', '16:00:00'),
(6, 2, '08:00:00', '16:00:00'),
(6, 3, '08:00:00', '16:00:00'),
(6, 4, '08:00:00', '16:00:00'),
(6, 5, '08:00:00', '16:00:00');

-- Insert Staff Breaks (lunch breaks)
INSERT INTO `staff_breaks` (`staff_id`, `day_of_week`, `start_time`, `end_time`) VALUES
-- Sarah's lunch: Mon-Fri 1pm-2pm
(1, 1, '13:00:00', '14:00:00'),
(1, 2, '13:00:00', '14:00:00'),
(1, 3, '13:00:00', '14:00:00'),
(1, 4, '13:00:00', '14:00:00'),
(1, 5, '13:00:00', '14:00:00'),
-- Dr. Anderson's lunch: Mon-Fri 12pm-1pm
(4, 1, '12:00:00', '13:00:00'),
(4, 2, '12:00:00', '13:00:00'),
(4, 3, '12:00:00', '13:00:00'),
(4, 4, '12:00:00', '13:00:00'),
(4, 5, '12:00:00', '13:00:00'),
-- Dr. Patel's lunch: Tue-Sat 1pm-2pm
(5, 2, '13:00:00', '14:00:00'),
(5, 3, '13:00:00', '14:00:00'),
(5, 4, '13:00:00', '14:00:00'),
(5, 5, '13:00:00', '14:00:00'),
(5, 6, '13:00:00', '14:00:00');

-- Insert Holidays
INSERT INTO `holidays` (`tenant_id`, `name`, `date`, `is_recurring`) VALUES
(1, 'New Year\'s Day', '2025-01-01', 1),
(1, 'Independence Day', '2025-07-04', 1),
(1, 'Thanksgiving', '2025-11-27', 0),
(1, 'Christmas', '2025-12-25', 1),
(2, 'New Year\'s Day', '2025-01-01', 1),
(2, 'Memorial Day', '2025-05-26', 0),
(2, 'Independence Day', '2025-07-04', 1),
(2, 'Labor Day', '2025-09-01', 0),
(2, 'Thanksgiving', '2025-11-27', 0),
(2, 'Christmas', '2025-12-25', 1);

-- Insert Sample Clients - Bella Beauty Salon
INSERT INTO `clients` (`tenant_id`, `first_name`, `last_name`, `email`, `phone`, `timezone`) VALUES
(1, 'Jennifer', 'Anderson', 'jennifer.anderson@example.com', '+1-212-555-1001', 'America/New_York'),
(1, 'Michael', 'Brown', 'michael.brown@example.com', '+1-212-555-1002', 'America/New_York'),
(1, 'Ashley', 'Garcia', 'ashley.garcia@example.com', '+1-212-555-1003', 'America/New_York'),
(1, 'David', 'Miller', 'david.miller@example.com', '+1-212-555-1004', 'America/New_York'),
(1, 'Rachel', 'Wilson', 'rachel.wilson@example.com', '+1-212-555-1005', 'America/New_York'),
(1, 'Christopher', 'Moore', 'chris.moore@example.com', '+1-212-555-1006', 'America/New_York'),
(1, 'Amanda', 'Taylor', 'amanda.taylor@example.com', '+1-212-555-1007', 'America/New_York'),
(1, 'James', 'Thomas', 'james.thomas@example.com', '+1-212-555-1008', 'America/New_York');

-- Insert Sample Clients - Healthy Smiles Dental
INSERT INTO `clients` (`tenant_id`, `first_name`, `last_name`, `email`, `phone`, `timezone`) VALUES
(2, 'Robert', 'Johnson', 'robert.johnson@example.com', '+1-310-555-2001', 'America/Los_Angeles'),
(2, 'Maria', 'Rodriguez', 'maria.rodriguez@example.com', '+1-310-555-2002', 'America/Los_Angeles'),
(2, 'William', 'Martinez', 'william.martinez@example.com', '+1-310-555-2003', 'America/Los_Angeles'),
(2, 'Linda', 'Hernandez', 'linda.hernandez@example.com', '+1-310-555-2004', 'America/Los_Angeles'),
(2, 'Richard', 'Lopez', 'richard.lopez@example.com', '+1-310-555-2005', 'America/Los_Angeles'),
(2, 'Barbara', 'Gonzalez', 'barbara.gonzalez@example.com', '+1-310-555-2006', 'America/Los_Angeles'),
(2, 'Joseph', 'Wilson', 'joseph.wilson@example.com', '+1-310-555-2007', 'America/Los_Angeles'),
(2, 'Susan', 'Lee', 'susan.lee@example.com', '+1-310-555-2008', 'America/Los_Angeles');

-- Insert Sample Bookings - Bella Beauty Salon (various dates and statuses)
INSERT INTO `bookings` (`tenant_id`, `service_id`, `staff_id`, `client_id`, `booking_date`, `start_time`, `end_time`, `timezone`, `status`, `payment_status`, `reference_number`, `created_by_user_id`) VALUES
-- Today's bookings
(1, 1, 1, 1, CURDATE(), '10:00:00', '11:00:00', 'America/New_York', 'confirmed', 'unpaid', 'BOOK-BB-001', 2),
(1, 5, 3, 2, CURDATE(), '11:00:00', '11:45:00', 'America/New_York', 'confirmed', 'paid', 'BOOK-BB-002', 2),
(1, 3, 2, 3, CURDATE(), '14:00:00', '16:00:00', 'America/New_York', 'confirmed', 'unpaid', 'BOOK-BB-003', 2),
(1, 9, 3, 4, CURDATE(), '13:00:00', '13:30:00', 'America/New_York', 'confirmed', 'unpaid', 'BOOK-BB-004', 3),
-- Tomorrow's bookings
(1, 2, 1, 5, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', '09:30:00', 'America/New_York', 'confirmed', 'unpaid', 'BOOK-BB-005', 2),
(1, 6, 3, 6, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', '11:00:00', 'America/New_York', 'confirmed', 'unpaid', 'BOOK-BB-006', 2),
(1, 1, 1, 7, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '15:00:00', '16:00:00', 'America/New_York', 'pending', 'unpaid', 'BOOK-BB-007', NULL),
-- Past bookings
(1, 4, 2, 1, DATE_SUB(CURDATE(), INTERVAL 3 DAY), '11:00:00', '11:45:00', 'America/New_York', 'completed', 'paid', 'BOOK-BB-008', 2),
(1, 7, 3, 2, DATE_SUB(CURDATE(), INTERVAL 5 DAY), '14:00:00', '15:00:00', 'America/New_York', 'completed', 'paid', 'BOOK-BB-009', 2),
(1, 1, 1, 3, DATE_SUB(CURDATE(), INTERVAL 7 DAY), '10:00:00', '11:00:00', 'America/New_York', 'no_show', 'unpaid', 'BOOK-BB-010', 2),
(1, 10, 3, 8, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '09:00:00', '10:30:00', 'America/New_York', 'completed', 'paid', 'BOOK-BB-011', 3),
-- Canceled booking
(1, 3, 2, 5, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '13:00:00', '15:00:00', 'America/New_York', 'canceled', 'unpaid', 'BOOK-BB-012', 2);

-- Insert Sample Bookings - Healthy Smiles Dental
INSERT INTO `bookings` (`tenant_id`, `service_id`, `staff_id`, `client_id`, `booking_date`, `start_time`, `end_time`, `timezone`, `status`, `payment_status`, `reference_number`, `created_by_user_id`) VALUES
-- Today's bookings
(2, 11, 4, 9, CURDATE(), '08:00:00', '08:30:00', 'America/Los_Angeles', 'confirmed', 'unpaid', 'BOOK-HS-001', 7),
(2, 12, 6, 10, CURDATE(), '09:00:00', '09:45:00', 'America/Los_Angeles', 'confirmed', 'paid', 'BOOK-HS-002', 7),
(2, 14, 5, 11, CURDATE(), '10:00:00', '11:00:00', 'America/Los_Angeles', 'confirmed', 'unpaid', 'BOOK-HS-003', 7),
(2, 11, 4, 12, CURDATE(), '13:00:00', '13:30:00', 'America/Los_Angeles', 'confirmed', 'unpaid', 'BOOK-HS-004', 6),
-- Tomorrow's bookings
(2, 12, 6, 13, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:00:00', '08:45:00', 'America/Los_Angeles', 'confirmed', 'unpaid', 'BOOK-HS-005', 7),
(2, 15, 5, 14, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', '11:30:00', 'America/Los_Angeles', 'confirmed', 'paid', 'BOOK-HS-006', 7),
(2, 17, 5, 15, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '14:00:00', '15:30:00', 'America/Los_Angeles', 'pending', 'unpaid', 'BOOK-HS-007', NULL),
-- Next week bookings
(2, 18, 5, 9, DATE_ADD(CURDATE(), INTERVAL 5 DAY), '09:00:00', '11:00:00', 'America/Los_Angeles', 'confirmed', 'unpaid', 'BOOK-HS-008', 7),
-- Past bookings
(2, 11, 4, 10, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '08:00:00', '08:30:00', 'America/Los_Angeles', 'completed', 'paid', 'BOOK-HS-009', 7),
(2, 12, 6, 11, DATE_SUB(CURDATE(), INTERVAL 4 DAY), '09:00:00', '09:45:00', 'America/Los_Angeles', 'completed', 'paid', 'BOOK-HS-010', 7),
(2, 14, 4, 12, DATE_SUB(CURDATE(), INTERVAL 6 DAY), '10:00:00', '11:00:00', 'America/Los_Angeles', 'completed', 'paid', 'BOOK-HS-011', 6),
(2, 19, 5, 13, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '14:00:00', '14:45:00', 'America/Los_Angeles', 'completed', 'paid', 'BOOK-HS-012', 7),
-- No-show
(2, 11, 4, 14, DATE_SUB(CURDATE(), INTERVAL 3 DAY), '15:00:00', '15:30:00', 'America/Los_Angeles', 'no_show', 'unpaid', 'BOOK-HS-013', 7);

-- Insert Sample Email Logs
INSERT INTO `email_logs` (`tenant_id`, `to_email`, `subject`, `body`, `type`, `related_booking_id`) VALUES
(1, 'jennifer.anderson@example.com', 'Booking Confirmation - Women\'s Haircut', 'Dear Jennifer,\n\nYour appointment has been confirmed!\n\nService: Women\'s Haircut\nDate: ' || CURDATE() || '\nTime: 10:00 AM\nStaff: Sarah Johnson\n\nReference: BOOK-BB-001\n\nThank you for choosing Bella Beauty Salon!', 'booking_confirmation', 1),
(1, 'michael.brown@example.com', 'Booking Confirmation - Manicure', 'Dear Michael,\n\nYour appointment has been confirmed!\n\nService: Manicure\nDate: ' || CURDATE() || '\nTime: 11:00 AM\nStaff: Emily Davis\n\nReference: BOOK-BB-002\n\nThank you for choosing Bella Beauty Salon!', 'booking_confirmation', 2),
(2, 'robert.johnson@example.com', 'Booking Confirmation - General Checkup', 'Dear Robert,\n\nYour dental appointment has been confirmed!\n\nService: General Checkup\nDate: ' || CURDATE() || '\nTime: 8:00 AM\nProvider: Dr. Lisa Anderson\n\nReference: BOOK-HS-001\n\nThank you for choosing Healthy Smiles Dental!', 'booking_confirmation', 13),
(2, 'maria.rodriguez@example.com', 'Reminder: Teeth Cleaning Tomorrow', 'Dear Maria,\n\nThis is a reminder of your upcoming appointment:\n\nService: Teeth Cleaning\nDate: ' || DATE_ADD(CURDATE(), INTERVAL 1 DAY) || '\nTime: 8:00 AM\nProvider: Jessica Taylor\n\nReference: BOOK-HS-005\n\nSee you soon!\nHealthy Smiles Dental', 'reminder', 17);

-- Insert Business Settings
INSERT INTO `business_settings` (`tenant_id`, `setting_key`, `setting_value`) VALUES
(1, 'booking_advance_days', '60'),
(1, 'min_cancellation_hours', '24'),
(1, 'require_payment_online', '0'),
(1, 'business_hours_start', '09:00'),
(1, 'business_hours_end', '19:00'),
(2, 'booking_advance_days', '90'),
(2, 'min_cancellation_hours', '48'),
(2, 'require_payment_online', '0'),
(2, 'business_hours_start', '08:00'),
(2, 'business_hours_end', '18:00');
