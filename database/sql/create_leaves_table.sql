-- Create leaves table for Leave Management
-- Database: school (or your database name)

USE school;

-- Drop table if exists (optional - use only if you want to recreate)
-- DROP TABLE IF EXISTS `leaves`;

-- Create leaves table
CREATE TABLE IF NOT EXISTS `leaves` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id` BIGINT UNSIGNED NOT NULL,
  `leave_reason` VARCHAR(255) NOT NULL,
  `from_date` DATE NOT NULL,
  `to_date` DATE NOT NULL,
  `status` VARCHAR(255) NOT NULL DEFAULT 'Pending',
  `remarks` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `leaves_staff_id_from_date_index` (`staff_id`, `from_date`),
  INDEX `leaves_from_date_to_date_index` (`from_date`, `to_date`),
  INDEX `leaves_status_index` (`status`),
  CONSTRAINT `leaves_staff_id_foreign` 
    FOREIGN KEY (`staff_id`) 
    REFERENCES `staff` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verify table creation
-- SELECT * FROM `leaves` LIMIT 1;

