-- Migration script: 2026‑03‑10 – Add scheduling support and audit columns
-- Run this against the existing database; it is written to be safe on a live schema.

-- 1. clean up any existing duplicate appointments so the unique index can be added
DELETE a1
FROM appointments a1
JOIN appointments a2
  ON a1.doctor_id = a2.doctor_id
 AND a1.appointment_date = a2.appointment_date
 AND a1.appointment_time = a2.appointment_time
 AND a1.appointment_id > a2.appointment_id;

-- 2. alter appointments table
-- the `IF NOT EXISTS` clauses make this block safe to re‑execute (MySQL 8+)
ALTER TABLE appointments
  -- basic duration field, default 30 minutes (legacy code will keep working)
  ADD COLUMN IF NOT EXISTS appointment_duration INT NOT NULL DEFAULT 30 COMMENT 'minutes',
  -- new columns for tracking changes; existing rows will simply have NULL
  ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL AFTER created_at,
  ADD COLUMN IF NOT EXISTS updated_by INT NULL AFTER updated_at,
  -- index on the user who updated the row (may already exist)
  ADD KEY IF NOT EXISTS ix_appointments_updated_by (updated_by),
  -- expand status enumeration; existing values are preserved
  MODIFY COLUMN status ENUM('scheduled','completed','canceled','no_show','rescheduled')
        NOT NULL DEFAULT 'scheduled',
  -- prevent two patients booking the same doctor at the same slot
  ADD UNIQUE KEY IF NOT EXISTS ux_doc_datetime (doctor_id, appointment_date, appointment_time);

-- foreign key for updated_by (allows NULL when user deleted)
-- drop existing constraint if present so migration can be re-run
DELIMITER //
BEGIN
  DECLARE cnt INT DEFAULT 0;
  SELECT COUNT(*) INTO cnt
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'appointments'
    AND CONSTRAINT_NAME = 'fk_appointments_updated_by';
  IF cnt > 0 THEN
      ALTER TABLE appointments DROP FOREIGN KEY fk_appointments_updated_by;
  END IF;
  ALTER TABLE appointments
      ADD CONSTRAINT fk_appointments_updated_by
          FOREIGN KEY (updated_by) REFERENCES users(user_id)
          ON DELETE SET NULL;
END;//
DELIMITER ;

-- 3. schedule tables for doctors
CREATE TABLE IF NOT EXISTS doctor_schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id   INT NOT NULL,
    day_of_week TINYINT NOT NULL COMMENT '0=Sunday...6=Saturday',
    start_time  TIME NOT NULL,
    end_time    TIME NOT NULL,
    CONSTRAINT fk_ds_doctor FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY ux_doctor_day (doctor_id, day_of_week, start_time, end_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS doctor_unavailability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    date DATE NOT NULL,
    start_time TIME NOT NULL DEFAULT '00:00:00',
    end_time   TIME NOT NULL DEFAULT '23:59:59',
    reason VARCHAR(255) DEFAULT NULL,
    CONSTRAINT fk_du_doctor FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY ux_doctor_unavail (doctor_id, date, start_time, end_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. optional audit log for status changes (doesn't affect existing application code)
CREATE TABLE IF NOT EXISTS appointment_logs (
    log_id        INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    changed_by     INT NULL,
    change_time    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    old_status     ENUM('scheduled','completed','canceled','no_show','rescheduled') NULL,
    new_status     ENUM('scheduled','completed','canceled','no_show','rescheduled') NOT NULL,
    notes          TEXT DEFAULT NULL,
    CONSTRAINT fk_al_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE CASCADE,
    CONSTRAINT fk_al_user        FOREIGN KEY (changed_by)     REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- end of migration
