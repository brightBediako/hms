# Code Standards

Implementation rules and conventions for the Hotel Management System (HMS). Follow these in every session so PHP modules stay consistent with `context/architecture.md`.

---

## Engineering Mindset

- Read `context/project-overview.md`, `context/architecture.md`, `context/build-plan.md`, and `context/library-docs.md` before implementing.
- For any UI work, also read `context/ui-tokens.md`, `context/ui-rules.md`, and `context/ui-registry.md` (and relevant files under `context/designs/`).
- Build only the current feature from `context/build-plan.md`.
- Search the project before creating any function, class, view, or SQL — do not duplicate.
- Keep changes small and scoped to the current task.
- Prefer simple, readable code over clever abstractions.
- Every module should be reachable through navigation and testable with seeded MySQL data.
- Do not introduce AssetFlow, Android, Spring Boot, React, or other unrelated stacks.

---

## PHP Standards

- PHP 8.x preferred; declare types on method parameters and return values where practical.
- One class per file; filename matches class name (`ReservationService.php`).
- PSR-4 autoloading via Composer.
- Controllers: thin — validate input, call a service, return a view or redirect. No business rules or SQL in controllers.
- Services: business rules only (availability, billing totals, check-in rules). Unit-testable without HTTP.
- Models: data access only (CRUD + queries) via PDO prepared statements. No business rules.
- Never concatenate user input into SQL.
- Prefer early returns over deep nesting.
- Domain naming: reservation, guest, room, room type, invoice, payment, housekeeping, maintenance, expense, staff, audit log.

---

## Directory Structure

Follow `context/architecture.md`. Do not invent parallel trees.

```text
public/                 # Web root only
  index.php
  assets/css|js|images
  uploads/

app/
  controllers/
  services/
  models/
  core/                 # Database, Router, Auth, Request, Response, Validator, CSRF
  views/
    layouts/
    {module}/
  helpers/

config/
db/                     # schema + seed SQL (source of truth for tables)
storage/logs|backups|cache
tests/
```

Only `public/` is browser-accessible. Config, models, services, and `.env` stay outside the document root.

---

## File Naming

- Classes: PascalCase (`BillingController.php`, `Guest.php`).
- Views: lowercase snake or kebab folders matching modules (`reservations/index.php`, `billing/show.php`).
- JS: feature files under `public/assets/js/` (`reservations.js`, `calendar.js`).
- Helpers: lowercase (`format.php`, `permissions.php`).
- Config: lowercase (`app.php`, `database.php`, `roles.php`).

---

## Request Lifecycle

Every feature follows the same path:

1. `public/index.php` → Router
2. Auth middleware (logged in? `Auth::can(...)`?)
3. CSRF check on state-changing POST/PUT/DELETE
4. Controller validates → Service applies rules → Model queries
5. Controller renders view or redirects with flash message

Do not add one-off entry PHP files that bypass the front controller.

---

## Controllers

```php
// Thin controller sketch
public function store(Request $request): void
{
    $data = $this->validator->validate($request->post(), [
        'guest_id' => 'required|int',
        'room_id'  => 'required|int',
        'check_in' => 'required|date',
        'check_out'=> 'required|date',
    ]);

    $reservation = $this->reservationService->create($data, Auth::id());

    Response::redirect('/reservations/' . $reservation->id);
}
```

Rules:

- Authorize with permission keys, not role name comparisons.
- Escape output in views with `htmlspecialchars()` (or a shared `e()` helper).
- Flash success/error messages for user feedback.
- Pass IDs in URLs; load entities in the controller/service — do not trust client-submitted full objects.

---

## Services & Models

- Put “can this room be booked on these dates?” in `AvailabilityService` / `ReservationService`, not in a view or controller.
- Models return arrays or simple entity objects; keep SQL inside the model.
- Use transactions for multi-step writes (invoice + items, check-in + room status + audit).
- Monetary values: `DECIMAL(10,2)` or integer minor units — never floats.
- Dates: store `DATETIME` (UTC or hotel-local consistently); format for display in helpers.

---

## Views & Frontend

- Server-rendered PHP templates + Tailwind utility classes.
- Shared layout: `layouts/app.php` for authenticated pages, `layouts/auth.php` for login.
- Sidebar lists only modules the current user may access.
- Vanilla JS per feature; no heavy SPA framework unless the build plan explicitly changes.
- Printable invoices/reports use print CSS; PDF libraries are optional later.
- Responsive: usable on desktop front desk and mobile staff devices.

---

## Authentication & RBAC

- Session-based auth for staff (not guests).
- Password: `password_hash()` / `password_verify()` only.
- Permissions use keys from seed data (`reservations.create`, `billing.void`, etc.).
- Every protected route checks permission server-side; hiding a nav link is not security.
- Default roles from the PRD: Owner, Manager, Receptionist, Accountant, Housekeeping Staff, Maintenance Staff, System Administrator.

---

## Security Rules

| Concern | Rule |
| ------- | ---- |
| SQL injection | PDO prepared statements only |
| CSRF | Token on every state-changing request |
| XSS | Escape all dynamic output in views |
| Sessions | Regenerate ID on login; httponly; SameSite=Lax; idle timeout |
| Uploads | Validate type/size; store under controlled paths; serve sensitively through a controller when needed |
| Secrets | `.env` only; never commit credentials |
| Audit | Log create/update/delete on reservations, invoices, payments, staff, settings |

---

## Seed & Demo Data

- Use `db/hms_schema.sql` and `db/hms_seed_data.sql` as the baseline.
- Add demo rooms/guests/reservations via seeders or migrations under `db/` — not hardcoded fake arrays inside random views.
- Keep sample data internally consistent so dashboard metrics match operational tables.

---

## Error Handling

- Never use empty catch blocks.
- Log exceptions to `storage/logs`.
- Show user-safe messages; do not dump stack traces in production.
- Validation errors return to the form with field messages and old input where appropriate.

---

## Dependency Rules

Approved direction:

- PHP + Composer autoload
- PDO / MySQL
- Tailwind CSS (compiled to `public/assets/css`)
- Vanilla JavaScript
- Optional later: PHPMailer (email), similar small libraries as needed

Do not use for this project:

- Android / Kotlin / Jetpack Compose
- Spring Boot / Retrofit / Room / WorkManager
- Next.js / React as the primary app UI
- Job-search stack leftovers (InsForge, Adzuna, PostHog, Browserbase, Stagehand, OpenAI matching)

Add new Composer packages only when the current feature requires them.

---

## Comments

- Comments explain why, not what.
- Do not leave TODO comments as a substitute for implementation.
- Brief comments are fine at security or money-critical boundaries.

---

## Completion Standard

A feature is complete only when:

- It matches the current `build-plan.md` feature
- It uses the layered Controller → Service → Model → View pattern
- It is reachable through the app shell for permitted roles
- It enforces RBAC and CSRF where applicable
- It uses prepared statements and escaped output
- Normal and relevant empty/error states are covered
- `context/progress-tracker.md` is updated
- `context/ui-registry.md` is updated if a reusable UI partial/component was added
)
