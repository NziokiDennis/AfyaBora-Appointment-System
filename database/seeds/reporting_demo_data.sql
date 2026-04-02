-- Reporting demo seed data
-- Purpose: populate enough realistic records for at least 5 reports to show actionable insights

USE bilpham_outpatients_system;

DELETE FROM medical_records WHERE notes LIKE 'Report seed %';
DELETE FROM feedback WHERE comments LIKE 'Report seed %';
DELETE FROM appointment_logs WHERE notes LIKE 'report-seed%';
DELETE FROM appointments WHERE additional_notes LIKE 'Report seed batch %';

INSERT INTO appointments
    (patient_id, doctor_id, appointment_date, appointment_time, status, payment_status, payment_amount, payment_date, payment_method, payment_reference, created_at, updated_at, updated_by, reason, additional_notes, appointment_duration)
VALUES
    (1, 5,  '2026-01-05', '08:00:00', 'completed', 'paid',    500.00, '2026-01-04 09:12:00', 'M-Pesa',       'RPTA001', '2026-01-01 08:00:00', '2026-01-04 09:12:00', 1,  'Routine Check-up',  'Report seed batch A01', 30),
    (2, 7,  '2026-01-07', '10:00:00', 'completed', 'paid',    500.00, '2026-01-06 10:20:00', 'M-Pesa',       'RPTA002', '2026-01-02 08:15:00', '2026-01-06 10:20:00', 1,  'Follow-up',         'Report seed batch A02', 30),
    (3, 19, '2026-01-10', '09:30:00', 'completed', 'paid',    500.00, '2026-01-09 14:40:00', 'Credit Card',  'RPTA003', '2026-01-03 09:30:00', '2026-01-09 14:40:00', 1,  'New Symptoms',      'Report seed batch A03', 30),
    (17,20, '2026-01-12', '11:00:00', 'canceled',  'unpaid',  500.00, NULL,                  NULL,           NULL,      '2026-01-04 10:00:00', '2026-01-11 18:00:00', 1,  'Routine Check-up',  'Report seed batch A04', 30),
    (18,5,  '2026-01-15', '13:00:00', 'completed', 'paid',    500.00, '2026-01-14 16:25:00', 'Bank Transfer','RPTA005', '2026-01-05 11:00:00', '2026-01-14 16:25:00', 1,  'Chronic Condition', 'Report seed batch A05', 30),
    (1, 7,  '2026-01-20', '14:00:00', 'no_show',   'pending', 500.00, NULL,                  'M-Pesa',       'RPTA006', '2026-01-06 12:10:00', '2026-01-19 09:00:00', 1,  'Follow-up',         'Report seed batch A06', 30),
    (2, 19, '2026-01-22', '09:00:00', 'completed', 'paid',    500.00, '2026-01-21 17:30:00', 'M-Pesa',       'RPTA007', '2026-01-07 13:10:00', '2026-01-21 17:30:00', 1,  'Routine Check-up',  'Report seed batch A07', 30),
    (3, 20, '2026-01-28', '15:30:00', 'scheduled', 'unpaid',  500.00, NULL,                  NULL,           NULL,      '2026-01-10 14:00:00', NULL,                  NULL,'Other',             'Report seed batch A08', 30),

    (17,5,  '2026-02-03', '08:30:00', 'completed', 'paid',    500.00, '2026-02-02 09:10:00', 'M-Pesa',       'RPTA009', '2026-02-01 08:30:00', '2026-02-02 09:10:00', 1,  'Follow-up',         'Report seed batch A09', 30),
    (18,7,  '2026-02-05', '12:00:00', 'completed', 'paid',    500.00, '2026-02-04 11:15:00', 'M-Pesa',       'RPTA010', '2026-02-01 09:20:00', '2026-02-04 11:15:00', 1,  'New Symptoms',      'Report seed batch A10', 30),
    (1, 19, '2026-02-08', '10:30:00', 'canceled',  'unpaid',  500.00, NULL,                  NULL,           NULL,      '2026-02-02 10:00:00', '2026-02-07 18:40:00', 1,  'Routine Check-up',  'Report seed batch A11', 30),
    (2, 20, '2026-02-11', '11:30:00', 'completed', 'paid',    500.00, '2026-02-10 15:00:00', 'Bank Transfer','RPTA012', '2026-02-03 11:40:00', '2026-02-10 15:00:00', 1,  'Chronic Condition', 'Report seed batch A12', 30),
    (3, 5,  '2026-02-14', '09:00:00', 'scheduled', 'pending', 500.00, NULL,                  'M-Pesa',       'RPTA013', '2026-02-04 12:00:00', NULL,                  NULL,'Routine Check-up',  'Report seed batch A13', 30),
    (17,7,  '2026-02-17', '10:00:00', 'completed', 'paid',    500.00, '2026-02-16 08:45:00', 'M-Pesa',       'RPTA014', '2026-02-05 13:10:00', '2026-02-16 08:45:00', 1,  'Other',             'Report seed batch A14', 30),
    (18,19, '2026-02-21', '14:30:00', 'completed', 'paid',    500.00, '2026-02-20 16:50:00', 'Credit Card',  'RPTA015', '2026-02-06 14:30:00', '2026-02-20 16:50:00', 1,  'Follow-up',         'Report seed batch A15', 30),
    (1, 20, '2026-02-26', '16:00:00', 'scheduled', 'unpaid',  500.00, NULL,                  NULL,           NULL,      '2026-02-08 15:20:00', NULL,                  NULL,'New Symptoms',      'Report seed batch A16', 30),

    (2, 5,  '2026-03-02', '08:00:00', 'completed', 'paid',    500.00, '2026-03-01 09:30:00', 'M-Pesa',       'RPTA017', '2026-03-01 08:00:00', '2026-03-01 09:30:00', 1,  'Routine Check-up',  'Report seed batch A17', 30),
    (3, 7,  '2026-03-05', '13:30:00', 'completed', 'paid',    500.00, '2026-03-04 11:00:00', 'M-Pesa',       'RPTA018', '2026-03-01 09:10:00', '2026-03-04 11:00:00', 1,  'Chronic Condition', 'Report seed batch A18', 30),
    (17,19, '2026-03-09', '09:00:00', 'no_show',   'pending', 500.00, NULL,                  'M-Pesa',       'RPTA019', '2026-03-02 10:00:00', NULL,                  NULL,'Follow-up',         'Report seed batch A19', 30),
    (18,20, '2026-03-12', '10:00:00', 'completed', 'paid',    500.00, '2026-03-11 14:10:00', 'Bank Transfer','RPTA020', '2026-03-03 11:30:00', '2026-03-11 14:10:00', 1,  'Routine Check-up',  'Report seed batch A20', 30),
    (1, 5,  '2026-03-16', '11:00:00', 'scheduled', 'pending', 500.00, NULL,                  'M-Pesa',       'RPTA021', '2026-03-05 12:40:00', NULL,                  NULL,'New Symptoms',      'Report seed batch A21', 30),
    (2, 7,  '2026-03-19', '15:00:00', 'canceled',  'unpaid',  500.00, NULL,                  NULL,           NULL,      '2026-03-06 09:00:00', '2026-03-18 18:20:00', 1,  'Other',             'Report seed batch A22', 30),
    (3, 19, '2026-03-23', '12:00:00', 'completed', 'paid',    500.00, '2026-03-22 16:35:00', 'M-Pesa',       'RPTA023', '2026-03-07 10:15:00', '2026-03-22 16:35:00', 1,  'Follow-up',         'Report seed batch A23', 30),
    (17,20, '2026-03-27', '09:30:00', 'scheduled', 'unpaid',  500.00, NULL,                  NULL,           NULL,      '2026-03-08 13:25:00', NULL,                  NULL,'Routine Check-up',  'Report seed batch A24', 30);

INSERT INTO appointment_logs (appointment_id, changed_by, old_status, new_status, notes)
SELECT appointment_id, doctor_id, 'scheduled', status, CONCAT('report-seed-', appointment_id)
FROM appointments
WHERE additional_notes LIKE 'Report seed batch %';

INSERT INTO medical_records (appointment_id, diagnosis, prescription, notes, created_at)
SELECT appointment_id,
       CASE MOD(appointment_id, 5)
           WHEN 0 THEN 'Seasonal Flu'
           WHEN 1 THEN 'Hypertension'
           WHEN 2 THEN 'Migraine'
           WHEN 3 THEN 'Upper Respiratory Tract Infection'
           ELSE 'Gastritis'
       END,
       CASE MOD(appointment_id, 4)
           WHEN 0 THEN 'Paracetamol and hydration'
           WHEN 1 THEN 'Lifestyle review and BP medication'
           WHEN 2 THEN 'Pain management and rest'
           ELSE 'Antacids and diet guidance'
       END,
       CONCAT('Report seed medical note for appointment ', appointment_id),
       DATE_ADD(created_at, INTERVAL 2 HOUR)
FROM appointments
WHERE additional_notes LIKE 'Report seed batch %'
  AND status = 'completed';

INSERT INTO feedback (patient_id, doctor_id, rating, comments, created_at) VALUES
    (1, 5,  5, 'Report seed excellent follow-up communication.', '2026-01-06 10:00:00'),
    (2, 7,  4, 'Report seed clear explanation of treatment.',     '2026-01-08 12:00:00'),
    (3, 19, 4, 'Report seed consultation was professional.',      '2026-01-11 13:00:00'),
    (17,20, 3, 'Report seed queue was a bit long.',               '2026-01-13 16:00:00'),
    (18,5,  5, 'Report seed doctor was very attentive.',          '2026-01-16 09:00:00'),
    (1, 7,  3, 'Report seed good service overall.',               '2026-01-21 11:30:00'),
    (2, 19, 4, 'Report seed helpful medication guidance.',        '2026-01-23 15:00:00'),
    (3, 20, 2, 'Report seed rescheduling took too long.',         '2026-01-29 10:00:00'),
    (17,5,  5, 'Report seed fast and smooth visit.',              '2026-02-04 08:15:00'),
    (18,7,  4, 'Report seed doctor answered all my questions.',   '2026-02-06 14:20:00'),
    (1, 19, 3, 'Report seed average waiting time.',               '2026-02-09 12:10:00'),
    (2, 20, 5, 'Report seed excellent care and friendliness.',    '2026-02-12 16:45:00'),
    (3, 5,  4, 'Report seed polite and efficient service.',       '2026-02-15 09:30:00'),
    (17,7,  5, 'Report seed felt well cared for.',                '2026-02-18 13:00:00'),
    (18,19, 4, 'Report seed clinic instructions were clear.',     '2026-02-22 10:40:00'),
    (1, 20, 3, 'Report seed payment process was okay.',           '2026-02-27 15:10:00'),
    (2, 5,  5, 'Report seed quick diagnosis and treatment.',      '2026-03-03 08:40:00'),
    (3, 7,  4, 'Report seed very professional interaction.',      '2026-03-06 11:25:00'),
    (17,19, 2, 'Report seed missed appointment reminders.',       '2026-03-10 17:00:00'),
    (18,20, 5, 'Report seed receptionist and doctor were great.', '2026-03-13 09:50:00');
