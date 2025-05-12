USE mart_kms;

-- Delete existing admin user if exists
DELETE FROM users WHERE username = 'admin';

-- Insert admin user with correct password hash (password: admin123)
INSERT INTO users (username, password, email, full_name, role) 
VALUES ('admin', '$2y$10$YourNewHashHere', 'admin@mart.com', 'System Administrator', 'admin'); 