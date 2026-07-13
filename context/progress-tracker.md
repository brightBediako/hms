# Progress Tracker

Update this file after every completed feature. Any AI agent reading this should immediately know what is done, what is in progress, and what is next.

Track against `context/build-plan.md`. Schema/seed under `db/` count as data foundation, not as application feature completion.

---

## Current Status

**Phase:** Phase 1 - Foundation  
**Last completed:** Database schema + seed SQL (`db/hms_schema.sql`, `db/hms_seed_data.sql`)  
**In progress:** None  
**Next:** 01 Project Shell & Routing

Product, architecture, build, standards, library, progress, and UI docs (`ui-tokens`, `ui-rules`, `ui-registry` + `designs/`) are aligned to PHP/MySQL/Tailwind HMS (Hospitality Command). Application folders (`public/`, `app/`, etc.) are not built yet.

---

## Progress

### Data Foundation

- [x] MySQL schema (`db/hms_schema.sql`)
- [x] Seed roles, permissions, baseline categories (`db/hms_seed_data.sql`)

### Phase 1 - Foundation

- [ ] 01 Project Shell & Routing
- [ ] 02 Database Connection & Core Plumbing
- [ ] 03 Auth, RBAC, CSRF & Sessions
- [ ] 04 App Layout & Tailwind Shell

### Phase 2 - Rooms & Room Types

- [ ] 05 Room Types & Rate Plans
- [ ] 06 Room Management

### Phase 3 - Guests & Reservations

- [ ] 07 Guest Management
- [ ] 08 Reservations & Availability

### Phase 4 - Front Desk

- [ ] 09 Check-In, Check-Out & Room Assignment

### Phase 5 - Billing & Payments

- [ ] 10 Billing & Invoicing
- [ ] 11 Payments

### Phase 6 - Housekeeping & Maintenance

- [ ] 12 Housekeeping
- [ ] 13 Maintenance

### Phase 7 - Operations, Staff & Insights

- [ ] 14 Expenses
- [ ] 15 Staff & Roles UI
- [ ] 16 Reports & Dashboard

### Phase 8 - Admin & Cross-Cutting

- [ ] 17 Notifications
- [ ] 18 Audit Logs
- [ ] 19 Backup & Restore UI
- [ ] 20 System Settings

### Later Phase - Integrations

- [ ] Restaurant room-charge API (service API key)
- [ ] Other integrations as needed (Laundry, Conference, SMS, Email, Payment Gateway, Accounting)

---

## Decisions Made During Build

- HMS is a web app: PHP, MySQL, Tailwind CSS, HTML5, Vanilla JavaScript (per PRD).
- Architecture is layered MVC-inspired: Controllers → Services → Models → Views; only `public/` is the web root.
- Auth is session-based with RBAC permission keys (not role-name string checks).
- Single hotel currency assumed (e.g. GHS / Mobile Money context) unless Settings later make it configurable.
- Restaurant Management System remains a separate application; integration is via a narrow charge-posting API, not shared DB writes.
- Schema and seed already define tables and the seven PRD roles with permission keys.

---

## Notes

- Keep this file updated after each completed feature.
- When a feature is started, update `In progress`.
- When a feature is completed, check it off and update `Last completed` and `Next`.
- If implementation differs from `context/build-plan.md`, record the reason under Decisions.
- Do not mark integration or optional PDF/email work as blocking core hotel operations features.
)
