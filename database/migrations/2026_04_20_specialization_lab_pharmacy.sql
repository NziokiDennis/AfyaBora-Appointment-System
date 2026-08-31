-- Doctor specialization, Lab results, and Pharmacy dispensing modules

USE bilpham_outpatients_system;

ALTER TABLE users
  ADD COLUMN specialization VARCHAR(100) NULL AFTER role;

CREATE TABLE IF NOT EXISTS lab_results (
  lab_result_id INT AUTO_INCREMENT PRIMARY KEY,
  appointment_id INT NOT NULL,
  test_name VARCHAR(150) NOT NULL,
  result_value VARCHAR(255) NOT NULL,
  normal_range VARCHAR(100) DEFAULT NULL,
  notes TEXT,
  recorded_by INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE CASCADE,
  FOREIGN KEY (recorded_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pharmacy_dispenses (
  dispense_id INT AUTO_INCREMENT PRIMARY KEY,
  appointment_id INT NOT NULL,
  medication_name VARCHAR(150) NOT NULL,
  dosage VARCHAR(100) DEFAULT NULL,
  quantity VARCHAR(50) DEFAULT NULL,
  dispensed_by INT DEFAULT NULL,
  dispensed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  notes TEXT,
  FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE CASCADE,
  FOREIGN KEY (dispensed_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
