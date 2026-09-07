-- ============================================
-- Eventrify - Seed Data
-- Run after database.sql
-- ============================================

USE eventrify;

-- Admin account (password: admin123)
INSERT INTO users (name, email, password, role) VALUES
('System Admin', 'admin@eventrify.com', '$2y$10$bVy6GQ5QsPpPYQkDgAFO4uu9R8Awq7EAG8vMlFI.W9uNWhtklFuL.', 'admin');

-- Club admin (password: club123)
INSERT INTO users (name, email, password, role) VALUES
('Club Manager', 'club@eventrify.com', '$2y$10$a2Hcu7jIuJXck7XQMIILqO4FqRrqjA6aFHXvr7NBPeTeX1p7Z.CwS', 'club_admin');

-- Regular users (password: user123)
INSERT INTO users (name, email, password, role) VALUES
('John Doe', 'john@example.com', '$2y$10$Wr2C/bqOLU0oArirrf7Y.ecMYvTldVTBHFp2xOPp8mVhO/.zlI1XC', 'user'),
('Jane Smith', 'jane@example.com', '$2y$10$Wr2C/bqOLU0oArirrf7Y.ecMYvTldVTBHFp2xOPp8mVhO/.zlI1XC', 'user'),
('Alex Rahman', 'alex@example.com', '$2y$10$Wr2C/bqOLU0oArirrf7Y.ecMYvTldVTBHFp2xOPp8mVhO/.zlI1XC', 'user');

-- Sample events
INSERT INTO events (title, description, venue, event_date, capacity, status, created_by) VALUES
('Annual Tech Fest 2026', 'A day of workshops, hackathons and tech talks for all students.', 'Campus Auditorium', '2026-10-15 10:00:00', 200, 'upcoming', 2),
('Cultural Night', 'Celebrate diversity with music, dance and food from around the world.', 'Main Campus Hall', '2026-11-05 18:00:00', 150, 'upcoming', 2),
('Algorithm Contest', 'Competitive programming contest with exciting prizes.', 'Computer Lab 2', '2026-09-20 09:00:00', 80, 'upcoming', 2),
('Orientation 2026', 'Welcome event for new students joining this semester.', 'University Auditorium', '2026-08-01 09:00:00', 300, 'completed', 2);

-- Sample registrations
INSERT INTO registrations (event_id, user_id) VALUES
(1, 3),
(1, 4),
(2, 5),
(3, 4),
(3, 5);

-- Sample tasks
INSERT INTO tasks (title, event_id, assigned_to, due_date, priority, status) VALUES
('Book the auditorium', 1, 2, '2026-10-01 12:00:00', 'high', 'done'),
('Prepare speaker invitations', 1, NULL, '2026-10-05 12:00:00', 'medium', 'in_progress'),
('Arrange refreshments', 2, 4, '2026-11-01 15:00:00', 'low', 'pending'),
('Design posters', 2, NULL, '2026-10-20 12:00:00', 'medium', 'pending');