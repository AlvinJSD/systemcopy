-- ============================================================
-- PapeLESS OJT Document Tracking System
-- Complete MySQL Database Schema + Sample Data
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `papeless_db` 
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `papeless_db`;

-- ============================================================
-- TABLE: settings
-- ============================================================
CREATE TABLE `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('academic_year', '2024-2025'),
('semester', '2nd Semester'),
('submission_deadline', '2025-05-31'),
('max_file_size_mb', '10'),
('survey_enabled', '1'),
('system_name', 'PapeLESS'),
('institution_name', 'Polytechnic University of the Philippines'),
('campus', 'Sto. Tomas Campus');

-- ============================================================
-- TABLE: coordinators
-- ============================================================
CREATE TABLE `coordinators` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` VARCHAR(50) NOT NULL UNIQUE,
  `first_name` VARCHAR(100) NOT NULL,
  `middle_name` VARCHAR(100) DEFAULT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `profile_photo` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default coordinator: password = Admin@123
INSERT INTO `coordinators` (`employee_id`, `first_name`, `middle_name`, `last_name`, `email`, `password`) VALUES
('COORD-001', 'Maria', 'Santos', 'Reyes', 'coordinator@papeless.edu.ph', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- ============================================================
-- TABLE: advisers
-- ============================================================
CREATE TABLE `advisers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` VARCHAR(50) NOT NULL UNIQUE,
  `first_name` VARCHAR(100) NOT NULL,
  `middle_name` VARCHAR(100) DEFAULT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `profile_photo` VARCHAR(255) DEFAULT NULL,
  `contact_number` VARCHAR(20) DEFAULT NULL,
  `program` VARCHAR(100) NOT NULL,
  `year_level` TINYINT NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `coordinators`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample advisers: password = Adviser@123
INSERT INTO `advisers` (`employee_id`, `first_name`, `middle_name`, `last_name`, `email`, `password`, `program`, `year_level`, `created_by`) VALUES
('ADV-001', 'Juan', 'Cruz', 'Dela Cruz', 'adviser.bsit3@papeless.edu.ph', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'BSIT', 3, 1),
('ADV-002', 'Ana', 'Garcia', 'Santos', 'adviser.bsit4@papeless.edu.ph', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'BSIT', 4, 1),
('ADV-003', 'Pedro', 'Lim', 'Bautista', 'adviser.bscs3@papeless.edu.ph', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'BSCS', 3, 1);

-- ============================================================
-- TABLE: students
-- ============================================================
CREATE TABLE `students` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_number` VARCHAR(50) NOT NULL UNIQUE,
  `first_name` VARCHAR(100) NOT NULL,
  `middle_name` VARCHAR(100) DEFAULT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `birthdate` DATE NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `profile_photo` VARCHAR(255) DEFAULT NULL,
  `contact_number` VARCHAR(20) DEFAULT NULL,
  `program` VARCHAR(100) NOT NULL,
  `year_level` TINYINT NOT NULL,
  `section` TINYINT NOT NULL,
  `company_name` VARCHAR(200) NOT NULL,
  `company_address` TEXT NOT NULL,
  `department_role` VARCHAR(200) NOT NULL,
  `internship_start` DATE NOT NULL,
  `internship_end` DATE DEFAULT NULL,
  `time_in` TIME NOT NULL,
  `time_out` TIME NOT NULL,
  `required_hours` INT DEFAULT 486,
  `rendered_hours` DECIMAL(8,2) DEFAULT 0,
  `adviser_id` INT DEFAULT NULL,
  `status` ENUM('pending','approved','rejected','completed') DEFAULT 'pending',
  `rejection_reason` TEXT DEFAULT NULL,
  `approved_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`adviser_id`) REFERENCES `advisers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample students: password = Student@123
INSERT INTO `students` (`student_number`, `first_name`, `middle_name`, `last_name`, `birthdate`, `email`, `password`, `program`, `year_level`, `section`, `company_name`, `company_address`, `department_role`, `internship_start`, `time_in`, `time_out`, `adviser_id`, `status`, `approved_at`) VALUES
('2021-00001', 'Jose', 'Rizal', 'Mercado', '2001-06-19', 'jose.mercado@student.edu.ph', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'BSIT', 3, 1, 'TechCorp Philippines', 'Makati City, Metro Manila', 'Web Developer Intern', '2025-01-15', '08:00:00', '17:00:00', 1, 'approved', NOW()),
('2021-00002', 'Andres', 'Bonifacio', 'De Leon', '2001-11-30', 'andres.deleon@student.edu.ph', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'BSIT', 3, 1, 'Globe Telecom', 'Taguig City, Metro Manila', 'Network Engineer Intern', '2025-01-15', '08:00:00', '17:00:00', 1, 'approved', NOW()),
('2021-00003', 'Emilio', NULL, 'Aguinaldo', '2000-03-22', 'emilio.aguinaldo@student.edu.ph', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'BSIT', 3, 2, 'PLDT Inc', 'Makati City, Metro Manila', 'IT Support Intern', '2025-01-15', '09:00:00', '18:00:00', 1, 'pending', NULL);

-- ============================================================
-- TABLE: submission_requirements
-- ============================================================
CREATE TABLE `submission_requirements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `requirement_name` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `allowed_types` VARCHAR(200) DEFAULT 'pdf,docx,doc,jpg,jpeg,png,zip',
  `is_required` TINYINT(1) DEFAULT 1,
  `week_number` INT DEFAULT NULL,
  `sort_order` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `submission_requirements` (`requirement_name`, `description`, `week_number`, `sort_order`) VALUES
('Resume / CV', 'Your updated resume or curriculum vitae', NULL, 1),
('Memorandum of Agreement (MOA)', 'Signed MOA between the school and company', NULL, 2),
('Endorsement Letter', 'Official endorsement letter from school', NULL, 3),
('Medical Certificate', 'Medical certificate for internship clearance', NULL, 4),
('Week 1 Daily Time Record', 'DTR for the first week of internship', 1, 5),
('Week 1 Weekly Report', 'Activity report for Week 1', 1, 6),
('Week 2 Daily Time Record', 'DTR for the second week of internship', 2, 7),
('Week 2 Weekly Report', 'Activity report for Week 2', 2, 8),
('Week 3 Daily Time Record', 'DTR for the third week of internship', 3, 9),
('Week 3 Weekly Report', 'Activity report for Week 3', 3, 10),
('Week 4 Daily Time Record', 'DTR for the fourth week of internship', 4, 11),
('Week 4 Weekly Report', 'Activity report for Week 4', 4, 12),
('Narrative Report', 'Complete narrative/final report of internship', NULL, 13),
('Certificate of Completion', 'Certificate from the company confirming completion', NULL, 14),
('Performance Evaluation Form', 'Evaluation form filled by company supervisor', NULL, 15);

-- ============================================================
-- TABLE: submissions
-- ============================================================
CREATE TABLE `submissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `requirement_id` INT NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `file_type` VARCHAR(50) NOT NULL,
  `file_size` BIGINT NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `status` ENUM('pending','approved','needs_revision','resubmitted') DEFAULT 'pending',
  `adviser_comment` TEXT DEFAULT NULL,
  `reviewed_by` INT DEFAULT NULL,
  `reviewed_at` TIMESTAMP NULL DEFAULT NULL,
  `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`requirement_id`) REFERENCES `submission_requirements`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`reviewed_by`) REFERENCES `advisers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: announcements
-- ============================================================
CREATE TABLE `announcements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `attachment` VARCHAR(255) DEFAULT NULL,
  `attachment_name` VARCHAR(255) DEFAULT NULL,
  `target_audience` ENUM('all','students','advisers') DEFAULT 'all',
  `target_program` VARCHAR(100) DEFAULT NULL,
  `target_year` TINYINT DEFAULT NULL,
  `is_pinned` TINYINT(1) DEFAULT 0,
  `posted_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`posted_by`) REFERENCES `coordinators`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `announcements` (`title`, `content`, `target_audience`, `is_pinned`, `posted_by`) VALUES
('Welcome to PapeLESS!', 'Welcome to the PapeLESS OJT Document Tracking System. Please complete your profile and submit all required documents before the deadline. If you have any questions, contact your assigned adviser.', 'all', 1, 1),
('Submission Deadline Reminder', 'All OJT documents must be submitted by May 31, 2025. Please ensure all required files are uploaded and approved before the deadline. Late submissions will not be accepted.', 'students', 1, 1),
('Adviser Meeting Schedule', 'All advisers are required to attend the OJT monitoring meeting on February 28, 2025 at 2:00 PM via Zoom. Meeting link will be sent via email.', 'advisers', 0, 1);

-- ============================================================
-- TABLE: messages
-- ============================================================
CREATE TABLE `messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sender_type` ENUM('student','adviser') NOT NULL,
  `sender_id` INT NOT NULL,
  `receiver_type` ENUM('student','adviser') NOT NULL,
  `receiver_id` INT NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `parent_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`parent_id`) REFERENCES `messages`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: surveys
-- ============================================================
CREATE TABLE `surveys` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `survey_date` DATE NOT NULL,
  `q1_mood` TINYINT NOT NULL COMMENT '1-5 scale: How are you today?',
  `q2_stress` TINYINT NOT NULL COMMENT '1-5 scale: Stress level',
  `q3_needs_consultation` TINYINT(1) DEFAULT 0 COMMENT 'Needs consultation?',
  `q4_company_problem` TINYINT(1) DEFAULT 0 COMMENT 'Company problems?',
  `q5_experience_rating` TINYINT NOT NULL COMMENT '1-5 scale: Internship experience today',
  `additional_notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_survey_per_day` (`student_id`, `survey_date`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: notifications
-- ============================================================
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_type` ENUM('student','adviser','coordinator') NOT NULL,
  `user_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('submission','approval','revision','message','announcement','survey','system') DEFAULT 'system',
  `reference_id` INT DEFAULT NULL,
  `reference_type` VARCHAR(50) DEFAULT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: activity_logs
-- ============================================================
CREATE TABLE `activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_type` ENUM('student','adviser','coordinator') NOT NULL,
  `user_id` INT NOT NULL,
  `action` VARCHAR(255) NOT NULL,
  `details` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: guidelines
-- ============================================================
CREATE TABLE `guidelines` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `drive_link` VARCHAR(500) DEFAULT NULL,
  `file_path` VARCHAR(500) DEFAULT NULL,
  `category` ENUM('procedure','template','policy','other') DEFAULT 'other',
  `sort_order` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `guidelines` (`title`, `description`, `drive_link`, `category`, `sort_order`) VALUES
('OJT Student Handbook', 'Complete guide for OJT students covering rules, regulations, and procedures.', '#', 'policy', 1),
('MOA Template', 'Memorandum of Agreement template to be signed by school and company.', '#', 'template', 2),
('Weekly Report Template', 'Template for writing your weekly internship activity report.', '#', 'template', 3),
('Narrative Report Guide', 'Guidelines and template for writing the final narrative report.', '#', 'template', 4),
('DTR Template', 'Daily Time Record template for tracking daily attendance.', '#', 'template', 5),
('OJT Proper Procedures', 'Step-by-step procedures for OJT from endorsement to completion.', '#', 'procedure', 6),
('Performance Evaluation Form', 'Form to be filled out by company supervisor for student evaluation.', '#', 'template', 7);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- END OF SCHEMA
-- ============================================================
