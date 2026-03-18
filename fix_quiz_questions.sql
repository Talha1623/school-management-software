-- Fix quiz_questions table tablespace issue
-- Run this in MySQL command line or phpMyAdmin

USE school;

-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS=0;

-- Try to drop the table (this will fail if tablespace exists, but that's okay)
DROP TABLE IF EXISTS quiz_questions;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS=1;

-- Now create the table
CREATE TABLE quiz_questions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    quiz_id BIGINT UNSIGNED NOT NULL,
    question_number INT NOT NULL,
    question TEXT NULL,
    answer1 VARCHAR(191) NULL,
    marks1 INT NOT NULL DEFAULT 0,
    answer2 VARCHAR(191) NULL,
    marks2 INT NOT NULL DEFAULT 0,
    answer3 VARCHAR(191) NULL,
    marks3 INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_quiz_id (quiz_id),
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
