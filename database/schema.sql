-- ============================================
-- Student Evaluation System - Full Schema
-- ============================================

CREATE DATABASE IF NOT EXISTS `grading_system_v2`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `grading_system_v2`;

-- Users
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `role` ENUM('admin','instructor') NOT NULL DEFAULT 'instructor',
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Settings
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(50) NOT NULL UNIQUE,
  `setting_value` TEXT DEFAULT NULL
) ENGINE=InnoDB;

-- Courses
CREATE TABLE IF NOT EXISTS `courses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `course_name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Semesters
CREATE TABLE IF NOT EXISTS `semesters` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Subjects
CREATE TABLE IF NOT EXISTS `subjects` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `semester_id` INT DEFAULT NULL,
  `course_id` INT NOT NULL,
  `instructor_id` INT DEFAULT NULL,
  `course_no` VARCHAR(30) NOT NULL,
  `descriptive_title` VARCHAR(150) NOT NULL,
  `with_lab` TINYINT(1) NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`instructor_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`semester_id`) REFERENCES `semesters`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Subject Schedules
CREATE TABLE IF NOT EXISTS `subject_schedules` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `subject_id` INT NOT NULL,
  `day` VARCHAR(30) DEFAULT NULL,
  `time_start` TIME DEFAULT NULL,
  `time_end` TIME DEFAULT NULL,
  `room` VARCHAR(50) DEFAULT NULL,
  `total_students` INT DEFAULT 0,
  `female_students` INT DEFAULT 0,
  `male_students` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Students
CREATE TABLE IF NOT EXISTS `students` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `subject_id` INT NOT NULL,
  `student_id` VARCHAR(30) NOT NULL,
  `first_name` VARCHAR(60) NOT NULL,
  `last_name` VARCHAR(60) NOT NULL,
  `gender` ENUM('Male','Female') DEFAULT 'Male',
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Criteria (category weights per subject)
CREATE TABLE IF NOT EXISTS `criteria` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `subject_id` INT NOT NULL,
  `category` VARCHAR(50) NOT NULL,
  `weight` DECIMAL(5,2) NOT NULL DEFAULT 0,
  `type` ENUM('Lecture','Lab') NOT NULL DEFAULT 'Lecture',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Activities (quizzes, exams, etc.)
CREATE TABLE IF NOT EXISTS `activities` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `subject_id` INT NOT NULL,
  `category` VARCHAR(50) NOT NULL,
  `title` VARCHAR(100) NOT NULL,
  `total_points` DECIMAL(7,2) NOT NULL DEFAULT 100,
  `type` ENUM('Lecture','Lab') NOT NULL DEFAULT 'Lecture',
  `activity_date` DATE DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Activity Scores
CREATE TABLE IF NOT EXISTS `activity_scores` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `activity_id` INT NOT NULL,
  `student_id` INT NOT NULL,
  `score` DECIMAL(7,2) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`activity_id`) REFERENCES `activities`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_score` (`activity_id`, `student_id`)
) ENGINE=InnoDB;

-- Attendance Sessions
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `subject_id` INT NOT NULL,
  `session_date` DATE NOT NULL,
  `title` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Attendance Records
CREATE TABLE IF NOT EXISTS `attendance_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `attendance_id` INT NOT NULL,
  `student_id` INT NOT NULL,
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '1=Present, 0=Absent, 2=Late',
  FOREIGN KEY (`attendance_id`) REFERENCES `attendance`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_record` (`attendance_id`, `student_id`)
) ENGINE=InnoDB;

-- ============================================
-- Seed Data
-- ============================================
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('system_title', 'Student Evaluation System'),
('system_logo', '');

INSERT INTO `semesters` (`name`, `status`) VALUES
('First Semester 2024-2025', 1);

INSERT INTO `users` (`username`, `password`, `full_name`, `role`) VALUES
('admin', '$2y$10$9CAbFJyDRZFDaxbrCn/CWOVF/Qt.hV/Otd0Vacf0fV5Fd8xPlFlCS', 'System Administrator', 'admin'),
('instructor', '$2y$10$9CAbFJyDRZFDaxbrCn/CWOVF/Qt.hV/Otd0Vacf0fV5Fd8xPlFlCS', 'Juan Dela Cruz', 'instructor'),
('instructor2', '$2y$10$9CAbFJyDRZFDaxbrCn/CWOVF/Qt.hV/Otd0Vacf0fV5Fd8xPlFlCS', 'Maria Santos', 'instructor');

INSERT INTO `courses` (`course_name`, `description`) VALUES
('BSIT', 'Bachelor of Science in Information Technology'),
('BSCS', 'Bachelor of Science in Computer Science'),
('BEED', 'Bachelor of Elementary Education');
