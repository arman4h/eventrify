
-- ============================================
-- Eventrify - Database Schema
-- DBMS Lab Project
-- ============================================

CREATE DATABASE IF NOT EXISTS eventrify CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE eventrify;

CREATE TABLE students (
    student_id       INT AUTO_INCREMENT PRIMARY KEY,
    university_id    VARCHAR(20) NOT NULL UNIQUE,   -- the student's UIU ID number
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
-- 2. SYSTEM_ADMINS  (platform-level administrators only)
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
-- ============================================================
CREATE TABLE clubs (
    club_id       INT AUTO_INCREMENT PRIMARY KEY,
    club_name     VARCHAR(100) NOT NULL UNIQUE,
    description   TEXT,
    logo          VARCHAR(255),
    status        ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    reviewed_by   INT,                             -- system_admins.admin_id who approved/rejected
    reviewed_at   TIMESTAMP NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_clubs_reviewed_by FOREIGN KEY (reviewed_by) REFERENCES system_admins(admin_id)
);

-- ============================================================
-- 4. CLUB_USERS  (everyone with a login on the club side:
--    owner / admin / executive)
--    - owner    : the person who applied for the club, full rights
--    - admin    : club-level admin, broad access
--    - executive: restricted access, governed by executive_permissions
-- ============================================================
CREATE TABLE club_users (
    club_user_id   INT AUTO_INCREMENT PRIMARY KEY,
    club_id        INT NOT NULL,
    full_name      VARCHAR(100) NOT NULL,
    email          VARCHAR(100) NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    phone          VARCHAR(20),
    role           ENUM('owner', 'admin', 'executive') NOT NULL DEFAULT 'executive',
    status         ENUM('active', 'inactive', 'removed') NOT NULL DEFAULT 'active',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_clubusers_club FOREIGN KEY (club_id) REFERENCES clubs(club_id) ON DELETE CASCADE
);

-- Now that club_users exists, link back to whoever requested the club (its owner)
ALTER TABLE clubs
    ADD COLUMN requested_by INT AFTER club_name,
    ADD CONSTRAINT fk_clubs_requested_by FOREIGN KEY (requested_by) REFERENCES club_users(club_user_id);

CREATE TABLE club_permission_pages (
    page_id    INT AUTO_INCREMENT PRIMARY KEY,
    page_key   VARCHAR(50) NOT NULL UNIQUE,   -- e.g. 'events', 'attendance'
    page_name  VARCHAR(100) NOT NULL          -- e.g. 'Event Management'
);

-- ============================================================
-- 6. EXECUTIVE_PERMISSIONS  (which pages an executive can access)
--    Only meaningful for club_users.role = 'executive'.
--    Owners and admins are assumed to have full access in the app layer.
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
    venue                  VARCHAR(150),
    start_time             DATETIME NOT NULL,
    end_time               DATETIME NOT NULL,
    registration_deadline  DATETIME,
    capacity               INT NOT NULL DEFAULT 0,
    status                 ENUM('draft', 'published', 'cancelled', 'completed') NOT NULL DEFAULT 'draft',
    created_by             INT NOT NULL,           -- club_users.club_user_id
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
    field_options  TEXT,                           -- comma-separated options for dropdown/checkbox
    is_required    BOOLEAN NOT NULL DEFAULT FALSE,
    display_order  INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_fields_event FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE
);

-- ============================================================
-- 9. EVENT_REGISTRATIONS  (registration / waitlist / attendance)
-- ============================================================
CREATE TABLE event_registrations (
    registration_id    INT AUTO_INCREMENT PRIMARY KEY,
    event_id           INT NOT NULL,
    student_id         INT,                         -- NULL for walk-in guests
    guest_name         VARCHAR(100),
    guest_student_id   VARCHAR(20),
    is_walkin          BOOLEAN NOT NULL DEFAULT FALSE,
    status             ENUM('registered', 'waitlisted', 'cancelled', 'attended', 'no_show')
                       NOT NULL DEFAULT 'registered',
    waitlist_position  INT,
    registered_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    checked_in_at      TIMESTAMP NULL,
    check_in_method    ENUM('qr', 'student_id', 'manual'),
    checked_in_by      INT,                         -- club_users.club_user_id who verified check-in
    CONSTRAINT fk_reg_event       FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    CONSTRAINT fk_reg_student     FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    CONSTRAINT fk_reg_checked_by  FOREIGN KEY (checked_in_by) REFERENCES club_users(club_user_id),
    UNIQUE KEY uq_event_student (event_id, student_id),   -- prevents a logged-in student double-registering
    INDEX idx_reg_event_status (event_id, status)
);

-- ============================================================
-- 10. REGISTRATION_FIELD_RESPONSES (answers to custom form fields)
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
-- 11. REPORTS (student -> system admin issue reports on clubs/events)
-- ============================================================
CREATE TABLE reports (
    report_id          INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id        INT NOT NULL,               -- students.student_id
    reported_club_id   INT,
    reported_event_id  INT,
    subject            VARCHAR(150) NOT NULL,
    description        TEXT NOT NULL,
    status             ENUM('open', 'under_review', 'resolved', 'dismissed') NOT NULL DEFAULT 'open',
    reviewed_by        INT,                         -- system_admins.admin_id
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at        TIMESTAMP NULL,
    CONSTRAINT fk_reports_reporter FOREIGN KEY (reporter_id) REFERENCES students(student_id) ON DELETE CASCADE,
    CONSTRAINT fk_reports_club     FOREIGN KEY (reported_club_id) REFERENCES clubs(club_id) ON DELETE SET NULL,
    CONSTRAINT fk_reports_event    FOREIGN KEY (reported_event_id) REFERENCES events(event_id) ON DELETE SET NULL,
    CONSTRAINT fk_reports_reviewer FOREIGN KEY (reviewed_by) REFERENCES system_admins(admin_id)
);

INSERT INTO club_permission_pages (page_key, page_name) VALUES
    ('club_profile',   'Club Profile'),
    ('members',        'Member Management'),
    ('events',         'Event Management'),
    ('attendance',     'Attendance / Check-in'),
    ('registrations',  'Registrations & Waitlist'),
    ('reports',        'Reports');

-- ============================================================
-- SEED DATA: a system administrator account (change password before use)
-- ============================================================
INSERT INTO system_admins (full_name, email, password_hash)
VALUES ('admin@eventrify.com', 'admin@clubevent.local', '$2y$10$bVy6GQ5QsPpPYQkDgAFO4uu9R8Awq7EAG8vMlFI.W9uNWhtklFuL.');