-- Planned stay times on reservations; checkout policy is noon by default.
-- Safe to skip ADD COLUMN if already applied.

ALTER TABLE reservations
  ADD COLUMN check_in_time TIME NOT NULL DEFAULT '14:00:00' AFTER check_out_date,
  ADD COLUMN check_out_time TIME NOT NULL DEFAULT '12:00:00' AFTER check_in_time;

UPDATE settings SET `value` = '12:00' WHERE `key` = 'check_out_time';
