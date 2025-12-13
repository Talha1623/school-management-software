-- Add student_id column to leaves table
-- Run this SQL query directly in your MySQL database

-- Step 1: Make staff_id nullable (important for student leaves)
ALTER TABLE `leaves` 
MODIFY COLUMN `staff_id` BIGINT UNSIGNED NULL;

-- Step 2: Add student_id column
ALTER TABLE `leaves` 
ADD COLUMN `student_id` BIGINT UNSIGNED NULL AFTER `staff_id`,
ADD INDEX `leaves_student_id_index` (`student_id`);

-- Step 3: Add foreign key constraint for student_id
ALTER TABLE `leaves`
ADD CONSTRAINT `leaves_student_id_foreign` 
FOREIGN KEY (`student_id`) 
REFERENCES `students` (`id`) 
ON DELETE CASCADE;

