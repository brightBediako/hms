# Hotel Management System (HMS)

## System Architecture & Planning Document

**Version:** 1.0
**Stack:** PHP, MySQL, Tailwind CSS, HTML5, JavaScript
**Companion to:** HMS Product Requirements Document

---

## 1. Purpose of This Document

This document defines _how_ the HMS will be built — the architecture, folder structure, module boundaries, data flow, security model, and development phasing — as a bridge between the PRD (what to build) and implementation (writing code). It should be agreed upon before any database schema or module code is written, since it determines conventions every module will follow.

---

## 2. Architectural Style

**Pattern:** Layered MVC-inspired architecture, implemented in plain/procedural-friendly PHP (no framework assumed, but structured like one). This keeps the system lightweight and easy to host on shared/small hotel infrastructure while still being maintainable as it grows.

```
┌─────────────────────────────────────────┐
│  Presentation Layer                      │
│  (Views: PHP templates + Tailwind + JS)  │
├─────────────────────────────────────────┤
│  Application / Controller Layer          │
│  (Route handlers, request validation)    │
├─────────────────────────────────────────┤
│  Business Logic Layer                    │
│  (Services: Reservation, Billing, etc.)  │
├─────────────────────────────────────────┤
│  Data Access Layer                       │
│  (Models, PDO + Prepared Statements)     │
├─────────────────────────────────────────┤
│  MySQL Database                          │
└─────────────────────────────────────────┘
```

**Why this pattern:**

- Separates "what the user sees" from "what the system does," so UI changes (Tailwind redesigns) never touch business logic.
- Business logic (e.g., "can this room be booked on these dates") lives in one place, callable from Reservations, Front Desk, or an API layer later.
- Data access is centralized, so every query goes through prepared statements — closing off SQL injection by construction rather than by discipline.

---

## 3. Directory Structure

```
/hms
├── public/                      # Web root (only folder exposed to the browser)
│   ├── index.php                # Front controller / router entry point
│   ├── assets/
│   │   ├── css/                 # Compiled Tailwind output
│   │   ├── js/                  # Vanilla JS modules (per feature)
│   │   └── images/
│   └── uploads/                 # Guest ID scans, receipts, etc. (write-restricted)
│
├── app/
│   ├── controllers/
│   │   ├── DashboardController.php
│   │   ├── ReservationController.php
│   │   ├── FrontDeskController.php
│   │   ├── RoomController.php
│   │   ├── GuestController.php
│   │   ├── BillingController.php
│   │   ├── PaymentController.php
│   │   ├── HousekeepingController.php
│   │   ├── MaintenanceController.php
│   │   ├── ExpenseController.php
│   │   ├── StaffController.php
│   │   ├── ReportController.php
│   │   ├── AuditController.php
│   │   └── SettingsController.php
│   │
│   ├── services/                # Business logic, framework-agnostic
│   │   ├── ReservationService.php
│   │   ├── AvailabilityService.php
│   │   ├── BillingService.php
│   │   ├── PaymentService.php
│   │   ├── HousekeepingService.php
│   │   ├── ReportService.php
│   │   └── NotificationService.php
│   │
│   ├── models/                  # One model per core table/entity
│   │   ├── Room.php
│   │   ├── RoomType.php
│   │   ├── Reservation.php
│   │   ├── Guest.php
│   │   ├── Invoice.php
│   │   ├── Payment.php
│   │   ├── Staff.php
│   │   ├── Expense.php
│   │   └── AuditLog.php
│   │
│   ├── core/                    # Framework-level plumbing
│   │   ├── Database.php         # PDO singleton/connection manager
│   │   ├── Router.php
│   │   ├── Auth.php             # Session auth + RBAC checks
│   │   ├── Request.php
│   │   ├── Response.php
│   │   ├── Validator.php
│   │   └── CSRF.php
│   │
│   ├── views/
│   │   ├── layouts/
│   │   │   ├── app.php          # Main authenticated layout (sidebar/topbar)
│   │   │   └── auth.php         # Login/guest layout
│   │   ├── dashboard/
│   │   ├── reservations/
│   │   ├── rooms/
│   │   ├── guests/
│   │   ├── billing/
│   │   ├── housekeeping/
│   │   ├── reports/
│   │   └── settings/
│   │
│   └── helpers/
│       ├── format.php           # Currency, date formatting
│       └── permissions.php      # Role/permission constants
│
├── config/
│   ├── app.php                  # Env-driven config loader
│   ├── database.php
│   └── roles.php                # Role → permission map
│
├── database/
│   ├── migrations/               # Versioned .sql files
│   ├── seeders/                  # Sample/demo data
│   └── schema.sql                # Full current schema snapshot
│
├── storage/
│   ├── logs/
│   ├── backups/                  # Automated DB backups
│   └── cache/
│
├── tests/
│
├── .env                          # DB credentials, app secrets (not committed)
├── .env.example
└── composer.json                 # For autoloading + optional libraries (PDO, PHPMailer, etc.)
```

**Key principle:** only `public/` is web-accessible. Everything else — including config, models, and services — sits outside the document root so a misconfigured server can't expose source code or `.env` secrets.

---

## 4. Module-to-Architecture Mapping

Each PRD module maps to a Controller + Service + Model set. This keeps the 18 modules consistent and predictable to build.

| PRD Module           | Controller             | Core Service(s)                            | Primary Tables              |
| -------------------- | ---------------------- | ------------------------------------------ | --------------------------- |
| Dashboard            | DashboardController    | ReportService                              | (reads across modules)      |
| Reservations         | ReservationController  | ReservationService, AvailabilityService    | reservations, rooms         |
| Front Desk           | FrontDeskController    | ReservationService, BillingService         | reservations, rooms, guests |
| Room Management      | RoomController         | AvailabilityService                        | rooms, room_status_log      |
| Room Types & Pricing | RoomController         | —                                          | room_types, rate_plans      |
| Guest Management     | GuestController        | —                                          | guests, guest_documents     |
| Check-In/Out         | FrontDeskController    | ReservationService, HousekeepingService    | reservations, rooms         |
| Billing & Invoicing  | BillingController      | BillingService                             | invoices, invoice_items     |
| Payments             | PaymentController      | PaymentService                             | payments                    |
| Housekeeping         | HousekeepingController | HousekeepingService                        | housekeeping_tasks, rooms   |
| Maintenance          | MaintenanceController  | —                                          | maintenance_requests        |
| Expenses             | ExpenseController      | —                                          | expenses                    |
| Staff & Roles        | StaffController        | Auth                                       | staff, roles, permissions   |
| Reports & Analytics  | ReportController       | ReportService                              | (aggregates)                |
| Notifications        | (cross-cutting)        | NotificationService                        | notifications               |
| Audit Logs           | AuditController        | (cross-cutting, hooked into Auth/Services) | audit_logs                  |
| Backup & Restore     | SettingsController     | —                                          | (system-level)              |
| System Settings      | SettingsController     | —                                          | settings                    |

---

## 5. Core Data Entities (High-Level, Pre-Schema)

This is a conceptual entity map — the actual schema (columns, keys, constraints) is the next deliverable. Shown here to validate relationships before formal design.

```
Guest ──< Reservation >── Room ──> RoomType
                │
                ├──< Invoice ──< InvoiceItem
                │        │
                │        └──< Payment
                │
Staff ──< AuditLog
Staff ──< HousekeepingTask >── Room
Staff ──< MaintenanceRequest >── Room
Staff ──< Expense
```

Notes:

- A **Reservation** is the hinge entity: it connects a Guest to a Room for a date range and drives Front Desk, Billing, and Housekeeping.
- **Invoice** is generated from a Reservation but can include non-room charges (future Restaurant integration will post charges here).
- **Room status** (Available/Occupied/Reserved/Cleaning/Maintenance) is derived from Reservation + HousekeepingTask + MaintenanceRequest state, not a single manually-set flag — this avoids status drift.

---

## 6. Authentication & Role-Based Access Control (RBAC)

**Model:** Session-based auth (PHP native sessions, `httponly` + `secure` cookies), with a roles/permissions table rather than hardcoded role checks.

```
staff ──< staff_roles >── roles ──< role_permissions >── permissions
```

Default roles from the PRD's target users:

- **Owner** — full access, including financial reports and settings.
- **Manager** — full operational access, no system settings/backup.
- **Receptionist** — Reservations, Front Desk, Guests, Billing (create only), Payments.
- **Accountant** — Billing, Payments, Expenses, Reports (read).
- **Housekeeping Staff** — Housekeeping tasks only (room status updates).
- **Maintenance Staff** — Maintenance module only.
- **System Administrator** — Staff & Roles, System Settings, Backup & Restore, Audit Logs.

Every controller action checks permissions through a single `Auth::can('permission.key')` call — never role name string-matching — so permissions can be reassigned without code changes.

---

## 7. Security Architecture

Directly implementing the PRD's security section:

| Concern          | Implementation                                                                                                                                                              |
| ---------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| SQL Injection    | PDO prepared statements only; no raw query concatenation anywhere in models                                                                                                 |
| CSRF             | Per-session CSRF token, validated on all state-changing POST/PUT/DELETE requests                                                                                            |
| XSS              | All output escaped via `htmlspecialchars()` in views by default                                                                                                             |
| Session security | Regenerate session ID on login, `httponly`/`secure`/`SameSite=Lax` cookies, idle timeout                                                                                    |
| Password storage | `password_hash()` (bcrypt/argon2), never reversible encryption                                                                                                              |
| Access control   | RBAC enforced server-side on every route, not just hidden in the UI                                                                                                         |
| Audit trail      | Every create/update/delete on sensitive tables (reservations, invoices, payments, staff, settings) logged to `audit_logs` with actor, action, before/after state, timestamp |
| File uploads     | Type/size validation, stored outside guessable paths, served through a controller rather than direct linking where sensitive (guest ID scans)                               |
| Backups          | Scheduled encrypted DB dumps to `storage/backups`, rotated, with a documented restore procedure                                                                             |

---

## 8. Request Lifecycle

```
Browser Request
   │
   ▼
public/index.php (front controller)
   │
   ▼
Router — matches URL to Controller@method
   │
   ▼
Auth middleware — is logged in? has permission?
   │
   ▼
CSRF check (for state-changing requests)
   │
   ▼
Controller — validates input, calls Service
   │
   ▼
Service — business rules (e.g., "no double-booking a room")
   │
   ▼
Model — prepared-statement query to MySQL
   │
   ▼
Controller — passes data to View
   │
   ▼
View — Tailwind-styled HTML response
```

This flow is identical for every module, which is what makes 18 modules manageable — new modules add a Controller/Service/Model/View set, not new plumbing.

---

## 9. Frontend Approach

- **Tailwind CSS**, compiled once via CLI/build step into `public/assets/css` — no CDN in production, for performance and offline reliability.
- **Vanilla JavaScript**, organized per-feature (`assets/js/reservations.js`, `assets/js/calendar.js`, etc.) rather than one monolithic file — no heavy JS framework, keeping the system lightweight to match the "small/medium hotel" target.
- Shared layout (`layouts/app.php`) provides sidebar navigation scoped to the logged-in user's permitted modules, a topbar with quick actions (new reservation, check-in), and a consistent responsive shell (mobile-friendly per NFRs).
- Printable views (invoices, reports) use a dedicated print stylesheet rather than JS-generated PDFs, keeping the system dependency-light; PDF export can be a later enhancement.

---

## 10. Integration Boundary: Restaurant Management System

Per the PRD, the Restaurant Management System (RMS) stays a **separate application**. Integration point:

- HMS exposes a narrow internal API/service (e.g., `POST /api/room-charges`) that RMS calls to post a charge against an active reservation's folio.
- Authentication between the two systems uses a service-level API key, not staff sessions.
- HMS remains the source of truth for guest/room/reservation state; RMS never writes directly to HMS tables — everything goes through the charge-posting service, keeping the boundary clean if RMS is swapped out later.
- This same pattern (a small internal API layer) is the intended path for the other "Future Integrations" listed in the PRD (Laundry, Conference, SMS, Email, Payment Gateway, Accounting), so it's worth building the API layer generically from the start rather than one-off per integration.

---

## 11. Coding Conventions

- **PSR-4 style autoloading** via Composer, even without a full framework.
- One class per file, matching filename.
- Controllers: thin — validate input, call service, return view/response. No business logic or SQL in controllers.
- Services: contain business rules, are unit-testable independent of HTTP.
- Models: only data access (CRUD + queries), no business rules.
- All dates stored in UTC / MySQL `DATETIME`, formatted for display in the helper layer.
- All monetary values stored as integers (smallest currency unit) or `DECIMAL(10,2)` — never floats — to avoid rounding errors in billing.

---

## 12. Suggested Build Phases

A phased order that keeps each phase demoable and avoids building modules that depend on unbuilt ones:

1. **Foundation** — DB schema, Auth/RBAC, core layout, routing, CSRF/session security.
2. **Rooms & Room Types** — since almost everything else depends on room data existing.
3. **Guests & Reservations** — booking creation, calendar view, availability logic.
4. **Front Desk** — check-in/check-out, room assignment, tied to Reservations.
5. **Billing & Payments** — invoice generation from reservations, payment recording.
6. **Housekeeping & Maintenance** — room status workflows.
7. **Expenses, Staff & Roles management UI, Reports & Dashboard** — these read/aggregate data from everything above, so they benefit from being built once core data exists.
8. **Notifications, Audit Log viewer, Backup & Restore UI, System Settings** — operational/admin layer, wraps up the system.

This phasing also lines up naturally with the "database schema next" step you'd want before writing any module code.

---

## 13. Open Decisions to Confirm Before Building

- **PHP style:** plain procedural-with-structure (as scaffolded above) vs. a micro-framework (e.g., Slim) — the structure above works either way, but it changes the Router/Core layer.
- **Multi-currency:** PRD doesn't mention it — assuming single hotel currency (relevant to your GHS/Mobile Money context) unless you want it configurable in Settings.
- **Session-based vs. token-based auth:** session-based is assumed (fits a server-rendered PHP app); confirm if an API/mobile client is ever planned despite being out of scope now.
- **Backup destination:** local `storage/backups` only, or also offsite
