# AGENTS.md

Persistent guidance for AI agents working on the Hotel Management System (HMS).

HMS is a secure web app for small and medium hotels: reservations, front desk, rooms, guests, billing, housekeeping, maintenance, expenses, staff/RBAC, reports, and admin. The Restaurant Management System stays a separate app; integrate later only via a narrow room-charge API.

---

## Read Before Anything Else

Read these files in this exact order before implementation:

1. `context/project-overview.md`
2. `context/architecture.md`
3. `context/build-plan.md`
4. `context/progress-tracker.md`
5. `context/code-standards.md`
6. `context/library-docs.md`
7. `context/ui-tokens.md`
8. `context/ui-rules.md`
9. `context/ui-registry.md`

For visual composition of a module, also check matching mockups under `context/designs/`.

Use these files as the source of truth. If they conflict, prefer the most specific file for the task:

- Product scope: `project-overview.md`
- System boundaries and folder structure: `architecture.md`
- Current feature order: `build-plan.md`
- Completion status: `progress-tracker.md`
- PHP/SQL/security conventions: `code-standards.md`
- Stack usage (PDO, Tailwind, Auth, CSRF): `library-docs.md`
- Design system tokens (Hospitality Command): `ui-tokens.md`
- Screen and component rules: `ui-rules.md`
- Built/planned UI inventory: `ui-registry.md`
- Schema/seed: `db/hms_schema.sql`, `db/hms_seed_data.sql`

---

## Current Project Direction

- Product: Hotel Management System (HMS)
- Platform: Web (XAMPP / PHP hosting)
- Language: PHP 8.x
- UI: Server-rendered PHP views + Tailwind CSS + vanilla JavaScript
- Design system: Hospitality Command (`ui-tokens.md`) — Deep Teal + Warm Gold, Inter + JetBrains Mono
- Architecture: Layered MVC-inspired (Controller → Service → Model → View)
- Auth: Session-based staff login + RBAC permission keys
- Database: MySQL (`db/` schema and seed already exist)
- Default theme: Light
- Current phase: Phase 1 Foundation — next feature **01 Project Shell & Routing**
- Data: Real MySQL via PDO; use seed/demo rows under `db/`, not per-screen fake arrays
- Later: RMS room-charge API and other integrations listed in the PRD

Do not introduce Android, Kotlin, Compose, Spring Boot, Retrofit, Room, WorkManager, or AssetFlow patterns into this repo.

---

## Rules That Never Change

- Search first before creating any function, class, view, partial, or file.
- Do not create duplicates.
- Build only the current feature from `context/build-plan.md`.
- Only `public/` is the web root; keep config, app code, and `.env` outside public access.
- Controllers stay thin; business rules live in services; SQL lives in models with PDO prepared statements.
- Enforce RBAC with `Auth::can('permission.key')` — never role-name string matching alone.
- Validate CSRF on all state-changing requests.
- Escape all dynamic output in views.
- Update `context/progress-tracker.md` after every completed feature.
- Update `context/ui-registry.md` after every new reusable UI partial/component.
- Keep changes scoped to the current task.
- Do not invent a second schema; align with `db/hms_schema.sql` and seed permission keys.

---

## Feature Workflow

For every feature:

1. Read the current feature in `context/build-plan.md`.
2. Check `context/progress-tracker.md` for status.
3. Search for existing controllers, services, models, views, and partials.
4. Implement Controller → Service → Model → View (and JS only if needed).
5. Wire routes through `public/index.php` / Router; enforce auth and permissions.
6. Match Hospitality Command UI (`ui-tokens` / `ui-rules`); reuse registry partials.
7. Cover normal and relevant empty/loading/error states.
8. Update `context/ui-registry.md` if a reusable partial was added.
9. Update `context/progress-tracker.md` when the feature is complete.

A feature is successful when a permitted staff role can complete the workflow in the browser against MySQL.

---

## PHP Implementation Rules

- Use Composer PSR-4 autoloading under `app/`.
- Front controller: `public/index.php`.
- Shared layouts: `app/views/layouts/app.php` (authenticated), `auth.php` (login).
- Fixed sidebar (240px) + topbar (56px) + fluid dense content — not mobile-card-first UI.
- Tables are the primary list pattern; use `data-mono` for room numbers, rates, and folio/invoice IDs.
- Status chips: near-square, tinted background — reuse one partial for all domain statuses.
- Gold (`secondary-container`) only for conversion actions (e.g. Check-In, New Reservation).
- Compile Tailwind to `public/assets/css` for production; do not rely on CDN in the app.
- Monetary values: `DECIMAL(10,2)` or integer minor units — never floats.
- Pass IDs in URLs; look up entities in services/models — do not trust full objects from the client.

---

## Data Rules

- Apply `db/hms_schema.sql` and `db/hms_seed_data.sql` before first run.
- Centralize demo hotel data in seeders/migrations under `db/` when needed.
- Dashboard and report metrics must aggregate from real tables, not hardcoded fake KPIs.
- Room status should stay consistent with reservations, housekeeping, and maintenance.
- Sensitive mutations (reservations, invoices, payments, staff, settings) belong in `audit_logs`.

---

## Navigation Rules

Authenticated sidebar (permission-scoped) should grow to include:

- Dashboard
- Reservations
- Front Desk
- Rooms / Room Types
- Guests
- Billing & Invoices
- Payments
- Housekeeping
- Maintenance
- Expenses
- Staff & Roles
- Reports
- Notifications
- Audit Logs
- Settings / Backup

Topbar quick actions: New Reservation, Check-In (where permitted). Staff profile/logout from the account entry point.

Hide nav items the role cannot access; still enforce permissions server-side on every route.

---

## Later Integrations

Only after core modules are stable:

- Internal API for Restaurant Management System room charges (service API key, not staff sessions)
- Same narrow-API pattern for Laundry, Conference, SMS, Email, Payment Gateway, Accounting as needed

HMS remains the source of truth for guests, rooms, and reservations. External systems must not write HMS tables directly.

---

## Do Not Use

These belong to other products or prior directions and must not guide HMS:

- AssetFlow Mobile / Kotlin / Jetpack Compose / Navigation Compose
- Spring Boot / Retrofit / Room / WorkManager / JWT-as-primary-auth for this app
- Next.js / React as the primary HMS UI
- InsForge, Adzuna, PostHog, Browserbase, Stagehand
- OpenAI job matching, resume generation, company research agents
- JobPilot / job-search language and UI patterns

Tailwind **is** used here (compiled). Do not confuse that with “do not use Tailwind” rules from other projects.

---

## Completion Checklist

Before calling a feature complete, confirm:

- It matches the current feature in `context/build-plan.md`
- Existing files were checked first (no duplicates)
- Controller → Service → Model → View layering is respected
- RBAC and CSRF apply where required
- PDO prepared statements and escaped views are used
- UI follows Hospitality Command (`ui-tokens` / `ui-rules`)
- Screen is reachable for permitted roles and testable in the browser
- Normal and relevant empty/loading/error states are covered
- No unrelated stack or infrastructure work was added
- `context/progress-tracker.md` is updated
- `context/ui-registry.md` is updated if reusable UI was added
)
