-- ========================================================
-- SKIT021: Student Participation & Achievement Management System
-- College: Swami Keshvanand Institute of Technology (SKIT Jaipur)
-- Database: campuscred
-- ========================================================

CREATE DATABASE IF NOT EXISTS campuscred CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE campuscred;

-- 1. Table structure for table `users`
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id_code VARCHAR(50) UNIQUE NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin', 'recruiter') NOT NULL DEFAULT 'student',
    department VARCHAR(100) NOT NULL,
    branch VARCHAR(100) NULL,
    academic_year VARCHAR(30) NULL,
    admission_year INT NULL,
    phone VARCHAR(20) NULL,
    company_name VARCHAR(150) NULL,
    company_website VARCHAR(200) NULL,
    designation VARCHAR(100) NULL,
    industry VARCHAR(100) NULL,
    headline VARCHAR(200) NULL,
    skills VARCHAR(255) NULL,
    bio TEXT NULL,
    linkedin_url VARCHAR(200) NULL,
    github_url VARCHAR(200) NULL,
    cgpa DECIMAL(3,2) NULL,
    is_open_to_work TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_role (role),
    INDEX idx_user_student_id (student_id_code),
    INDEX idx_user_dept (department)
) ENGINE=InnoDB;

-- 2. Table structure for table `activities`
CREATE TABLE IF NOT EXISTS activities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    submission_code VARCHAR(50) UNIQUE NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    event_name VARCHAR(200) NOT NULL,
    category ENUM('Academic', 'Technical', 'Cultural', 'Sports', 'Extracurricular', 'Other') NOT NULL DEFAULT 'Other',
    event_date DATE NOT NULL,
    organizer VARCHAR(200) NOT NULL,
    department VARCHAR(100) NOT NULL,
    participation_type ENUM('Winner', 'Runner-up', '3rd Place', 'Finalist', 'Participant', 'Volunteer', 'Organizer', 'Presenter') NOT NULL DEFAULT 'Participant',
    achievement_description TEXT NULL,
    certificate_path VARCHAR(255) NOT NULL,
    certificate_original_name VARCHAR(255) NOT NULL,
    certificate_hash VARCHAR(64) NULL,
    status ENUM('Pending Verification', 'Verified', 'Rejected', 'Resubmitted / Pending Review') NOT NULL DEFAULT 'Pending Verification',
    verification_uuid VARCHAR(64) UNIQUE NOT NULL,
    reviewer_id INT UNSIGNED NULL,
    rejection_reason TEXT NULL,
    review_notes TEXT NULL,
    reviewed_at DATETIME NULL,
    resubmission_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_activity_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_activity_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_activity_status (status),
    INDEX idx_activity_student (student_id),
    INDEX idx_activity_category (category),
    INDEX idx_activity_dept (department),
    INDEX idx_activity_date (event_date),
    INDEX idx_activity_uuid (verification_uuid)
) ENGINE=InnoDB;

-- 3. Table structure for table `verification_logs`
CREATE TABLE IF NOT EXISTS verification_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    activity_id INT UNSIGNED NOT NULL,
    reviewer_id INT UNSIGNED NULL,
    action ENUM('Submitted', 'Verified', 'Rejected', 'Resubmitted', 'Updated') NOT NULL,
    previous_status VARCHAR(50) NULL,
    new_status VARCHAR(50) NOT NULL,
    reason TEXT NULL,
    logged_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_activity FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE,
    CONSTRAINT fk_log_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_log_activity (activity_id),
    INDEX idx_log_action (action)
) ENGINE=InnoDB;

-- 4. Table structure for table `recruiter_bookmarks`
CREATE TABLE IF NOT EXISTS recruiter_bookmarks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    recruiter_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    notes TEXT NULL,
    status ENUM('Saved', 'Screening', 'Interviewing', 'Offered', 'Archived') NOT NULL DEFAULT 'Saved',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bm_recruiter FOREIGN KEY (recruiter_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_bm_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uk_recruiter_student (recruiter_id, student_id),
    INDEX idx_bm_recruiter (recruiter_id),
    INDEX idx_bm_student (student_id)
) ENGINE=InnoDB;

-- 5. Table structure for table `recruitment_inquiries`
CREATE TABLE IF NOT EXISTS recruitment_inquiries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    recruiter_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    job_title VARCHAR(150) NOT NULL,
    job_type ENUM('Full-time', 'Internship', 'Contract', 'Part-time') NOT NULL DEFAULT 'Full-time',
    location VARCHAR(100) NULL,
    salary_range VARCHAR(100) NULL,
    message TEXT NOT NULL,
    contact_email VARCHAR(190) NOT NULL,
    status ENUM('Sent', 'Viewed', 'Accepted', 'Declined') NOT NULL DEFAULT 'Sent',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_inq_recruiter FOREIGN KEY (recruiter_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_inq_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_inq_recruiter (recruiter_id),
    INDEX idx_inq_student (student_id)
) ENGINE=InnoDB;

-- Demo Users
INSERT INTO users (id, student_id_code, name, email, password_hash, role, department, branch, academic_year, admission_year, phone, headline, skills, cgpa, is_open_to_work, company_name, designation, industry)
VALUES
(1, 'SKIT/ADM/001', 'Dr. Rajesh Sharma', 'admin@skit.ac.in', '$2y$10$h9W8vR1v8T3Z1q4W8s5Sre2iJ4r8n7gV8n9dE3fG5h7i9jK1lM2nO', 'admin', 'Computer Science & Engineering', 'CSE', 'Faculty / Admin', 2018, '9829012345', 'Head of Department & Verification Officer', NULL, NULL, 0, NULL, NULL, NULL),
(2, 'SKIT/2023/CSE/014', 'Aravind Swaminathan', 'aravind@skit.ac.in', '$2y$10$h9W8vR1v8T3Z1q4W8s5Sre2iJ4r8n7gV8n9dE3fG5h7i9jK1lM2nO', 'student', 'Computer Science & Engineering', 'CSE', '3rd Year', 2023, '9829112233', 'National Hackathon Winner | Python, Drone Robotics & Full-Stack Developer', 'Python, React, Django, Drone Tech, IoT, Docker, Git', 8.85, 1, NULL, NULL, NULL),
(3, 'SKIT/2022/IT/088', 'Priya Sharma', 'priya.sharma@skit.ac.in', '$2y$10$h9W8vR1v8T3Z1q4W8s5Sre2iJ4r8n7gV8n9dE3fG5h7i9jK1lM2nO', 'student', 'Information Technology', 'IT', '4th Year', 2022, '9829223344', 'ACM-ICPC Regional Finalist | Algorithms & Cloud Architecture Specialist', 'Java, C++, Algorithms, AWS Cloud, Spring Boot, MySQL', 9.10, 1, NULL, NULL, NULL),
(4, 'SKIT/2024/ECE/105', 'Rohit Verma', 'rohit.verma@skit.ac.in', '$2y$10$h9W8vR1v8T3Z1q4W8s5Sre2iJ4r8n7gV8n9dE3fG5h7i9jK1lM2nO', 'student', 'Electronics & Communication', 'ECE', '2nd Year', 2024, '9829334455', 'Autonomous Robotics & Embedded Systems Engineer | ESP32 Enthusiast', 'C, C++, Embedded Systems, ESP32, ROS, Robotics, Hardware Design', 8.40, 1, NULL, NULL, NULL),
(5, NULL, 'Pooja Malhotra', 'recruiter@techcorp.com', '$2y$10$h9W8vR1v8T3Z1q4W8s5Sre2iJ4r8n7gV8n9dE3fG5h7i9jK1lM2nO', 'recruiter', 'Human Resources', NULL, NULL, NULL, '9876543210', NULL, NULL, NULL, 0, 'TechCorp Innovations & Labs', 'Senior University Talent Acquisition Lead', 'Enterprise Software, Cloud & AI Solutions')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Demo Recruiter Bookmarks & Inquiries
INSERT INTO recruiter_bookmarks (recruiter_id, student_id, notes, status)
VALUES
(5, 2, 'Impressive 1st place in Smart India Hackathon. Great fit for our Autonomous Systems & Cloud engineering track.', 'Interviewing'),
(5, 3, 'Outstanding ACM-ICPC standing and high CGPA. Excellent fit for Backend & Algorithms role.', 'Screening')
ON DUPLICATE KEY UPDATE notes=VALUES(notes);

INSERT INTO recruitment_inquiries (id, recruiter_id, student_id, job_title, job_type, location, salary_range, message, contact_email, status)
VALUES
(1, 5, 2, 'Graduate Software Engineer - Cloud & Autonomous Systems', 'Full-time', 'Bangalore / Hybrid', '₹12 - ₹16 LPA', 'Hello Aravind,\n\nWe were highly impressed by your verified achievements at Smart India Hackathon and your background in Drone Tech and Python. We would love to invite you for an introductory technical discussion with our engineering director.\n\nBest regards,\nPooja Malhotra\nTalent Acquisition, TechCorp Labs', 'recruiter@techcorp.com', 'Sent')
ON DUPLICATE KEY UPDATE job_title=VALUES(job_title);

-- Demo Activities
INSERT INTO activities (
    id, submission_code, student_id, event_name, category, event_date, organizer, department, participation_type,
    achievement_description, certificate_path, certificate_original_name, status, verification_uuid, reviewer_id,
    rejection_reason, review_notes, reviewed_at
) VALUES
(
    1, 'SUB-2026-CSE001', 2, 'Smart India Hackathon 2025 (Hardware & Software)', 'Technical', '2025-12-14',
    'Ministry of Education & AICTE', 'Computer Science & Engineering', 'Winner',
    'Secured 1st Place across 35 collegiate teams for developing an automated agricultural drone surveillance system.',
    'sample_cert_sih.pdf', 'SIH_2025_First_Prize_Certificate.pdf', 'Verified',
    'ver-c9b8f2a1-4e7d-4293-8b1e-72fa9108c1a1', 1,
    NULL, 'Verified genuine against AICTE national registry credentials.', '2026-01-10 14:30:00'
),
(
    2, 'SUB-2026-CSE002', 2, 'Pravah 2026 Inter-College Cultural Festival', 'Cultural', '2026-02-18',
    'SKIT Jaipur Cultural Council', 'Computer Science & Engineering', 'Winner',
    'First prize in Solo Classical Instrumental (Flute) competition among 18 universities.',
    'sample_cert_pravah.jpg', 'Pravah_Instrumental_1st_Place.jpg', 'Verified',
    'ver-a1e4d7c8-9b2f-4130-98ef-83bd0219d2b2', 1,
    NULL, 'Verified by SKIT Cultural Committee record book.', '2026-02-22 11:15:00'
),
(
    3, 'SUB-2026-CSE003', 2, 'National Cyber Security Research Workshop', 'Academic', '2026-03-05',
    'IIT Roorkee & IEEE Computer Society', 'Computer Science & Engineering', 'Presenter',
    'Delivered paper presentation on Zero-Trust Architecture for Distributed Campus Networks.',
    'sample_cert_paper.pdf', 'IEEE_CyberSecurity_Paper_Cert.pdf', 'Pending Verification',
    'ver-3f82a9c1-74d8-4be0-86d1-419bcf8231c3', NULL,
    NULL, NULL, NULL
),
(
    4, 'SUB-2026-IT001', 3, 'Rajasthan State Inter-University Basketball Tournament', 'Sports', '2026-01-28',
    'Rajasthan Technical University (RTU)', 'Information Technology', 'Runner-up',
    'Represented SKIT Jaipur as Team Vice-Captain; reached tournament finals and secured Silver medal.',
    'sample_cert_sports.jpg', 'RTU_Basketball_Silver_Medal.jpg', 'Verified',
    'ver-6d19e4a2-11c5-4d7a-b94f-5612ac9831d4', 1,
    NULL, 'Confirmed with Department of Physical Education.', '2026-02-02 16:45:00'
),
(
    5, 'SUB-2026-IT002', 3, 'ACM International Collegiate Programming Contest (ICPC)', 'Technical', '2026-02-10',
    'ACM India Chapter', 'Information Technology', 'Finalist',
    'Qualified for regional finals; ranked in top 5% among over 400 collegiate teams.',
    'sample_cert_icpc.pdf', 'ICPC_Regional_Finalist.pdf', 'Verified',
    'ver-7f91a2e3-55b6-4c8d-ae12-9843bf1042e5', 1,
    NULL, 'Verified with official ACM ranking bulletin.', '2026-02-15 10:20:00'
),
(
    6, 'SUB-2026-IT003', 3, 'Green Campus Tree Plantation & Cleanliness Drive', 'Extracurricular', '2026-03-12',
    'NSS Unit - SKIT Jaipur', 'Information Technology', 'Volunteer',
    'Coordinated 60 student volunteers for eco-restoration and campus plastic elimination campaign.',
    'sample_cert_nss.pdf', 'NSS_Cleanliness_Drive_Volunteer.pdf', 'Pending Verification',
    'ver-9c84e1b2-33d7-4f9a-8a19-6127dc8451f6', NULL,
    NULL, NULL, NULL
),
(
    7, 'SUB-2026-ECE001', 4, 'IEEE Robotics & Embedded IoT Expo', 'Technical', '2026-02-25',
    'MNIT Jaipur', 'Electronics & Communication', 'Participant',
    'Demonstrated autonomous line-following obstacle detection robot with ESP32 integration.',
    'sample_cert_mnit.jpg', 'MNIT_Robotics_Participation.jpg', 'Rejected',
    'ver-4a71b8e9-22c6-4d1e-9b88-1234ef5678a7', 1,
    'Uploaded certificate is blurry, illegible, and the participant name is cropped. Please upload a clear, high-resolution scan showing full credentials.',
    'Rejected due to illegible scan.', '2026-03-01 09:30:00'
),
(
    8, 'SUB-2026-ECE002', 4, 'Annual Blood Donation & Red Cross Camp', 'Extracurricular', '2026-03-08',
    'Indian Red Cross Society & SKIT Youth Club', 'Electronics & Communication', 'Volunteer',
    'Assisted medical officers with donor registry and blood unit logistics; awarded citation.',
    'sample_cert_redcross.pdf', 'RedCross_Donor_Camp_Citation.pdf', 'Resubmitted / Pending Review',
    'ver-5b82c9d1-33e7-4f2a-8c99-2345fa6789b8', NULL,
    NULL, NULL, NULL
)
ON DUPLICATE KEY UPDATE event_name=VALUES(event_name);

-- Demo Audit Logs
INSERT INTO verification_logs (activity_id, reviewer_id, action, previous_status, new_status, reason, logged_at)
VALUES
(1, NULL, 'Submitted', NULL, 'Pending Verification', 'Initial submission by student', '2026-01-05 10:00:00'),
(1, 1, 'Verified', 'Pending Verification', 'Verified', 'Verified genuine against AICTE national registry credentials.', '2026-01-10 14:30:00'),
(2, NULL, 'Submitted', NULL, 'Pending Verification', 'Initial submission by student', '2026-02-19 09:00:00'),
(2, 1, 'Verified', 'Pending Verification', 'Verified', 'Verified by SKIT Cultural Committee record book.', '2026-02-22 11:15:00'),
(3, NULL, 'Submitted', NULL, 'Pending Verification', 'Initial submission by student', '2026-03-06 14:15:00'),
(4, NULL, 'Submitted', NULL, 'Pending Verification', 'Initial submission by student', '2026-01-29 11:00:00'),
(4, 1, 'Verified', 'Pending Verification', 'Verified', 'Confirmed with Department of Physical Education.', '2026-02-02 16:45:00'),
(5, NULL, 'Submitted', NULL, 'Pending Verification', 'Initial submission by student', '2026-02-11 16:30:00'),
(5, 1, 'Verified', 'Pending Verification', 'Verified', 'Verified with official ACM ranking bulletin.', '2026-02-15 10:20:00'),
(6, NULL, 'Submitted', NULL, 'Pending Verification', 'Initial submission by student', '2026-03-13 09:45:00'),
(7, NULL, 'Submitted', NULL, 'Pending Verification', 'Initial submission by student', '2026-02-26 15:20:00'),
(7, 1, 'Rejected', 'Pending Verification', 'Rejected', 'Uploaded certificate is blurry, illegible, and the participant name is cropped. Please upload a clear, high-resolution scan showing full credentials.', '2026-03-01 09:30:00'),
(8, NULL, 'Submitted', NULL, 'Pending Verification', 'Initial submission by student', '2026-03-09 11:00:00'),
(8, 1, 'Rejected', 'Pending Verification', 'Rejected', 'Certificate missing official stamp.', '2026-03-10 12:00:00'),
(8, NULL, 'Resubmitted', 'Rejected', 'Resubmitted / Pending Review', 'Re-uploaded stamped certificate from Red Cross convenor.', '2026-03-11 15:00:00');