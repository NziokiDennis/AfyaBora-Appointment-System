-- Receptionist role, three-stage payment workflow, and test receptionist account

USE bilpham_outpatients_system;

ALTER TABLE users
  MODIFY COLUMN role ENUM('admin','doctor','patient','receptionist') NOT NULL;

ALTER TABLE appointments
  MODIFY COLUMN payment_status ENUM('unpaid','pending','paid') DEFAULT 'unpaid';

UPDATE appointments
SET payment_status = 'unpaid'
WHERE payment_status IS NULL;

UPDATE appointments
SET payment_status = 'unpaid'
WHERE payment_status = 'pending'
  AND (payment_reference IS NULL OR payment_reference = '');

UPDATE appointments
SET payment_status = 'paid'
WHERE status = 'completed';

INSERT INTO users (full_name, email, password_hash, phone_number, role)
SELECT 'Test Receptionist', 'receptionist@afyabora.test', '$2y$12$mqkQf8zwzhB7hYFzoSO51u9TFVg45hT.sXCyKMjC9C06kSs1C6c92', '0712345678', 'receptionist'
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'receptionist@afyabora.test'
);
