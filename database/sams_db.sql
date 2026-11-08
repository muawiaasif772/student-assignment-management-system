-- Student Assignment Management System
-- Importing this file DROPS and recreates the sams_db database.
--
-- Demo accounts (passwords are stored only as bcrypt hashes):
--   Administrator  admin@sams.local             Admin@123
--   Teacher        teacher@sams.local           Teacher@123
--   Student        student@sams.local           Student@123
--   Teacher        teacher2@sams.local          Teacher@123
--   Student        student2@sams.local          Student@123
--   Student        student3@sams.local          Student@123
--   Inactive       student.inactive@sams.local  Student@123

SET NAMES utf8mb4;
SET time_zone = '+05:00';
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS sams_db;
CREATE DATABASE sams_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sams_db;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role),
    KEY idx_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE assignments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    teacher_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    deadline DATETIME NOT NULL,
    max_marks DECIMAL(6,2) NOT NULL,
    status ENUM('draft', 'published', 'closed') NOT NULL DEFAULT 'draft',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_assignments_teacher (teacher_id),
    KEY idx_assignments_status (status),
    KEY idx_assignments_deadline (deadline),
    CONSTRAINT fk_assignments_teacher FOREIGN KEY (teacher_id) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE submissions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    assignment_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    marks DECIMAL(6,2) DEFAULT NULL,
    feedback TEXT DEFAULT NULL,
    status ENUM('submitted', 'late', 'graded') NOT NULL DEFAULT 'submitted',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_submission_once (assignment_id, student_id),
    KEY idx_submissions_student (student_id),
    KEY idx_submissions_status (status),
    CONSTRAINT fk_submissions_assignment FOREIGN KEY (assignment_id) REFERENCES assignments (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_submissions_student FOREIGN KEY (student_id) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT DEFAULT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_settings_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(150) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_login_email_time (email, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (id, name, email, password, role, status) VALUES
(1, 'System Administrator', 'admin@sams.local', '$2b$10$n6EhQ8heX65nmanhQK.E4uEn.WzXWH8Hd6/S7araESzCJ57enJ.ne', 'admin', 'active'),
(2, 'Amina Shah', 'teacher@sams.local', '$2b$10$IocEwxrHr2PJDSOZCVev4uDqdpQ/AsR0lRuoa.oLCd99iNOPnTo9K', 'teacher', 'active'),
(3, 'Sana Iqbal', 'student@sams.local', '$2b$10$BWU6lC/s9xcoMNqeuRVL9eymq7rhDKzgHJ9ipa.9RB5zelu7XlzVq', 'student', 'active'),
(4, 'Bilal Mehmood', 'teacher2@sams.local', '$2b$10$IocEwxrHr2PJDSOZCVev4uDqdpQ/AsR0lRuoa.oLCd99iNOPnTo9K', 'teacher', 'active'),
(5, 'Omar Farooq', 'student2@sams.local', '$2b$10$BWU6lC/s9xcoMNqeuRVL9eymq7rhDKzgHJ9ipa.9RB5zelu7XlzVq', 'student', 'active'),
(6, 'Hira Nadeem', 'student3@sams.local', '$2b$10$BWU6lC/s9xcoMNqeuRVL9eymq7rhDKzgHJ9ipa.9RB5zelu7XlzVq', 'student', 'active'),
(7, 'Usman Tariq', 'student.inactive@sams.local', '$2b$10$BWU6lC/s9xcoMNqeuRVL9eymq7rhDKzgHJ9ipa.9RB5zelu7XlzVq', 'student', 'inactive');

INSERT INTO assignments (id, teacher_id, title, description, deadline, max_marks, status) VALUES
(1, 2, 'Database Normalization Exercises', 'Explain the anomalies in the supplied registration table and decompose it into Third Normal Form. Submit one PDF that includes the dependency diagram, the final schema, and a short justification for each table.', DATE_ADD(NOW(), INTERVAL 14 DAY), 20.00, 'published'),
(2, 2, 'Web Security Case Study', 'Choose one web application vulnerability from the OWASP Top 10. Describe how the attack works, the impact on users, and the defensive control a developer should implement. Do not include exploit code. Submit a PDF or DOCX.', DATE_ADD(NOW(), INTERVAL 7 DAY), 30.00, 'published'),
(3, 2, 'Software Requirements Specification', 'Prepare a software requirements specification for a library desk-reservation module. Include functional requirements, non-functional requirements, and two use cases.', DATE_ADD(NOW(), INTERVAL 21 DAY), 25.00, 'draft'),
(4, 2, 'Computer Networks Lab Report', 'Document the packet capture completed in the lab. Identify the protocols observed and explain the three-way handshake in your own words.', DATE_SUB(NOW(), INTERVAL 3 DAY), 15.00, 'published'),
(5, 2, 'Operating Systems Review', 'Summarize the difference between preemptive and non-preemptive scheduling and illustrate it with one short example.', DATE_SUB(NOW(), INTERVAL 12 DAY), 10.00, 'closed'),
(6, 4, 'Introduction to Artificial Intelligence', 'Write a short essay that distinguishes supervised learning from unsupervised learning and give one everyday software example of each.', DATE_ADD(NOW(), INTERVAL 10 DAY), 20.00, 'published');

INSERT INTO submissions (assignment_id, student_id, file_name, file_path, submitted_at, marks, feedback, status) VALUES
(2, 3, 'Web_Security_Case_Study.pdf', 'c0ffee01a1b2c3d4e5f60718293a4b5c.pdf', DATE_SUB(NOW(), INTERVAL 1 DAY), NULL, NULL, 'submitted'),
(2, 5, 'Security_Case_Study_Omar.pdf', 'c0ffee02a1b2c3d4e5f60718293a4b5c.pdf', DATE_SUB(NOW(), INTERVAL 2 DAY), 24.00, 'Clear structure and relevant examples. Expand the section on how the chosen control stops the attack.', 'graded'),
(4, 6, 'Networks_Lab_Report.pdf', 'c0ffee03a1b2c3d4e5f60718293a4b5c.pdf', DATE_SUB(NOW(), INTERVAL 5 DAY), NULL, NULL, 'submitted'),
(5, 3, 'OS_Review.pdf', 'c0ffee04a1b2c3d4e5f60718293a4b5c.pdf', DATE_SUB(NOW(), INTERVAL 13 DAY), 8.00, 'Good coverage of process scheduling. Add a short comparison of two scheduling algorithms next time.', 'graded');

INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'Student Assignment Management System'),
('institution_name', 'Department of Computer Science'),
('academic_year', 'Fall 2026'),
('contact_email', 'admin@sams.local'),
('student_registration_enabled', '1'),
('teacher_registration_enabled', '1'),
('allow_late_submissions', '0'),
('announcement', 'Welcome to SAMS. Check the deadline on each assignment before you upload your work.');
