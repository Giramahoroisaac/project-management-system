-- Update users table to modify role enum
ALTER TABLE users MODIFY COLUMN role ENUM('user', 'super_admin', 'sub_admin') DEFAULT 'user';

-- Convert existing administrative roles to sub_admin
UPDATE users 
SET role = 'sub_admin' 
WHERE role IN ('user_manager', 'approver', 'reviewer');
