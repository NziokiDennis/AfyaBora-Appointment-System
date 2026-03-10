-- Seed data for scheduling tables (for testing purposes)
-- Assumes doctors with user_id 5 and 7 exist and some patients exist

USE bilpham_outpatients_system;

-- clear previous test data if any (optional)
DELETE FROM doctor_schedules WHERE doctor_id IN (5,7);
DELETE FROM doctor_unavailability WHERE doctor_id IN (5,7);

-- weekly availability: doctor 5 works Mon-Fri 08:00-17:00
INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time) VALUES
(5, 1, '08:00:00', '17:00:00'),
(5, 2, '08:00:00', '17:00:00'),
(5, 3, '08:00:00', '17:00:00'),
(5, 4, '08:00:00', '17:00:00'),
(5, 5, '08:00:00', '17:00:00');

-- doctor 7 works Tue-Sat 10:00-18:00
INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time) VALUES
(7, 2, '10:00:00', '18:00:00'),
(7, 3, '10:00:00', '18:00:00'),
(7, 4, '10:00:00', '18:00:00'),
(7, 5, '10:00:00', '18:00:00'),
(7, 6, '10:00:00', '18:00:00');

-- temporary unavailability slots
INSERT INTO doctor_unavailability (doctor_id, date, start_time, end_time, reason) VALUES
(5, DATE_ADD(CURDATE(), INTERVAL 5 DAY), '00:00:00', '23:59:59', 'Conference'),
(7, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '13:00:00', '15:00:00', 'Personal errand');

-- create a couple of appointments to test conflicts (patient_ids 1 and 2)
-- appointment for doctor 5 tomorrow at 09:00
INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, additional_notes, payment_status, payment_amount)
VALUES (1, 5, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', 'Routine Check-up', 'Seed data', 'paid', 500.00);

-- overlapping attempt: same slot later for doctor 5 should violate unique index if tried
-- stored separately for manual testing

-- appointment for doctor 7 three days from now at 11:00
INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, additional_notes, payment_status, payment_amount)
VALUES (2, 7, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '11:00:00', 'Follow-up', 'Seed data', 'pending', 500.00);

-- add an initial log entry for an appointment
INSERT INTO appointment_logs (appointment_id, changed_by, old_status, new_status, notes)
SELECT appointment_id, doctor_id, 'scheduled', 'scheduled', 'initial seed' FROM appointments WHERE appointment_id IN (LAST_INSERT_ID(), LAST_INSERT_ID());
