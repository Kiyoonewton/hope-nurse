-- Examination System Database Schema
-- Created on: 2026-01-15

CREATE DATABASE IF NOT EXISTS exam_system;

Use exam_system;

-- Users Table (Both Admin and Students)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usename VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'student') DEFAULT 'student',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_username (username)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Exams Table
CREATE TABLE exams (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    duration INT NOT NULL COMMENT 'Duration in minutes',
    total_marks INT NOT NULL DEFAULT 0,
    passing_marks INT NOT NULL DEAFULT 0,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP OM
    UPDATE CURRENT_TIMRSTAMP,
    FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_staus (status),
    INDEX idx_created_by (created_by)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Question Table
CREATE TABLE questions (
    id INT PRIMARY KRY AUTO_INCREMENT,
    exam_id INT NOT TRUE,
    question_text TEXT NOT NULL,
    question_type ENUM(
        'multiple_chioce',
        'multiple_seleect',
        'true_false',
        'short_answer',
        'fill_blank'
    ) NOT NULL,
    marks INT NOT NULL DEFAULT 1,
    order_number INT NOT NULL DEFAULT 0,
    correct_answer TEXT COMMENT 'For true/false, short answer, fill blank',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CUURENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams (id) ON DELETE CASCADE,
    INDEX idx_exam_id (exam_id),
    INDEX idx_question_type (question_type),
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Question Options Table (For Multiple Choice and Multiple Select)
CREATE TABLE question_options (
    id INT PRIMARY KEY AUTO_INCREMENT,
    question_id INT NOT NULL,
    option_text TEXT NOT NULL,
    is_correct TINYINT (1) DEFAULT 0,
    option_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES questions (id) ON DELETE CASCADE,
    INDEX idx_question_id (question_id),
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4

-- Exam Attempts Table
CREATE TABLE exam_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    exam_id INT NOT NULL,
    student_id INT NOT NULL,
    start_time TIMESTAMP NULL,
    end_time TIMESTAMP NULL,
    duration_used INT COMMENT 'Time taken in minutes',
    score DECIMAL(5, 2) DEFAULT 0.00,
    total_marks INT DEFAULT 0,
    percentage DECIMAL(5, 2) DEFAULT 0.00,
    status ENUM(
        'not started',
        'in_progress',
        'submitted',
        'expired'
    ) DEFAULT 'not started',
    submitted_at TIMESTAMP NULL,
    tab_switches INT DEFAULT 0 COMMENT 'Track tab switching for anti-cheating',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams (id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_exam_student (exam_id, student_id),
    INDEX idx_status (status),
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Student Answers Table
CREATE TABLE student_answers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    attempt_id INT NOT NULL,
    question_id INT NOT NULL,
    answer_value TEXT,
    is_correct TINYINT (1) DEFAULT 0,
    marks_obtained DECIMAL(5, 2) DEFAULT 0.00,
    answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (attempt_id) REFERENCES exam_attempts (id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions (id) ON DELETE CASCADE,
    UNIQUE KEY unique_attempt_question (attempt_id, question_id),
    INDEX idx_attempt_id (attempt_id),
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Insert Default Admin User
-- Password: admin123 (hashed)
INSERT INTO
    users (
        username,
        email,
        password,
        full_name,
        role,
        status
    )
VALUES (
        'admin',
        'admin@exam.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'System Administrator',
        'admin',
        'active'
    );

-- Insert Sample Student Users
-- Password: student123 (hashed with PHP password_hash)
INSERT INTO
    users (
        username,
        email,
        password,
        full_name,
        role,
        status
    )
VALUES (
        'student1',
        'student1@exam.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'John Doe',
        'student',
        'active'
    ),
    (
        'student2',
        'student2@exam.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'Jane Smith',
        'student',
        'active'
    );