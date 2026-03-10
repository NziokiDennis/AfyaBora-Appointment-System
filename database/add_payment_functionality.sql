-- SQL Script to add payment functionality to the appointment system

-- Add payment fields to appointments table
ALTER TABLE `appointments` 
ADD COLUMN `payment_status` ENUM('pending', 'paid') DEFAULT 'pending' AFTER `status`,
ADD COLUMN `payment_amount` DECIMAL(10, 2) DEFAULT 4500.00 AFTER `payment_status`,
ADD COLUMN `payment_date` TIMESTAMP NULL DEFAULT NULL AFTER `payment_amount`,
ADD COLUMN `payment_method` VARCHAR(50) DEFAULT NULL AFTER `payment_date`,
ADD COLUMN `payment_reference` VARCHAR(100) DEFAULT NULL AFTER `payment_method`;

-- Update existing appointments to have payment_status as 'pending'
UPDATE `appointments` SET `payment_status` = 'pending' WHERE `payment_status` IS NULL;

-- Optional: Set completed appointments as paid
UPDATE `appointments` SET `payment_status` = 'paid', `payment_date` = NOW() WHERE `status` = 'completed';
