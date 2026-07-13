-- Split default tax into GETF / NHIL / VAT and note check-in is booking time.
-- Idempotent: upserts settings keys.

INSERT INTO settings (`key`, `value`) VALUES
  ('tax_getf_rate', '0.0250'),
  ('tax_nhil_rate', '0.0250'),
  ('tax_vat_rate',  '0.1500')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- Keep check_out_time; check_in_time is no longer used for policy (booking creation time).
UPDATE settings SET `value` = '12:00' WHERE `key` = 'check_out_time';

-- Combined rate for any legacy readers of default_tax_rate (2.5 + 2.5 + 15 = 20%).
INSERT INTO settings (`key`, `value`) VALUES ('default_tax_rate', '0.2000')
ON DUPLICATE KEY UPDATE `value` = '0.2000';
