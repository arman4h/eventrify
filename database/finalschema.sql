-- ============================================================
-- Eventrify — Merged Database Schema + Seed Data
-- Consolidated from: schema.sql, migrate.sql, the club-registration
-- expansion migration, and demo seed inserts.
--
-- NOTE: The legacy "simple" schema (flat `users` table + a second,
-- incompatible `events`/`registrations`/`tasks` set, plus its own
-- seed.sql) has been DROPPED from this merge. It defined a second
-- `events` table with a different primary key/FK shape than the
-- multi-tenant `events` table below, and a flat `users` table that
-- duplicates the `students` / `club_users` / `system_admins` model.
-- Keeping both would break inserts (e.g. `event_date` doesn't exist
-- on this `events` table) and split your auth model in two.
-- If you actually need that simpler users/events/tasks module for
-- something else, it should live in its own database, not this one.
-- ============================================================

CREATE DATABASE IF NOT EXISTS eventrify CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE eventrify;

-- ============================================================
-- 1. STUDENTS
-- ============================================================
CREATE TABLE students (
    student_id       INT AUTO_INCREMENT PRIMARY KEY,
    university_id    VARCHAR(20) NOT NULL UNIQUE,
    full_name        VARCHAR(100) NOT NULL,
    email            VARCHAR(100) NOT NULL UNIQUE,
    password_hash    VARCHAR(255) NOT NULL,
    department       VARCHAR(100),
    batch            VARCHAR(20),
    phone            VARCHAR(20),
    interests        TEXT,
    profile_picture  VARCHAR(255),
    is_active        BOOLEAN NOT NULL DEFAULT TRUE,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- 2. SYSTEM_ADMINS
-- ============================================================
CREATE TABLE system_admins (
    admin_id       INT AUTO_INCREMENT PRIMARY KEY,
    full_name      VARCHAR(100) NOT NULL,
    email          VARCHAR(100) NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- 3. CLUBS
-- (columns from the original CREATE plus the "expansion" migration
--  merged in directly — the later ALTER TABLE ... ADD COLUMN IF NOT
--  EXISTS block from file 4 is no longer needed and was dropped)
-- ============================================================
CREATE TABLE clubs (
    club_id          INT AUTO_INCREMENT PRIMARY KEY,
    club_name        VARCHAR(100) NOT NULL UNIQUE,
    requested_by     INT,                          -- FK added below, after club_users exists
    university       VARCHAR(150),
    club_type        VARCHAR(50),
    established_year SMALLINT,
    description      TEXT,
    official_email   VARCHAR(100),
    website          VARCHAR(255),
    facebook         VARCHAR(255),
    social_links     VARCHAR(255),
    logo             LONGTEXT NULL,                -- base64-encoded club logo image
    status           ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    reviewed_by      INT,
    reviewed_at      TIMESTAMP NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_clubs_reviewed_by FOREIGN KEY (reviewed_by) REFERENCES system_admins(admin_id)
);

-- ============================================================
-- 4. CLUB_USERS
--    owner    : applied for the club, full rights
--    admin    : club-level admin, broad access
--    executive: restricted access, governed by executive_permissions
-- ============================================================
CREATE TABLE club_users (
    club_user_id     INT AUTO_INCREMENT PRIMARY KEY,
    club_id          INT NOT NULL,
    full_name        VARCHAR(100) NOT NULL,
    email            VARCHAR(100) NOT NULL UNIQUE,
    password_hash    VARCHAR(255) NOT NULL,
    phone            VARCHAR(20),
    student_id       VARCHAR(20),
    position         VARCHAR(50),
    university_email VARCHAR(100),
    role             ENUM('owner', 'admin', 'executive') NOT NULL DEFAULT 'executive',
    access_scope     ENUM('all', 'limited') NOT NULL DEFAULT 'all',
    status           ENUM('active', 'inactive', 'removed') NOT NULL DEFAULT 'active',
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_clubusers_club FOREIGN KEY (club_id) REFERENCES clubs(club_id) ON DELETE CASCADE
);

-- Now that club_users exists, wire up clubs.requested_by
ALTER TABLE clubs
    ADD CONSTRAINT fk_clubs_requested_by FOREIGN KEY (requested_by) REFERENCES club_users(club_user_id);

-- ============================================================
-- 5. CLUB_PERMISSION_PAGES
-- ============================================================
CREATE TABLE club_permission_pages (
    page_id    INT AUTO_INCREMENT PRIMARY KEY,
    page_key   VARCHAR(50) NOT NULL UNIQUE,
    page_name  VARCHAR(100) NOT NULL
);

-- ============================================================
-- 6. EXECUTIVE_PERMISSIONS
-- ============================================================
CREATE TABLE executive_permissions (
    club_user_id  INT NOT NULL,
    page_id       INT NOT NULL,
    access_level  ENUM('view', 'manage') NOT NULL DEFAULT 'view',
    granted_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (club_user_id, page_id),
    CONSTRAINT fk_execperm_user FOREIGN KEY (club_user_id) REFERENCES club_users(club_user_id) ON DELETE CASCADE,
    CONSTRAINT fk_execperm_page FOREIGN KEY (page_id) REFERENCES club_permission_pages(page_id) ON DELETE CASCADE
);

-- ============================================================
-- 7. EVENTS
-- ============================================================
CREATE TABLE events (
    event_id               INT AUTO_INCREMENT PRIMARY KEY,
    club_id                INT NOT NULL,
    title                  VARCHAR(150) NOT NULL,
    description            TEXT,
    category               VARCHAR(50),
    poster                 LONGTEXT,
    venue                  VARCHAR(150),
    start_time             DATETIME NOT NULL,
    end_time               DATETIME NOT NULL,
    registration_deadline  DATETIME,
    capacity               INT NOT NULL DEFAULT 0,
    status                 ENUM('draft', 'published', 'cancelled', 'completed') NOT NULL DEFAULT 'draft',
    created_by             INT NOT NULL,
    created_at             TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at             TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_events_club       FOREIGN KEY (club_id) REFERENCES clubs(club_id) ON DELETE CASCADE,
    CONSTRAINT fk_events_created_by FOREIGN KEY (created_by) REFERENCES club_users(club_user_id),
    INDEX idx_events_club_status (club_id, status)
);

-- ============================================================
-- 8. EVENT_REGISTRATION_FIELDS (custom form builder per event)
-- ============================================================
CREATE TABLE event_registration_fields (
    field_id       INT AUTO_INCREMENT PRIMARY KEY,
    event_id       INT NOT NULL,
    field_label    VARCHAR(100) NOT NULL,
    field_type     ENUM('text', 'number', 'email', 'dropdown', 'checkbox', 'date') NOT NULL,
    field_options  TEXT,
    is_required    BOOLEAN NOT NULL DEFAULT FALSE,
    display_order  INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_fields_event FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE
);

-- ============================================================
-- 9. EVENT_REGISTRATIONS
-- ============================================================
CREATE TABLE event_registrations (
    registration_id    INT AUTO_INCREMENT PRIMARY KEY,
    event_id           INT NOT NULL,
    student_id         INT,
    guest_name         VARCHAR(100),
    guest_student_id   VARCHAR(20),
    is_walkin          BOOLEAN NOT NULL DEFAULT FALSE,
    status             ENUM('registered', 'waitlisted', 'cancelled', 'attended', 'no_show')
                       NOT NULL DEFAULT 'registered',
    waitlist_position  INT,
    registered_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    checked_in_at      TIMESTAMP NULL,
    check_in_method    ENUM('qr', 'student_id', 'manual'),
    checked_in_by      INT,
    CONSTRAINT fk_reg_event       FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    CONSTRAINT fk_reg_student     FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    CONSTRAINT fk_reg_checked_by  FOREIGN KEY (checked_in_by) REFERENCES club_users(club_user_id),
    UNIQUE KEY uq_event_student (event_id, student_id),
    INDEX idx_reg_event_status (event_id, status)
);

-- ============================================================
-- 10. REGISTRATION_FIELD_RESPONSES
-- ============================================================
CREATE TABLE registration_field_responses (
    response_id      INT AUTO_INCREMENT PRIMARY KEY,
    registration_id  INT NOT NULL,
    field_id         INT NOT NULL,
    response_value   TEXT,
    CONSTRAINT fk_resp_registration FOREIGN KEY (registration_id) REFERENCES event_registrations(registration_id) ON DELETE CASCADE,
    CONSTRAINT fk_resp_field        FOREIGN KEY (field_id) REFERENCES event_registration_fields(field_id) ON DELETE CASCADE
);

-- ============================================================
-- 11. REPORTS
-- ============================================================
CREATE TABLE reports (
    report_id          INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id        INT NOT NULL,
    reported_club_id   INT,
    reported_event_id  INT,
    subject            VARCHAR(150) NOT NULL,
    description        TEXT NOT NULL,
    status             ENUM('open', 'under_review', 'resolved', 'dismissed') NOT NULL DEFAULT 'open',
    reviewed_by        INT,
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at        TIMESTAMP NULL,
    CONSTRAINT fk_reports_reporter FOREIGN KEY (reporter_id) REFERENCES students(student_id) ON DELETE CASCADE,
    CONSTRAINT fk_reports_club     FOREIGN KEY (reported_club_id) REFERENCES clubs(club_id) ON DELETE SET NULL,
    CONSTRAINT fk_reports_event    FOREIGN KEY (reported_event_id) REFERENCES events(event_id) ON DELETE SET NULL,
    CONSTRAINT fk_reports_reviewer FOREIGN KEY (reviewed_by) REFERENCES system_admins(admin_id)
);

-- ============================================================
-- 12. ROOM_REQUESTS (from migrate.sql)
-- ============================================================
CREATE TABLE room_requests (
    request_id             INT AUTO_INCREMENT PRIMARY KEY,
    club_id                INT NOT NULL,
    event_id               INT NULL,
    requested_date         DATE NOT NULL,
    start_time             TIME NOT NULL,
    end_time               TIME NOT NULL,
    expected_participants  INT NOT NULL DEFAULT 0,
    preferred_room         VARCHAR(100),
    reason                 TEXT,
    status                 ENUM('pending', 'approved', 'declined') NOT NULL DEFAULT 'pending',
    review_notes           TEXT,
    reviewed_by            INT,
    reviewed_at            TIMESTAMP NULL,
    created_at             TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_roomreq_club      FOREIGN KEY (club_id) REFERENCES clubs(club_id) ON DELETE CASCADE,
    CONSTRAINT fk_roomreq_event     FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE SET NULL,
    CONSTRAINT fk_roomreq_reviewer  FOREIGN KEY (reviewed_by) REFERENCES system_admins(admin_id)
);

-- ============================================================
-- STATIC LOOKUP DATA
-- ============================================================
INSERT INTO club_permission_pages (page_key, page_name) VALUES
    ('club_profile',   'Club Profile'),
    ('members',        'Member Management'),
    ('events',         'Event Management'),
    ('attendance',     'Attendance / Check-in'),
    ('registrations',  'Registrations & Waitlist'),
    ('reports',        'Reports'),
    ('room_requests',  'Room Requests');

-- Single system admin account (was inserted then immediately UPDATEd/
-- re-inserted across two files — collapsed to one clean insert).
-- Change the password before real use.
INSERT INTO system_admins (full_name, email, password_hash)
VALUES ('System Administrator', 'admin@eventrify.com', '$2y$10$2YMFAHCTTU05KX3sD9kxGO3FZVKmGvVGuyvwD/AdsHG7J5NwrdyJy');

-- ============================================================
-- DEMO SEED DATA
-- ============================================================

-- Students
INSERT INTO students (university_id, full_name, email, password_hash, department, batch, phone, interests, is_active) VALUES
('0112211234', 'Arman Hossain', 'arman4hn@gmail.com', '$2y$10$xCKJn5uN49hsKLzkKC9l4.coCtFSJSRro8QenqyU1ttMvwF/5r5mm', 'Computer Science & Engineering', 'Summer 2025', '+8801712345678', 'AI, Robotics, Web Development', 1),
('0112310456', 'Samiha Rahman', 'samiha@example.com', '$2y$10$xCKJn5uN49hsKLzkKC9l4.coCtFSJSRro8QenqyU1ttMvwF/5r5mm', 'Computer Science & Engineering', 'Spring 2025', '+8801811122233', 'Programming, Music', 1),
('0112220789', 'Rakib Hasan', 'rakib@example.com', '$2y$10$xCKJn5uN49hsKLzkKC9l4.coCtFSJSRro8QenqyU1ttMvwF/5r5mm', 'Electrical & Electronic Engineering', 'Fall 2024', '+8801912345678', 'Robotics, IoT', 1),
('0112440567', 'Nusrat Jahan', 'nusrat@example.com', '$2y$10$xCKJn5uN49hsKLzkKC9l4.coCtFSJSRro8QenqyU1ttMvwF/5r5mm', 'Business Administration', 'Fall 2025', '+8801612345678', 'Entrepreneurship', 1);

-- Clubs (columns now match the merged CREATE TABLE directly — no
-- later ALTER TABLE needed)
INSERT INTO clubs (club_name, description, university, club_type, established_year, official_email, website, facebook, social_links, status, reviewed_at, created_at) VALUES
('UIU Robotics Club', 'A student-run club dedicated to robotics, automation and hands-on engineering projects.', 'United International University', 'Technical', 2018, 'robotics@uiu.edu.bd', 'https://robotics.uiu.edu.bd', 'https://facebook.com/uiu.robotics', 'Instagram: @uiu.robotics', 'approved', NOW(), NOW() - INTERVAL 400 DAY),
('UIU Programming League', 'The competitive programming community of UIU. We train, host contests and go to ICPC.', 'United International University', 'Technical', 2019, 'programming@uiu.edu.bd', '', 'https://facebook.com/uiu.programming', '', 'approved', NOW(), NOW() - INTERVAL 350 DAY),
('UIU AI & Machine Learning Club', 'Exploring artificial intelligence, machine learning and data science through workshops and research.', 'United International University', 'Academic', 2022, 'ai.ml@uiu.edu.bd', 'https://aiml.uiu.edu.bd', 'https://facebook.com/uiu.aiml', '', 'approved', NOW(), NOW() - INTERVAL 300 DAY),
('UIU Cultural Society', 'Celebrating diversity through music, drama, art and cultural programs.', 'United International University', 'Cultural', 2015, 'cultural@uiu.edu.bd', '', 'https://facebook.com/uiu.cultural', '', 'pending', NULL, NOW() - INTERVAL 7 DAY),
('UIU Business & Entrepreneurship Club', 'Fostering entrepreneurial thinking through case competitions, startup talks and networking.', 'United International University', 'Social', 2020, 'business@uiu.edu.bd', '', 'https://facebook.com/uiu.business', 'LinkedIn: uiubusiness', 'pending', NULL, NOW() - INTERVAL 3 DAY);

-- Club users
INSERT INTO club_users (club_id, full_name, email, password_hash, phone, student_id, position, university_email, role, status) VALUES
(1, 'Mahi Islam', 'mahi@gmail.com', '$2y$10$RXNrxkHe1TZZDMZwLYt5IueR6kOjO6bd4xWOH9vdFvfU0XfBpunbW', '+8801712345000', '0112210001', 'President', 'mahi.islam@bscse.uiu.ac.bd', 'owner', 'active'),
(1, 'Fahim Karim', 'fahim.robotics@example.com', '$2y$10$xCKJn5uN49hsKLzkKC9l4.coCtFSJSRro8QenqyU1ttMvwF/5r5mm', '+8801712345111', '0112210002', 'Vice President', 'fahim.karim@bscse.uiu.ac.bd', 'admin', 'active'),
(2, 'Tasnim Ahmed', 'tasnim.prog@example.com', '$2y$10$xCKJn5uN49hsKLzkKC9l4.coCtFSJSRro8QenqyU1ttMvwF/5r5mm', '+8801811122000', '0112220001', 'President', 'tasnim.ahmed@bscse.uiu.ac.bd', 'owner', 'active'),
(3, 'Sadia Kabir', 'sadia.ai@example.com', '$2y$10$xCKJn5uN49hsKLzkKC9l4.coCtFSJSRro8QenqyU1ttMvwF/5r5mm', '+8801911222333', '0112230001', 'President', 'sadia.kabir@bscse.uiu.ac.bd', 'owner', 'active'),
(4, 'Nusrat Jahan', 'nusrat.cultural@uiu.edu.bd', '$2y$10$xCKJn5uN49hsKLzkKC9l4.coCtFSJSRro8QenqyU1ttMvwF/5r5mm', '+8801612345678', '0112440567', 'President', 'nusrat.jahan@bscse.uiu.ac.bd', 'owner', 'active'),
(5, 'Tanvir Rahman', 'tanvir.business@uiu.edu.bd', '$2y$10$xCKJn5uN49hsKLzkKC9l4.coCtFSJSRro8QenqyU1ttMvwF/5r5mm', '+8801512345678', '0112550123', 'Vice President', 'tanvir.rahman@bba.uiu.ac.bd', 'owner', 'active');

UPDATE clubs SET requested_by = (SELECT club_user_id FROM club_users WHERE email = 'mahi@gmail.com') WHERE club_id = 1;
UPDATE clubs SET requested_by = (SELECT club_user_id FROM club_users WHERE email = 'tasnim.prog@example.com') WHERE club_id = 2;
UPDATE clubs SET requested_by = (SELECT club_user_id FROM club_users WHERE email = 'sadia.ai@example.com') WHERE club_id = 3;
UPDATE clubs SET requested_by = (SELECT club_user_id FROM club_users WHERE email = 'nusrat.cultural@uiu.edu.bd') WHERE club_id = 4;
UPDATE clubs SET requested_by = (SELECT club_user_id FROM club_users WHERE email = 'tanvir.business@uiu.edu.bd') WHERE club_id = 5;

-- Events
INSERT INTO events (club_id, title, description, category, venue, start_time, end_time, registration_deadline, capacity, status, created_by) VALUES
(1, 'Intro to AI Workshop', 'A hands-on beginner workshop covering machine learning fundamentals, neural networks, and a small hands-on image classification exercise using Python.', 'Workshop', 'Library Building, Lab 203', '2026-10-05 14:00:00', '2026-10-05 17:00:00', '2026-10-03 23:59:00', 80, 'published', 2),
(1, 'Inter-University Robotics Competition', 'Battle bots, line followers and maze solvers. Teams of up to 4. Prizes for the top 3 teams with certificates for all finalists.', 'Competition', 'Academic Building 3, Ground Floor Hall', '2026-11-14 09:00:00', '2026-11-14 18:00:00', '2026-11-10 23:59:00', 200, 'published', 2),
(1, 'Beginner Arduino Bootcamp', 'Get started with microcontrollers. Build 3 real circuits across the day and keep your own Arduino kit.', 'Workshop', 'Lab 104, Science Building', '2026-09-28 10:00:00', '2026-09-28 16:00:00', '2026-09-26 23:59:00', 50, 'published', 2),
(2, 'Web Development Bootcamp', 'A two-day intensive bootcamp on modern web development: HTML, CSS, JavaScript, and React basics with a group project.', 'Workshop', 'Academic Building 1, Room 501', '2026-10-16 09:00:00', '2026-10-17 17:00:00', '2026-10-12 23:59:00', 100, 'published', 3),
(2, 'ICPC Practice Contest #1', 'Solve problems under contest conditions with a virtual judge. Ranked practice for upcoming regional contests.', 'Contest', 'Computer Center, CC-3', '2026-09-25 10:00:00', '2026-09-25 13:30:00', '2026-09-24 23:59:00', 60, 'published', 3),
(3, 'Research Methodology Seminar', 'Learn how to pick a research topic, write a literature review, and publish your first paper. Panel Q&A included.', 'Seminar', 'Auditorium, Admin Building', '2026-10-20 15:00:00', '2026-10-20 17:30:00', '2026-10-18 23:59:00', 150, 'published', 4),
(1, 'Robotics Demo Day', 'Annual showcase of our members robots, drones and automation projects to the whole university.', 'Showcase', 'Central Plaza', '2026-12-01 11:00:00', '2026-12-01 16:00:00', '2026-11-28 23:59:00', 300, 'draft', 2);

-- Event registrations
-- (fixed: original demo data inserted status = 'walkin_registered',
--  which is not a valid value of this column's ENUM and would have
--  errored; inserted as 'registered' directly instead)
INSERT INTO event_registrations (event_id, student_id, is_walkin, status, registered_at) VALUES
(1, 1, 0, 'registered', NOW() - INTERVAL 3 DAY),
(1, 2, 0, 'registered', NOW() - INTERVAL 2 DAY),
(1, 3, 0, 'waitlisted', NOW() - INTERVAL 1 DAY),
(2, 1, 0, 'registered', NOW() - INTERVAL 5 DAY),
(2, 2, 0, 'registered', NOW() - INTERVAL 4 DAY),
(4, 1, 0, 'registered', NOW() - INTERVAL 6 DAY),
(4, 2, 1, 'registered', NOW() - INTERVAL 1 DAY),
(5, 3, 0, 'registered', NOW() - INTERVAL 2 DAY);

-- Custom registration fields for the AI Workshop (event 1)
INSERT INTO event_registration_fields (event_id, field_label, field_type, field_options, is_required, display_order) VALUES
(1, 'Have you worked with Python before?', 'dropdown', 'Beginner,Intermediate,Advanced', 1, 1),
(1, 'Which track are you most interested in?', 'dropdown', 'Machine Learning,Computer Vision,NLP', 0, 2),
(1, 'Laptop availability', 'checkbox', 'I can bring a laptop', 0, 3),
(1, 'T-shirt size', 'dropdown', 'S,M,L,XL', 0, 4);

-- Default registration fields for every event (no duplicates)
INSERT INTO event_registration_fields (event_id, field_label, field_type, field_options, is_required, display_order)
SELECT e.event_id, d.label, d.type, d.options, d.required, d.ord
FROM events e
CROSS JOIN (
    SELECT 'Full Name' label, 'text' type, '' options, 1 required, 1 ord
    UNION ALL SELECT 'Email', 'email', '', 1, 2
    UNION ALL SELECT 'Student ID', 'text', '', 1, 3
    UNION ALL SELECT 'Department', 'dropdown', 'CSE,EEE,DS,English,BBA,EDS,Economics', 1, 4
) d
WHERE NOT EXISTS (
    SELECT 1 FROM event_registration_fields f WHERE f.event_id = e.event_id AND f.field_label = d.label
);

-- Room requests
INSERT INTO room_requests (club_id, event_id, requested_date, start_time, end_time, expected_participants, preferred_room, reason, status, reviewed_at) VALUES
(1, 2, '2026-11-14', '08:30:00', '09:50:00', 200, 'Ground Floor Hall', 'Large hall needed for the competition arena and seating for teams.', 'pending', NULL),
(3, 6, '2026-10-20', '09:51:00', '11:10:00', 150, 'Auditorium', 'Auditorium fits the expected seminar turnout.', 'approved', NOW() - INTERVAL 2 DAY),
(1, 7, '2026-12-01', '12:31:00', '13:40:00', 300, 'Open Ground', 'Outdoor showcase - need permission to use the plaza.', 'pending', NULL),
(2, 4, '2026-10-16', '15:11:00', '16:30:00', 100, 'Room 501', 'Two-day bootcamp needs a projector and AC room.', 'declined', NOW() - INTERVAL 1 DAY);

-- Reports
INSERT INTO reports (reporter_id, reported_club_id, reported_event_id, subject, description, status, created_at) VALUES
(1, 1, 2, 'Registration confirmation missing', 'I registered for the robotics competition but never received a confirmation email.', 'open', NOW() - INTERVAL 1 DAY),
(2, NULL, NULL, 'Suggest feature: calendar sync', 'It would be great if registered events could be added to Google Calendar.', 'under_review', NOW() - INTERVAL 4 DAY);