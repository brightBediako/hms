-- ============================================================
-- HMS Seed Data — Roles, Permissions, and Default Categories
-- Run this once after hms_schema.sql, before first login.
-- ============================================================

USE hms;

-- ----------------------------------------
-- Roles (from PRD "Target Users")
-- ----------------------------------------
INSERT INTO roles (name, description) VALUES
  ('Owner',                'Full access, including financial reports and system settings'),
  ('Manager',              'Full operational access, excludes system settings/backup'),
  ('Receptionist',         'Reservations, Front Desk, Guests, Billing (create), Payments'),
  ('Accountant',           'Billing, Payments, Expenses, Reports (read)'),
  ('Housekeeping Staff',   'Housekeeping tasks and room status updates only'),
  ('Maintenance Staff',    'Maintenance module only'),
  ('System Administrator', 'Staff & Roles, System Settings, Backup & Restore, Audit Logs');

-- ----------------------------------------
-- Permissions (module.action pattern)
-- ----------------------------------------
INSERT INTO permissions (`key`, description) VALUES
  ('dashboard.view',            'View dashboard'),
  ('reservations.view',         'View reservations'),
  ('reservations.create',       'Create reservations'),
  ('reservations.edit',         'Modify reservations'),
  ('reservations.cancel',       'Cancel reservations'),
  ('frontdesk.checkin',         'Perform check-in'),
  ('frontdesk.checkout',        'Perform check-out'),
  ('frontdesk.transfer',        'Transfer guest between rooms'),
  ('rooms.view',                'View rooms'),
  ('rooms.manage',              'Create/edit rooms and room types'),
  ('guests.view',                'View guest profiles'),
  ('guests.manage',             'Create/edit guest profiles'),
  ('billing.view',              'View invoices'),
  ('billing.create',            'Generate invoices'),
  ('billing.void',              'Void invoices'),
  ('payments.record',           'Record payments'),
  ('housekeeping.view',         'View housekeeping tasks'),
  ('housekeeping.manage',       'Assign/update housekeeping tasks'),
  ('maintenance.view',          'View maintenance requests'),
  ('maintenance.manage',        'Create/assign/resolve maintenance requests'),
  ('expenses.view',             'View expenses'),
  ('expenses.manage',           'Record expenses'),
  ('staff.manage',              'Manage staff accounts and roles'),
  ('reports.view',              'View reports and analytics'),
  ('audit.view',                'View audit logs'),
  ('settings.manage',           'Manage system settings'),
  ('backup.manage',             'Trigger and manage backups');

-- ----------------------------------------
-- Role -> Permission mapping
-- Adjust freely; this reflects the PRD's default access model.
-- ----------------------------------------

-- Owner: everything
INSERT INTO role_permissions (role_id, permission_id)
SELECT (SELECT id FROM roles WHERE name = 'Owner'), id FROM permissions;

-- Manager: everything except settings/backup/staff role management
INSERT INTO role_permissions (role_id, permission_id)
SELECT (SELECT id FROM roles WHERE name = 'Manager'), id FROM permissions
WHERE `key` NOT IN ('settings.manage', 'backup.manage', 'staff.manage');

-- Receptionist
INSERT INTO role_permissions (role_id, permission_id)
SELECT (SELECT id FROM roles WHERE name = 'Receptionist'), id FROM permissions
WHERE `key` IN (
  'dashboard.view','reservations.view','reservations.create','reservations.edit','reservations.cancel',
  'frontdesk.checkin','frontdesk.checkout','frontdesk.transfer',
  'rooms.view','guests.view','guests.manage',
  'billing.view','billing.create','payments.record'
);

-- Accountant
INSERT INTO role_permissions (role_id, permission_id)
SELECT (SELECT id FROM roles WHERE name = 'Accountant'), id FROM permissions
WHERE `key` IN (
  'dashboard.view','billing.view','billing.create','billing.void',
  'payments.record','expenses.view','expenses.manage','reports.view'
);

-- Housekeeping Staff
INSERT INTO role_permissions (role_id, permission_id)
SELECT (SELECT id FROM roles WHERE name = 'Housekeeping Staff'), id FROM permissions
WHERE `key` IN ('housekeeping.view','housekeeping.manage','rooms.view');

-- Maintenance Staff
INSERT INTO role_permissions (role_id, permission_id)
SELECT (SELECT id FROM roles WHERE name = 'Maintenance Staff'), id FROM permissions
WHERE `key` IN ('maintenance.view','maintenance.manage','rooms.view');

-- System Administrator
INSERT INTO role_permissions (role_id, permission_id)
SELECT (SELECT id FROM roles WHERE name = 'System Administrator'), id FROM permissions
WHERE `key` IN ('staff.manage','settings.manage','backup.manage','audit.view','dashboard.view');

-- ----------------------------------------
-- Default expense categories
-- ----------------------------------------
INSERT INTO expense_categories (name) VALUES
  ('Utilities'), ('Repairs'), ('Purchases'), ('Operational');

-- ----------------------------------------
-- Default system settings (placeholders — adjust for your hotel)
-- ----------------------------------------
INSERT INTO settings (`key`, `value`) VALUES
  ('hotel_name',        'My Hotel'),
  ('currency',           'GHS'),
  ('default_tax_rate',   '0.00'),
  ('check_in_time',      '14:00'),
  ('check_out_time',     '11:00');

-- ----------------------------------------
-- First System Administrator account
-- Demo login (local only): admin@example.com / Admin@123
-- Replace this hash before any production deploy.
-- ----------------------------------------
INSERT INTO staff (role_id, full_name, email, password_hash, status)
VALUES (
  (SELECT id FROM roles WHERE name = 'System Administrator'),
  'System Administrator',
  'admin@example.com',
  '$2y$10$MS4cQQZvdqzYJDZtPt91z.DppfhMJa/yUlSeLRyPnzjT95imQcuge',
  'active'
);
