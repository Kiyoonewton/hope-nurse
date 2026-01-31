-- Fix User Passwords Migration
-- Run this script to update passwords to the correct hashes
--
-- Credentials after running this:
-- Admin: admin / admin123
-- Students: student1 / student123, student2 / student123

-- Update admin password (admin123)
UPDATE users
SET password = '$2y$12$K23K5BqYHyABD0/BX374Y.4bLMke64CcfYRiWrQrxpXX/3LLdFvRe'
WHERE username = 'admin';

-- Update student passwords (student123)
UPDATE users
SET password = '$2y$12$H8lc4W3s9LljzYk2/rDBbOXtA0q74hE/kHQ.UDLNX4UJJBPTleI7i'
WHERE username IN ('student1', 'student2');

-- Verify the updates
SELECT id, username, email, role, status FROM users;
