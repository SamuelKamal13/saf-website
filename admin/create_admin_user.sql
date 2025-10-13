-- Create Admin User for Admin Panel
-- Run this SQL script in your database to create an admin account

-- Method 1: Using MD5 (Legacy support)
-- Password will be: admin123
INSERT INTO users (name, email, password, role, barcode_id, phone, is_active, created_at) 
VALUES (
    'مدير النظام',                    -- Admin name in Arabic
    'admin@church.com',               -- Email (change this)
    MD5('admin123'),                  -- Password: admin123 (change this!)
    'admin',                          -- Role: admin
    'ADMIN_001',                      -- Barcode ID
    '01012345678',                    -- Phone (optional)
    1,                                -- Active
    NOW()                             -- Created timestamp
);

-- OR Method 2: Using bcrypt (More secure - recommended)
-- Password will be: admin123
-- You need to generate bcrypt hash using PHP or online tool
-- Example hash for "admin123": $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

-- INSERT INTO users (name, email, password, role, barcode_id, phone, is_active, created_at) 
-- VALUES (
--     'مدير النظام',
--     'admin@church.com',
--     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- bcrypt hash
--     'admin',
--     'ADMIN_001',
--     '01012345678',
--     1,
--     NOW()
-- );

-- Verify the user was created
SELECT id, name, email, role, is_active FROM users WHERE email = 'admin@church.com';

-- Optional: Create additional admin/servant accounts
INSERT INTO users (name, email, password, role, barcode_id, phone, is_active, created_at) 
VALUES 
    ('خادم الكنيسة', 'servant@church.com', MD5('servant123'), 'servant', 'SERVANT_001', '01012345679', 1, NOW()),
    ('عضو عادي', 'member@church.com', MD5('member123'), 'member', 'MEMBER_001', '01012345680', 1, NOW());

-- Check all users
SELECT id, name, email, role, barcode_id, is_active 
FROM users 
ORDER BY created_at DESC;

-- If you need to update an existing user to admin
UPDATE users 
SET role = 'admin' 
WHERE email = 'your-existing-email@example.com';

-- If you need to reset a password
UPDATE users 
SET password = MD5('new-password') 
WHERE email = 'admin@church.com';

-- IMPORTANT NOTES:
-- 1. Change 'admin@church.com' to your desired email
-- 2. Change 'admin123' to a strong password
-- 3. For production, use bcrypt instead of MD5
-- 4. Keep these credentials secure!

-- To generate bcrypt hash in PHP:
-- <?php echo password_hash('your-password', PASSWORD_BCRYPT); ?>
