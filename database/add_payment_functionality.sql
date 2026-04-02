-- SQL Script to align payment workflow with receptionist confirmation

-- Add payment fields to appointments table
ALTER TABLE `appointments` 
ADD COLUMN `payment_status` ENUM('unpaid', 'pending', 'paid') DEFAULT 'unpaid' AFTER `status`,
ADD COLUMN `payment_amount` DECIMAL(10, 2) DEFAULT 4500.00 AFTER `payment_status`,
ADD COLUMN `payment_date` TIMESTAMP NULL DEFAULT NULL AFTER `payment_amount`,
ADD COLUMN `payment_method` VARCHAR(50) DEFAULT NULL AFTER `payment_date`,
ADD COLUMN `payment_reference` VARCHAR(100) DEFAULT NULL AFTER `payment_method`;

-- Update existing appointments to have a clear three-stage flow:
-- unpaid  -> patient has not submitted payment
-- pending -> patient submitted payment, receptionist must confirm
-- paid    -> receptionist confirmed the payment
UPDATE `appointments` SET `payment_status` = 'unpaid' WHERE `payment_status` IS NULL;

-- If a payment reference exists, treat it as a submitted payment awaiting confirmation
UPDATE `appointments`
SET `payment_status` = 'pending'
WHERE `payment_status` = 'unpaid'
  AND `payment_reference` IS NOT NULL;

-- Optional: Set completed appointments as paid
UPDATE `appointments` SET `payment_status` = 'paid', `payment_date` = NOW() WHERE `status` = 'completed';
