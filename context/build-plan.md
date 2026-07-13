# Build Plan

## Core Principle

Build the Hotel Management System (HMS) as a secure, modular PHP web application on MySQL, following the layered architecture in `context/architecture.md`.

Each phase should leave a demoable slice of hotel operations. Prefer small increments that can be opened in the browser and verified immediately.

Implementation should follow:

- `context/project-overview.md` for product scope and modules
- `context/architecture.md` for folder structure, request lifecycle, and security
- `context/code-standards.md` for PHP/SQL/UI conventions
- `context/library-docs.md` for stack usage patterns
- `context/ui-tokens.md`, `context/ui-rules.md`, `context/ui-registry.md` for Hospitality Command UI
- `context/designs/` HTML mockups as visual targets per module
- `db/hms_schema.sql` and `db/hms_seed_data.sql` as the data foundation

**Stack:** PHP, MySQL, Tailwind CSS, HTML5, Vanilla JavaScript  
**Pattern:** Layered MVC-inspired (Controllers → Services → Models → Views)  
**Auth:** Session-based with RBAC (`Auth::can('permission.key')`)

Do not invent parallel architectures (Android, Spring Boot, React, etc.). Restaurant Management System stays a separate app; HMS only exposes a narrow room-charge API when that integration phase begins.

---

## Data Foundation Rule

Schema and seed already live under `db/`. Application code must use those tables and permission keys — do not create a second schema or ad-hoc tables inside modules.

- Apply `db/hms_schema.sql` before first run
- Apply `db/hms_seed_data.sql` for roles, permissions, and baseline categories
- All queries go through models with PDO prepared statements
- Seed or migrate demo hotel data (rooms, guests, reservations) as needed per phase so screens are testable

---

## Phase 1 - Foundation

### 01 Project Shell & Routing

Create the directory layout and front controller from `context/architecture.md`.

**Deliver:**

- Folders: `public/`, `app/` (controllers, services, models, core, views, helpers), `config/`, `storage/`, `tests/`
- `public/index.php` front controller
- Composer PSR-4 autoloading
- `.env.example` (DB credentials, app secrets — never commit real `.env`)
- `app/core/Router.php`, `Request.php`, `Response.php`

**Done When:**

- Only `public/` is the web root
- A health/ping or placeholder route renders successfully

---

### 02 Database Connection & Core Plumbing

Wire MySQL access and shared helpers.

**Deliver:**

- `config/database.php`, `config/app.php`
- `app/core/Database.php` (PDO singleton)
- Helpers: `format.php` (currency, dates), `permissions.php`
- Confirm connection against the `hms` database

**Done When:**

- App can run a prepared statement against seeded tables
- Config is env-driven, not hardcoded credentials in source

---

### 03 Auth, RBAC, CSRF & Sessions

Implement staff login and permission checks.

**Deliver:**

- `app/core/Auth.php`, `CSRF.php`, `Validator.php`
- Login/logout flow (staff email + password)
- Session security: regenerate on login, httponly / SameSite, idle timeout
- Password hashing via `password_hash()`
- `Auth::can('permission.key')` used by route guards
- `config/roles.php` aligned with seeded roles/permissions

**Done When:**

- Unauthenticated users cannot reach protected routes
- Permission checks use keys (e.g. `reservations.create`), not role-name string matching
- State-changing POSTs require a valid CSRF token

---

### 04 App Layout & Tailwind Shell

Build the authenticated UI chrome.

**Deliver:**

- `app/views/layouts/app.php` (sidebar + topbar)
- `app/views/layouts/auth.php` (login layout)
- Compiled Tailwind into `public/assets/css`
- Sidebar modules scoped to the logged-in staff member’s permissions
- Topbar quick actions placeholders (new reservation, check-in)

**Done When:**

- Login and authenticated shell render on desktop and mobile widths
- Navigation only shows modules the user can access

---

## Phase 2 - Rooms & Room Types

### 05 Room Types & Rate Plans

**Deliver:**

- RoomType / rate plan models and CRUD UI
- Base capacity, base rate, amenities, extra bed rate
- Permission: `rooms.manage` / `rooms.view`

**Done When:**

- Staff can create and edit room types and rate plans used by rooms

---

### 06 Room Management

**Deliver:**

- Room list and detail (number, type, floor, status)
- Status awareness: Available, Occupied, Reserved, Cleaning, Maintenance
- `room_status_log` updates where status changes are recorded
- Status derived from reservations + housekeeping + maintenance where practical (avoid orphan manual flags)

**Done When:**

- Rooms are listable/filterable and usable by later reservation/availability logic

---

## Phase 3 - Guests & Reservations

### 07 Guest Management

**Deliver:**

- Guest profiles: contact, ID type/number, nationality, notes
- Optional guest document upload path (validated, stored safely)
- Stay history link once reservations exist
- Permissions: `guests.view`, `guests.manage`

**Done When:**

- Guests can be created, edited, and searched for booking flows

---

### 08 Reservations & Availability

**Deliver:**

- `ReservationService` + `AvailabilityService`
- Walk-in and advance bookings
- Date-range conflict prevention (no double-booking a room)
- Modify / cancel reservation
- Calendar or date-grid view of occupancy
- Permissions: `reservations.*`

**Done When:**

- A reservation connects Guest + Room + dates
- Availability logic blocks overlapping bookings for the same room

---

## Phase 4 - Front Desk

### 09 Check-In, Check-Out & Room Assignment

**Deliver:**

- Arrivals / departures lists
- Check-in and check-out actions
- Room assignment and stay extension
- Guest transfer between rooms (`reservation_transfers`)
- Triggers housekeeping handoff on check-out where designed
- Permissions: `frontdesk.checkin`, `frontdesk.checkout`, `frontdesk.transfer`

**Done When:**

- Reception can complete a stay lifecycle from reserved → occupied → checked out

---

## Phase 5 - Billing & Payments

### 10 Billing & Invoicing

**Deliver:**

- Invoice generation from a reservation
- Line items: room charges, extras, discounts, taxes
- Void invoice (permission-gated)
- Printable invoice view
- Permissions: `billing.view`, `billing.create`, `billing.void`

**Done When:**

- An active/completed stay can produce a correct folio/invoice

---

### 11 Payments

**Deliver:**

- Record cash, mobile money, and card payments
- Partial payments and outstanding balance
- Link payments to invoices
- Permission: `payments.record`

**Done When:**

- Balances update correctly after payments; partial pay is supported

---

## Phase 6 - Housekeeping & Maintenance

### 12 Housekeeping

**Deliver:**

- Cleaning task list, assignment to staff
- Room status updates after cleaning
- Schedules tied to check-outs / stays
- Permissions: `housekeeping.view`, `housekeeping.manage`

**Done When:**

- Housekeeping staff can clear rooms back to Available (when no conflicting status)

---

### 13 Maintenance

**Deliver:**

- Work requests against rooms
- Assign, resolve, history
- Rooms can enter Maintenance status while open
- Permissions: `maintenance.view`, `maintenance.manage`

**Done When:**

- Maintenance lifecycle is visible and affects room availability appropriately

---

## Phase 7 - Operations, Staff & Insights

### 14 Expenses

**Deliver:**

- Expense categories and expense recording (utilities, repairs, purchases, ops)
- Permissions: `expenses.view`, `expenses.manage`

**Done When:**

- Expenses can be listed, filtered by date/category, and used by reports later

---

### 15 Staff & Roles UI

**Deliver:**

- Staff account CRUD (active/suspended)
- Assign role; permissions driven by seeded `role_permissions`
- Permission: `staff.manage`

**Done When:**

- Owner/Admin can manage staff without editing the database by hand

---

### 16 Reports & Dashboard

**Deliver:**

- Dashboard: occupancy, availability, arrivals/departures, revenue, outstanding balances, recent reservations
- Reports: occupancy, revenue, reservations, guests, expenses, profit summary
- Printable report views
- Permissions: `dashboard.view`, `reports.view`
- `ReportService` aggregates from existing tables (no duplicate fake metrics)

**Done When:**

- Dashboard and reports reflect real DB data for the logged-in hotel

---

## Phase 8 - Admin & Cross-Cutting

### 17 Notifications

**Deliver:**

- In-app notification records (booking, housekeeping, system)
- `NotificationService` hooks from key events
- Read/unread in UI

**Done When:**

- Staff see actionable notifications for events they care about

---

### 18 Audit Logs

**Deliver:**

- Log create/update/delete on sensitive entities (reservations, invoices, payments, staff, settings)
- Actor, action, before/after, timestamp
- Audit viewer UI (`audit.view`)

**Done When:**

- Sensitive changes are queryable for accountability

---

### 19 Backup & Restore UI

**Deliver:**

- Trigger DB dump to `storage/backups`
- List/rotate backups; documented restore steps
- Permission: `backup.manage`

**Done When:**

- Admin can create a backup from the UI and restore via documented procedure

---

### 20 System Settings

**Deliver:**

- Hotel name, currency (single currency assumed, e.g. GHS), tax defaults, other key-value settings
- Permission: `settings.manage`

**Done When:**

- Settings affect display/formatting and billing defaults consistently

---

## Later Phase - Integrations

Only after core modules are stable:

- Internal API for Restaurant Management System room charges (`POST /api/room-charges` + service API key)
- Same pattern for Laundry, Conference, SMS, Email, Payment Gateway, Accounting as needed
- HMS remains source of truth for guest/room/reservation state; external systems never write HMS tables directly

---

## Feature Count

| Phase | Focus | Features |
| ----- | ----- | -------- |
| 1 | Foundation | 01–04 |
| 2 | Rooms & Room Types | 05–06 |
| 3 | Guests & Reservations | 07–08 |
| 4 | Front Desk | 09 |
| 5 | Billing & Payments | 10–11 |
| 6 | Housekeeping & Maintenance | 12–13 |
| 7 | Expenses, Staff, Reports & Dashboard | 14–16 |
| 8 | Notifications, Audit, Backup, Settings | 17–20 |
| Later | Integrations | as needed |

**Total core features:** 20 (aligned with architecture build phases)
)
