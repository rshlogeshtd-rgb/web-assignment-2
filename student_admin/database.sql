-- ============================================================
-- College Student Admin Panel - database.sql
-- Import this file in phpMyAdmin (Import tab) or run it in SQL tab
-- ============================================================

CREATE DATABASE IF NOT EXISTS college_admin
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE college_admin;

-- Drop in the correct order (child table first)
DROP TABLE IF EXISTS student_files;
DROP TABLE IF EXISTS students;

-- ------------------------------------------------------------
-- Table 1: students
-- ------------------------------------------------------------
CREATE TABLE students (
  student_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_name VARCHAR(100) NOT NULL,
  roll_number  VARCHAR(30)  NOT NULL,
  course       VARCHAR(100) NOT NULL,
  department   VARCHAR(100) NOT NULL,
  semester     TINYINT UNSIGNED NOT NULL,
  email        VARCHAR(150) NOT NULL,
  phone        VARCHAR(20)  NOT NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (student_id),
  UNIQUE KEY uq_roll_number (roll_number)      -- no duplicate roll numbers
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Table 2: student_files (metadata of uploaded files)
-- ------------------------------------------------------------
CREATE TABLE student_files (
  file_id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id    INT UNSIGNED NOT NULL,
  file_category ENUM('profile_photo','id_proof','certificate','other') NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_name   VARCHAR(255) NOT NULL,
  file_path     VARCHAR(255) NOT NULL,
  file_type     VARCHAR(100) NOT NULL,
  file_size     INT UNSIGNED NOT NULL,
  upload_date   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (file_id),
  UNIQUE KEY uq_stored_name (stored_name),
  KEY idx_student (student_id),
  CONSTRAINT fk_files_student
    FOREIGN KEY (student_id) REFERENCES students (student_id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Sample students (files can be uploaded from the panel)
-- ------------------------------------------------------------
INSERT INTO students (student_name, roll_number, course, department, semester, email, phone) VALUES
('Aarav Sharma',  'CS2024001', 'B.Tech',  'Computer Science', 3, 'aarav.sharma@example.com', '9876543210'),
('Priya Nair',    'EC2024014', 'B.Tech',  'Electronics',      5, 'priya.nair@example.com',   '9123456780'),
('Rahul Verma',   'ME2023022', 'B.E',     'Mechanical',       7, 'rahul.verma@example.com',  '9988776655'),
('Sneha Reddy',   'BC2024031', 'B.Com',   'Commerce',         2, 'sneha.reddy@example.com',  '9090909090'),
('Karthik Raj',   'BS2023045', 'B.Sc',    'Physics',          4, 'karthik.raj@example.com',  '9812345678');
