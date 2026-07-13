-- Wipe all HMS table data (keeps structure).
-- DANGEROUS / irreversible.
--
-- Uses DELETE (not TRUNCATE) because MySQL error #1701 blocks TRUNCATE
-- on tables referenced by foreign keys.
--
-- Usage:
--   mysql -u root -p hms < db/wipe_all_data.sql
--
-- After wipe, re-seed before logging in:
--   mysql -u root -p hms < db/hms_seed_data.sql

SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM payments;
DELETE FROM invoice_items;
DELETE FROM invoices;
DELETE FROM reservation_transfers;
DELETE FROM reservations;
DELETE FROM guest_documents;
DELETE FROM guests;
DELETE FROM housekeeping_tasks;
DELETE FROM maintenance_requests;
DELETE FROM expenses;
DELETE FROM expense_categories;
DELETE FROM room_status_log;
DELETE FROM rooms;
DELETE FROM rate_plans;
DELETE FROM room_types;
DELETE FROM notifications;
DELETE FROM audit_logs;
DELETE FROM backup_logs;
DELETE FROM settings;
DELETE FROM role_permissions;
DELETE FROM staff;
DELETE FROM permissions;
DELETE FROM roles;

SET FOREIGN_KEY_CHECKS = 1;

-- Reset AUTO_INCREMENT counters
ALTER TABLE payments AUTO_INCREMENT = 1;
ALTER TABLE invoice_items AUTO_INCREMENT = 1;
ALTER TABLE invoices AUTO_INCREMENT = 1;
ALTER TABLE reservation_transfers AUTO_INCREMENT = 1;
ALTER TABLE reservations AUTO_INCREMENT = 1;
ALTER TABLE guest_documents AUTO_INCREMENT = 1;
ALTER TABLE guests AUTO_INCREMENT = 1;
ALTER TABLE housekeeping_tasks AUTO_INCREMENT = 1;
ALTER TABLE maintenance_requests AUTO_INCREMENT = 1;
ALTER TABLE expenses AUTO_INCREMENT = 1;
ALTER TABLE expense_categories AUTO_INCREMENT = 1;
ALTER TABLE room_status_log AUTO_INCREMENT = 1;
ALTER TABLE rooms AUTO_INCREMENT = 1;
ALTER TABLE rate_plans AUTO_INCREMENT = 1;
ALTER TABLE room_types AUTO_INCREMENT = 1;
ALTER TABLE notifications AUTO_INCREMENT = 1;
ALTER TABLE audit_logs AUTO_INCREMENT = 1;
ALTER TABLE backup_logs AUTO_INCREMENT = 1;
ALTER TABLE staff AUTO_INCREMENT = 1;
ALTER TABLE permissions AUTO_INCREMENT = 1;
ALTER TABLE roles AUTO_INCREMENT = 1;
