-- ESCR DQMS Admin Account Manager
-- 
-- Instructions:
-- 1. Open phpMyAdmin (http://localhost/phpmyadmin)
-- 2. Select the "escr_dqms" database
-- 3. Click on "SQL" tab
-- 4. Copy and paste this script
-- 5. Click "Go" to execute

-- Check if admin exists
SELECT id, username, email, role FROM users WHERE username = 'admin';

-- If admin doesn't exist, uncomment and run:
-- INSERT INTO users (username, email, password, role) 
-- VALUES ('admin', 'admin@escr.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- To update existing user to admin role:
UPDATE users SET role = 'admin' WHERE username = 'admin';

-- To reset admin password to 'Admin@123':
UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'admin';

-- Verify the changes
SELECT id, username, email, role FROM users WHERE role = 'admin';
