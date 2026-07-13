-- Planned stay times on reservations; checkout policy is noon by default.
-- Idempotent: safe to re-run if columns already exist.

SET @schema := DATABASE();

SET @has_check_in_time := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @schema
    AND TABLE_NAME = 'reservations'
    AND COLUMN_NAME = 'check_in_time'
);

SET @sql_check_in := IF(
  @has_check_in_time = 0,
  'ALTER TABLE reservations ADD COLUMN check_in_time TIME NOT NULL DEFAULT ''14:00:00'' AFTER check_out_date',
  'SELECT ''check_in_time already exists'' AS migration_info'
);
PREPARE stmt FROM @sql_check_in;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_check_out_time := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @schema
    AND TABLE_NAME = 'reservations'
    AND COLUMN_NAME = 'check_out_time'
);

SET @sql_check_out := IF(
  @has_check_out_time = 0,
  'ALTER TABLE reservations ADD COLUMN check_out_time TIME NOT NULL DEFAULT ''12:00:00'' AFTER check_in_time',
  'SELECT ''check_out_time already exists'' AS migration_info'
);
PREPARE stmt FROM @sql_check_out;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE settings SET `value` = '12:00' WHERE `key` = 'check_out_time';
