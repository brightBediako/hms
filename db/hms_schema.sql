-- ============================================================
-- Hotel Management System (HMS) — MySQL Schema
-- Version 1.0
-- Charset: utf8mb4 | Engine: InnoDB (transactional, FK support)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS hms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hms;

-- ============================================================
-- 1. ROLES & PERMISSIONS (RBAC)
-- ============================================================

CREATE TABLE roles (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(50)  NOT NULL UNIQUE,
  description   VARCHAR(255) NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE permissions (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key`         VARCHAR(100) NOT NULL UNIQUE,   -- e.g. 'reservations.create'
  description   VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE role_permissions (
  role_id        INT UNSIGNED NOT NULL,
  permission_id  INT UNSIGNED NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 2. STAFF (system users)
-- ============================================================

CREATE TABLE staff (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role_id        INT UNSIGNED NOT NULL,
  full_name      VARCHAR(100) NOT NULL,
  email          VARCHAR(150) NOT NULL UNIQUE,
  phone          VARCHAR(30)  NULL,
  password_hash  VARCHAR(255) NOT NULL,
  status         ENUM('active','suspended') NOT NULL DEFAULT 'active',
  last_login_at  TIMESTAMP NULL,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

-- ============================================================
-- 3. GUESTS
-- ============================================================

CREATE TABLE guests (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name    VARCHAR(150) NOT NULL,
  email        VARCHAR(150) NULL,
  phone        VARCHAR(30)  NULL,
  id_type      ENUM('passport','national_id','drivers_license','other') NULL,
  id_number    VARCHAR(100) NULL,
  nationality  VARCHAR(80)  NULL,
  address      VARCHAR(255) NULL,
  notes        TEXT NULL,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_guest_name  (full_name),
  INDEX idx_guest_phone (phone)
) ENGINE=InnoDB;

CREATE TABLE guest_documents (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  guest_id       INT UNSIGNED NOT NULL,
  file_path      VARCHAR(255) NOT NULL,
  document_type  VARCHAR(50)  NULL,     -- e.g. 'id_scan', 'signature'
  uploaded_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 4. ROOM TYPES, RATE PLANS & ROOMS
-- ============================================================

CREATE TABLE room_types (
  id                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name                   VARCHAR(80) NOT NULL,      -- e.g. 'Deluxe', 'Standard'
  description            TEXT NULL,
  base_capacity_adults   TINYINT UNSIGNED NOT NULL DEFAULT 2,
  base_capacity_children TINYINT UNSIGNED NOT NULL DEFAULT 0,
  base_rate              DECIMAL(10,2) NOT NULL,    -- default nightly rate
  extra_bed_rate         DECIMAL(10,2) NULL,
  amenities              TEXT NULL,                 -- JSON array or comma list
  created_at             TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at             TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE rate_plans (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  room_type_id  INT UNSIGNED NOT NULL,
  name          VARCHAR(80) NOT NULL,    -- e.g. 'Weekend Rate', 'Long Stay Discount'
  rate          DECIMAL(10,2) NOT NULL,
  start_date    DATE NULL,
  end_date      DATE NULL,
  is_active     TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE rooms (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  room_type_id  INT UNSIGNED NOT NULL,
  room_number   VARCHAR(20) NOT NULL UNIQUE,
  floor         VARCHAR(20) NULL,
  status        ENUM('available','occupied','reserved','cleaning','maintenance') NOT NULL DEFAULT 'available',
  notes         VARCHAR(255) NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (room_type_id) REFERENCES room_types(id),
  INDEX idx_room_status (status)
) ENGINE=InnoDB;

-- History of room status changes (available -> occupied -> cleaning, etc.)
CREATE TABLE room_status_log (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  room_id      INT UNSIGNED NOT NULL,
  old_status   ENUM('available','occupied','reserved','cleaning','maintenance') NULL,
  new_status   ENUM('available','occupied','reserved','cleaning','maintenance') NOT NULL,
  changed_by   INT UNSIGNED NULL,
  reason       VARCHAR(255) NULL,
  changed_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
  FOREIGN KEY (changed_by) REFERENCES staff(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 5. RESERVATIONS
-- ============================================================

CREATE TABLE reservations (
  id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_reference    VARCHAR(20) NOT NULL UNIQUE,   -- human-readable code, e.g. HMS-2026-000123
  guest_id             INT UNSIGNED NOT NULL,
  room_id              INT UNSIGNED NOT NULL,
  booked_by            INT UNSIGNED NULL,             -- staff who created the booking
  source               ENUM('walk_in','phone','advance','other') NOT NULL DEFAULT 'walk_in',
  check_in_date        DATE NOT NULL,
  check_out_date       DATE NOT NULL,
  actual_check_in      TIMESTAMP NULL,
  actual_check_out     TIMESTAMP NULL,
  adults               TINYINT UNSIGNED NOT NULL DEFAULT 1,
  children             TINYINT UNSIGNED NOT NULL DEFAULT 0,
  agreed_rate          DECIMAL(10,2) NOT NULL,        -- nightly rate locked in at booking time
  status               ENUM('booked','checked_in','checked_out','cancelled','no_show') NOT NULL DEFAULT 'booked',
  cancellation_reason  VARCHAR(255) NULL,
  notes                TEXT NULL,
  created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (guest_id) REFERENCES guests(id),
  FOREIGN KEY (room_id) REFERENCES rooms(id),
  FOREIGN KEY (booked_by) REFERENCES staff(id) ON DELETE SET NULL,
  INDEX idx_res_dates  (check_in_date, check_out_date),
  INDEX idx_res_status (status),
  INDEX idx_res_room   (room_id),
  CONSTRAINT chk_res_dates CHECK (check_out_date > check_in_date)
) ENGINE=InnoDB;

-- Room transfers during a stay (e.g. guest moved from Room 101 to Room 204)
CREATE TABLE reservation_transfers (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reservation_id   INT UNSIGNED NOT NULL,
  from_room_id     INT UNSIGNED NOT NULL,
  to_room_id       INT UNSIGNED NOT NULL,
  transferred_by   INT UNSIGNED NULL,
  reason           VARCHAR(255) NULL,
  transferred_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
  FOREIGN KEY (from_room_id) REFERENCES rooms(id),
  FOREIGN KEY (to_room_id) REFERENCES rooms(id),
  FOREIGN KEY (transferred_by) REFERENCES staff(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 6. BILLING & INVOICING
-- ============================================================

CREATE TABLE invoices (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_number   VARCHAR(30) NOT NULL UNIQUE,
  reservation_id   INT UNSIGNED NOT NULL,
  guest_id         INT UNSIGNED NOT NULL,
  subtotal         DECIMAL(10,2) NOT NULL DEFAULT 0,
  discount_amount  DECIMAL(10,2) NOT NULL DEFAULT 0,
  tax_amount       DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_amount     DECIMAL(10,2) NOT NULL DEFAULT 0,
  amount_paid      DECIMAL(10,2) NOT NULL DEFAULT 0,
  balance_due      DECIMAL(10,2) NOT NULL DEFAULT 0,
  status           ENUM('draft','issued','partially_paid','paid','void') NOT NULL DEFAULT 'draft',
  issued_by        INT UNSIGNED NULL,
  issued_at        TIMESTAMP NULL,
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (reservation_id) REFERENCES reservations(id),
  FOREIGN KEY (guest_id) REFERENCES guests(id),
  FOREIGN KEY (issued_by) REFERENCES staff(id) ON DELETE SET NULL,
  INDEX idx_invoice_status (status)
) ENGINE=InnoDB;

-- Line items: room charges, extra services, discounts, taxes.
-- source_module lets external systems (e.g. Restaurant Management System)
-- post charges onto a guest's folio without owning the invoice itself.
CREATE TABLE invoice_items (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_id     INT UNSIGNED NOT NULL,
  item_type      ENUM('room_charge','service','discount','tax','other') NOT NULL DEFAULT 'room_charge',
  description    VARCHAR(255) NOT NULL,
  quantity       DECIMAL(10,2) NOT NULL DEFAULT 1,
  unit_price     DECIMAL(10,2) NOT NULL,
  line_total     DECIMAL(10,2) NOT NULL,
  source_module  VARCHAR(50) NULL,      -- e.g. 'restaurant', 'laundry'
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 7. PAYMENTS
-- ============================================================

CREATE TABLE payments (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_id         INT UNSIGNED NOT NULL,
  method             ENUM('cash','mobile_money','card','bank_transfer','other') NOT NULL,
  amount             DECIMAL(10,2) NOT NULL,
  reference_number   VARCHAR(100) NULL,   -- transaction / MoMo reference
  received_by        INT UNSIGNED NULL,
  paid_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  notes              VARCHAR(255) NULL,
  FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
  FOREIGN KEY (received_by) REFERENCES staff(id) ON DELETE SET NULL,
  INDEX idx_payment_invoice (invoice_id)
) ENGINE=InnoDB;

-- ============================================================
-- 8. HOUSEKEEPING
-- ============================================================

CREATE TABLE housekeeping_tasks (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  room_id        INT UNSIGNED NOT NULL,
  assigned_to    INT UNSIGNED NULL,
  task_type      ENUM('checkout_clean','daily_clean','deep_clean','inspection') NOT NULL DEFAULT 'checkout_clean',
  status         ENUM('pending','in_progress','completed','verified') NOT NULL DEFAULT 'pending',
  scheduled_for  DATE NULL,
  started_at     TIMESTAMP NULL,
  completed_at   TIMESTAMP NULL,
  notes          VARCHAR(255) NULL,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
  FOREIGN KEY (assigned_to) REFERENCES staff(id) ON DELETE SET NULL,
  INDEX idx_hk_status (status)
) ENGINE=InnoDB;

-- ============================================================
-- 9. MAINTENANCE
-- ============================================================

CREATE TABLE maintenance_requests (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  room_id       INT UNSIGNED NULL,       -- nullable: could be a common area
  reported_by   INT UNSIGNED NULL,
  assigned_to   INT UNSIGNED NULL,
  issue_title   VARCHAR(150) NOT NULL,
  description   TEXT NULL,
  priority      ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  status        ENUM('open','in_progress','resolved','cancelled') NOT NULL DEFAULT 'open',
  reported_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  resolved_at   TIMESTAMP NULL,
  FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL,
  FOREIGN KEY (reported_by) REFERENCES staff(id) ON DELETE SET NULL,
  FOREIGN KEY (assigned_to) REFERENCES staff(id) ON DELETE SET NULL,
  INDEX idx_maint_status (status)
) ENGINE=InnoDB;

-- ============================================================
-- 10. EXPENSES
-- ============================================================

CREATE TABLE expense_categories (
  id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name  VARCHAR(80) NOT NULL UNIQUE     -- Utilities, Repairs, Purchases, Operational
) ENGINE=InnoDB;

CREATE TABLE expenses (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id    INT UNSIGNED NOT NULL,
  description    VARCHAR(255) NOT NULL,
  amount         DECIMAL(10,2) NOT NULL,
  expense_date   DATE NOT NULL,
  recorded_by    INT UNSIGNED NULL,
  receipt_path   VARCHAR(255) NULL,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES expense_categories(id),
  FOREIGN KEY (recorded_by) REFERENCES staff(id) ON DELETE SET NULL,
  INDEX idx_expense_date (expense_date)
) ENGINE=InnoDB;

-- ============================================================
-- 11. NOTIFICATIONS
-- ============================================================

CREATE TABLE notifications (
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  staff_id       INT UNSIGNED NOT NULL,   -- recipient
  title          VARCHAR(150) NOT NULL,
  message        VARCHAR(500) NOT NULL,
  type           VARCHAR(50) NULL,        -- e.g. 'reservation', 'maintenance'
  related_table  VARCHAR(50) NULL,
  related_id     INT UNSIGNED NULL,
  is_read        TINYINT(1) NOT NULL DEFAULT 0,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
  INDEX idx_notif_staff_read (staff_id, is_read)
) ENGINE=InnoDB;

-- ============================================================
-- 12. AUDIT LOGS
-- ============================================================

CREATE TABLE audit_logs (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  staff_id     INT UNSIGNED NULL,
  action       VARCHAR(100) NOT NULL,     -- e.g. 'reservation.cancel'
  table_name   VARCHAR(50) NULL,
  record_id    INT UNSIGNED NULL,
  old_values   JSON NULL,
  new_values   JSON NULL,
  ip_address   VARCHAR(45) NULL,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE SET NULL,
  INDEX idx_audit_table_record (table_name, record_id),
  INDEX idx_audit_staff (staff_id)
) ENGINE=InnoDB;

-- ============================================================
-- 13. SYSTEM SETTINGS & BACKUPS
-- ============================================================

CREATE TABLE settings (
  `key`       VARCHAR(100) PRIMARY KEY,
  `value`     TEXT NULL,
  updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE backup_logs (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  file_path         VARCHAR(255) NOT NULL,
  file_size_bytes   BIGINT UNSIGNED NULL,
  status            ENUM('success','failed') NOT NULL,
  triggered_by      ENUM('scheduled','manual') NOT NULL DEFAULT 'scheduled',
  created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
