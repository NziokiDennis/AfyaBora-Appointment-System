-- Next of kin details, captured at patient registration

USE bilpham_outpatients_system;

ALTER TABLE users
  ADD COLUMN next_of_kin_name VARCHAR(100) NULL AFTER address,
  ADD COLUMN next_of_kin_relationship VARCHAR(50) NULL AFTER next_of_kin_name,
  ADD COLUMN next_of_kin_phone VARCHAR(15) NULL AFTER next_of_kin_relationship;
