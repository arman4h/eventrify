-- ============================================================
-- Eventrify — Schema additions + Demo seed data
-- Run this AFTER database/schema.sql (or directly on an existing eventrify DB)
-- Import:  mysql -u root < database/migrate.sql
-- ============================================================

USE eventrify;

-- ────────────────────────────────────────────────────────────
-- Room requests (club -> admin venue allotment requests)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS room_requests (
    request_id        INT AUTO_INCREMENT PRIMARY KEY,
    club_id           INT NOT NULL,
    event_id          INT NULL,
    requested_date    DATE NOT NULL,
    start_time        TIME NOT NULL,
    end_time          TIME NOT NULL,
    expected_participants INT NOT NULL DEFAULT 0,
    preferred_building VARCHAR(100),
    preferred_room    VARCHAR(100),
    reason            TEXT,
    status            ENUM('pending', 'approved', 'declined') NOT NULL DEFAULT 'pending',
    review_notes      TEXT,
    reviewed_by       INT,
    reviewed_at       TIMESTAMP NULL,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_roomreq_club   FOREIGN KEY (club_id) REFERENCES clubs(club_id) ON DELETE CASCADE,
    CONSTRAINT fk_roomreq_event  FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE SET NULL,
    CONSTRAINT fk_roomreq_reviewer FOREIGN KEY (reviewed_by) REFERENCES system_admins(admin_id)
);

-- ────────────────────────────────────────────────────────────
-- Fix system admin demo account (email used by the login form)
-- ────────────────────────────────────────────────────────────
UPDATE system_admins SET email = 'admin@eventrify.com', full_name = 'System Administrator'
WHERE email = 'admin@clubevent.local';

INSERT INTO system_admins (full_name, email, password_hash)
SELECT 'System Administrator', 'admin@eventrify.com', '$2y$10$2YMFAHCTTU05KX3sD9kxGO3FZVKmGvVGuyvwD/AdsHG7J5NwrdyJy'
WHERE NOT EXISTS (SELECT 1 FROM system_admins WHERE email = 'admin@eventrify.com');

-- ────────────────────────────────────────────────────────────
-- Demo students
-- ────────────────────────────────────────────────────────────
INSERT INTO students (university_id, full_name, email, password_hash, department, batch, phone, interests, is_active) VALUES
('0112211234', 'Arman Hossain', 'arman4hn@gmail.com', '$2y$10$xCKJn5uN49hsKLzkKC9l4.coCtFSJSRro8QenqyU1ttMvwF/5r5mm', 'Computer Science & Engineering', 'Summer 2025', '+8801712345678', 'AI, Robotics, Web Development', 1),
('0112310456', 'Samiha Rahman', 'samiha@example.com', '$2y$10$xCKJn5uN49hsKLzkKC9l4.coCtFSJSRro8QenqyU1ttMvwF/5r5mm', 'Computer Science & Engineering', 'Spring 2025', '+8801811122233', 'Programming, Music', 1),
('0112220789', 'Rakib Hasan', 'rakib@example.com', '$2y$10$xCKJn5uN49hsKLzkKC9l4.coCtFSJSRro8QenqyU1ttMvwF/5r5mm', 'Electrical & Electronic Engineering', 'Fall 2024', '+8801912345678', 'Robotics, IoT', 1),
('0112440567', 'Nusrat Jahan', 'nusrat@example.com', '$2y$10$xCKJn5uN49hsKLzkKC9l4.coCtFSJSRro8QenqyU1ttMvwF/5r5mm', 'Business Administration', 'Fall 2025', '+8801612345678', 'Entrepreneurship', 1);

-- ────────────────────────────────────────────────────────────
-- Demo clubs
-- ────────────────────────────────────────────────────────────
INSERT INTO clubs (club_name, description, status, reviewed_at, created_at) VALUES
('UIU Robotics Club', 'A student-run club dedicated to robotics, automation and hands-on engineering projects.', 'approved', NOW(), NOW() - INTERVAL 400 DAY),
('UIU Programming League', 'The competitive programming community of UIU. We train, host contests and go to ICPC.', 'approved', NOW(), NOW() - INTERVAL 350 DAY),
('UIU AI & Machine Learning Club', 'Exploring artificial intelligence, machine learning and data science through workshops and research.', 'approved', NOW(), NOW() - INTERVAL 300 DAY),
('UIU Cultural Society', 'Celebrating diversity through music, drama, art and cultural programs.', 'pending', NULL, NOW() - INTERVAL 7 DAY),
('UIU Business & Entrepreneurship Club', 'Fostering entrepreneurial thinking through case competitions, startup talks and networking.', 'pending', NULL, NOW() - INTERVAL 3 DAY);

-- ────────────────────────────────────────────────────────────
-- Demo club users
-- ────────────────────────────────────────────────────────────
INSERT INTO club_users (club_id, full_name, email, password_hash, phone, role, status) VALUES
(1, 'Mahi Islam', 'mahi@gmail.com', '$2y$10$RXNrxkHe1TZZDMZwLYt5IueR6kOjO6bd4xWOH9vdFvfU0XfBpunbW', '+8801712345000', 'owner', 'active'),
(1, 'Fahim Karim', 'fahim.robotics@example.com', '$2y$10$xCKJn5uN49hsKLzkKC9l4.coCtFSJSRro8QenqyU1ttMvwF/5r5mm', '+8801712345111', 'admin', 'active'),
(2, 'Tasnim Ahmed', 'tasnim.prog@example.com', '$2y$10$xCKJn5uN49hsKLzkKC9l4.coCtFSJSRro8QenqyU1ttMvwF/5r5mm', '+8801811122000', 'owner', 'active'),
(3, 'Sadia Kabir', 'sadia.ai@example.com', '$2y$10$xCKJn5uN49hsKLzkKC9l4.coCtFSJSRro8QenqyU1ttMvwF/5r5mm', '+8801911222333', 'owner', 'active');

UPDATE clubs SET requested_by = (SELECT club_user_id FROM club_users WHERE email = 'mahi@gmail.com') WHERE club_id = 1;
UPDATE clubs SET requested_by = (SELECT club_user_id FROM club_users WHERE email = 'tasnim.prog@example.com') WHERE club_id = 2;
UPDATE clubs SET requested_by = (SELECT club_user_id FROM club_users WHERE email = 'sadia.ai@example.com') WHERE club_id = 3;

-- ────────────────────────────────────────────────────────────
-- Demo events
-- ────────────────────────────────────────────────────────────
INSERT INTO events (club_id, title, description, category, venue, start_time, end_time, registration_deadline, capacity, status, created_by) VALUES
(1, 'Intro to AI Workshop', 'A hands-on beginner workshop covering machine learning fundamentals, neural networks, and a small hands-on image classification exercise using Python.', 'Workshop', 'Library Building, Lab 203', '2026-10-05 14:00:00', '2026-10-05 17:00:00', '2026-10-03 23:59:00', 80, 'published', 2),
(1, 'Inter-University Robotics Competition', 'Battle bots, line followers and maze solvers. Teams of up to 4. Prizes for the top 3 teams with certificates for all finalists.', 'Competition', 'Academic Building 3, Ground Floor Hall', '2026-11-14 09:00:00', '2026-11-14 18:00:00', '2026-11-10 23:59:00', 200, 'published', 2),
(1, 'Beginner Arduino Bootcamp', 'Get started with microcontrollers. Build 3 real circuits across the day and keep your own Arduino kit.', 'Workshop', 'Lab 104, Science Building', '2026-09-28 10:00:00', '2026-09-28 16:00:00', '2026-09-26 23:59:00', 50, 'published', 2),
(2, 'Web Development Bootcamp', 'A two-day intensive bootcamp on modern web development: HTML, CSS, JavaScript, and React basics with a group project.', 'Workshop', 'Academic Building 1, Room 501', '2026-10-16 09:00:00', '2026-10-17 17:00:00', '2026-10-12 23:59:00', 100, 'published', 3),
(2, 'ICPC Practice Contest #1', 'Solve problems under contest conditions with a virtual judge. Ranked practice for upcoming regional contests.', 'Contest', 'Computer Center, CC-3', '2026-09-25 10:00:00', '2026-09-25 13:30:00', '2026-09-24 23:59:00', 60, 'published', 3),
(3, 'Research Methodology Seminar', 'Learn how to pick a research topic, write a literature review, and publish your first paper. Panel Q&A included.', 'Seminar', 'Auditorium, Admin Building', '2026-10-20 15:00:00', '2026-10-20 17:30:00', '2026-10-18 23:59:00', 150, 'published', 4),
(1, 'Robotics Demo Day', 'Annual showcase of our members robots, drones and automation projects to the whole university.', 'Showcase', 'Central Plaza', '2026-12-01 11:00:00', '2026-12-01 16:00:00', '2026-11-28 23:59:00', 300, 'draft', 2);

-- ────────────────────────────────────────────────────────────
-- Demo registrations
-- ────────────────────────────────────────────────────────────
INSERT INTO event_registrations (event_id, student_id, is_walkin, status, registered_at) VALUES
(1, 1, 0, 'registered', NOW() - INTERVAL 3 DAY),
(1, 2, 0, 'registered', NOW() - INTERVAL 2 DAY),
(1, 3, 0, 'waitlisted', NOW() - INTERVAL 1 DAY),
(2, 1, 0, 'registered', NOW() - INTERVAL 5 DAY),
(2, 2, 0, 'registered', NOW() - INTERVAL 4 DAY),
(4, 1, 0, 'registered', NOW() - INTERVAL 6 DAY),
(4, 2, 0, 'walkin_registered', NOW() - INTERVAL 1 DAY),
(5, 3, 0, 'registered', NOW() - INTERVAL 2 DAY);

UPDATE event_registrations SET status = 'registered' WHERE status = 'walkin_registered';

-- ────────────────────────────────────────────────────────────
-- Custom registration fields for the AI Workshop (event 1)
-- ────────────────────────────────────────────────────────────
INSERT INTO event_registration_fields (event_id, field_label, field_type, field_options, is_required, display_order) VALUES
(1, 'Have you worked with Python before?', 'dropdown', 'Beginner,Intermediate,Advanced', 1, 1),
(1, 'Which track are you most interested in?', 'dropdown', 'Machine Learning,Computer Vision,NLP', 0, 2),
(1, 'Laptop availability', 'checkbox', 'I can bring a laptop', 0, 3),
(1, 'T-shirt size', 'dropdown', 'S,M,L,XL', 0, 4);

-- ────────────────────────────────────────────────────────────
-- Default registration fields for EVERY event (no duplicates)
-- name, email, student id, department
-- ────────────────────────────────────────────────────────────
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

-- ────────────────────────────────────────────────────────────
-- Demo room requests
-- ────────────────────────────────────────────────────────────
INSERT INTO room_requests (club_id, event_id, requested_date, start_time, end_time, expected_participants, preferred_building, preferred_room, reason, status, reviewed_at) VALUES
(1, 2, '2026-11-14', '09:00:00', '18:00:00', 200, 'Academic Building 3', 'Ground Floor Hall', 'Large hall needed for the competition arena and seating for teams.', 'pending', NULL),
(3, 6, '2026-10-20', '15:00:00', '17:30:00', 150, 'Admin Building', 'Auditorium', 'Auditorium fits the expected seminar turnout.', 'approved', NOW() - INTERVAL 2 DAY),
(1, 7, '2026-12-01', '11:00:00', '16:00:00', 300, 'Central Plaza', 'Open Ground', 'Outdoor showcase - need permission to use the plaza.', 'pending', NULL),
(2, 4, '2026-10-16', '09:00:00', '17:00:00', 100, 'Academic Building 1', 'Room 501', 'Two-day bootcamp needs a projector and AC room.', 'declined', NOW() - INTERVAL 1 DAY);

-- ────────────────────────────────────────────────────────────
-- Demo reports
-- ────────────────────────────────────────────────────────────
INSERT INTO reports (reporter_id, reported_club_id, reported_event_id, subject, description, status, created_at) VALUES
(1, 1, 2, 'Registration confirmation missing', 'I registered for the robotics competition but never received a confirmation email.', 'open', NOW() - INTERVAL 1 DAY),
(2, NULL, NULL, 'Suggest feature: calendar sync', 'It would be great if registered events could be added to Google Calendar.', 'under_review', NOW() - INTERVAL 4 DAY);