-- =====================================================
--  StudentPortal database
--  Run this file ONCE (it recreates the database).
-- =====================================================
DROP DATABASE IF EXISTS student_portal;
CREATE DATABASE student_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE student_portal;

-- 1. users (passwords are stored as SHA-256 hashes)
CREATE TABLE users (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50)  NOT NULL UNIQUE,
    password VARCHAR(64)  NOT NULL
);

-- 2. students
CREATE TABLE students (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    roll_number   VARCHAR(30)  NOT NULL UNIQUE,
    course        VARCHAR(100) NOT NULL,
    department    VARCHAR(100) NOT NULL,
    semester      INT          NOT NULL,
    email         VARCHAR(120),
    phone         VARCHAR(20),
    profile_image VARCHAR(255) NULL        -- path relative to the uploads folder, e.g. profiles/priya_sharma.jpg
);

-- 3. student_files
CREATE TABLE student_files (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    student_id         INT          NOT NULL,
    original_file_name VARCHAR(255) NOT NULL,
    file_path          VARCHAR(500) NOT NULL,   -- path relative to the uploads folder, e.g. documents/priya_marksheet.pdf
    file_type          VARCHAR(100) NOT NULL,   -- MIME type, e.g. application/pdf
    upload_date        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_student_files_student
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Sample login users
INSERT INTO users (username, password) VALUES
('admin',   SHA2('admin123', 256)),
('teacher', SHA2('teacher123', 256));

-- Sample students
INSERT INTO students (id, name, roll_number, course, department, semester, email, phone, profile_image) VALUES
(1, 'Priya Sharma',  '25CSE001',  'B.E. Computer Science and Engineering',           'Computer Science and Engineering',           5, 'priya.sharma@example.com',  '9876543210', 'profiles/priya_sharma.jpg'),
(2, 'Arjun Kumar',   '25ECE014',  'B.E. Electronics and Communication',              'Electronics and Communication',              3, 'arjun.kumar@example.com',   '9876501234', 'profiles/arjun_kumar.png'),
(3, 'Meena Lakshmi', '25IT007',   'B.Tech Information Technology',                   'Information Technology',                     5, 'meena.lakshmi@example.com', '9845012345', NULL),
(4, 'Rahul Verma',   '25ME021',   'B.E. Mechanical Engineering',                     'Mechanical Engineering',                     7, 'rahul.verma@example.com',   '9123456780', 'profiles/rahul_verma.jpg'),
(5, 'Sneha Reddy',   '25AIDS003', 'B.Tech Artificial Intelligence and Data Science', 'Artificial Intelligence and Data Science',   1, 'sneha.reddy@example.com',   '9988776655', 'profiles/sneha_reddy.png');

-- Sample documents
--   Priya: 2 files | Arjun: 1 | Meena: 2 (and no profile image) | Rahul: none | Sneha: 1
INSERT INTO student_files (student_id, original_file_name, file_path, file_type) VALUES
(1, 'Semester 4 Marksheet.pdf',      'documents/priya_marksheet.pdf',        'application/pdf'),
(1, 'College ID Card.png',           'documents/priya_idcard.png',           'image/png'),
(2, 'Bonafide Certificate.pdf',      'documents/arjun_bonafide.pdf',         'application/pdf'),
(3, 'Resume.pdf',                    'documents/meena_resume.pdf',           'application/pdf'),
(3, 'Python Course Certificate.pdf', 'documents/meena_certificate.pdf',      'application/pdf'),
(5, 'Admission Letter.pdf',          'documents/sneha_admission_letter.pdf', 'application/pdf');
