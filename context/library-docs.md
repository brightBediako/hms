# Library Docs

Project-specific usage patterns for the Hotel Management System (HMS). Based on `context/project-overview.md`, `context/architecture.md`, and `context/build-plan.md`.

Stack focus: **PHP**, **MySQL (PDO)**, **Tailwind CSS**, **HTML5**, **Vanilla JavaScript**. No Android/Spring Boot tooling belongs here.

---

## Before Using Any Library

1. Confirm the product goal in `context/project-overview.md`.
2. Confirm boundaries in `context/architecture.md`.
3. Confirm the current feature in `context/build-plan.md`.
4. For UI/CSS work, follow `context/ui-tokens.md`, `context/ui-rules.md`, and `context/ui-registry.md`.
5. Search for an existing controller, service, model, view, or helper before adding a dependency.

Prefer:

- Server-rendered PHP views over SPA frameworks
- PDO prepared statements over raw `mysqli` string queries
- Compiled Tailwind over CDN Tailwind in production
- Small vanilla JS files per feature over a large JS framework

---

## PHP & Composer

PHP is the application language. Composer provides PSR-4 autoloading and optional packages.

### Rules

- Map namespaces to `app/` (e.g. `App\Controllers\`, `App\Services\`, `App\Models\`, `App\Core\`).
- One class per file.
- Prefer typed properties/parameters on PHP 8+.
- Keep Controllers / Services / Models responsibilities split as in architecture.

### Autoload Sketch

```json
{
  "autoload": {
    "psr-4": {
      "App\\": "app/"
    },
    "files": [
      "app/helpers/format.php",
      "app/helpers/permissions.php"
    ]
  }
}
```

---

## PDO & MySQL

MySQL is the source of truth. Access it only through `app/core/Database.php` and models.

### Rules

- Use PDO with `ERRMODE_EXCEPTION`, prepared statements, and bound parameters.
- Never interpolate request data into SQL strings.
- Use transactions for multi-table writes (reservation + room status, invoice + items + payment).
- Align tables with `db/hms_schema.sql` — do not invent parallel schemas in code.
- Charset: `utf8mb4`.

### Connection Pattern

```php
$pdo = Database::connection();
$stmt = $pdo->prepare(
    'SELECT * FROM rooms WHERE id = :id LIMIT 1'
);
$stmt->execute(['id' => $roomId]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);
```

### Money & Dates

- Money: `DECIMAL(10,2)` (or integer minor units) — never `float`.
- Format currency in helpers (e.g. GHS) for display.
- Store datetimes consistently; format in views via helpers.

---

## Router, Request, Response

Custom lightweight core (no required micro-framework unless explicitly adopted later).

### Rules

- All HTTP traffic enters `public/index.php`.
- Router maps path + method → `Controller@method`.
- `Request` wraps GET/POST/files/headers.
- `Response` handles view render, redirect, and JSON for future internal APIs.
- Do not create standalone scripts under `public/` that bypass auth/CSRF for module actions.

### Future Internal API

When integrating Restaurant Management System:

- Narrow endpoints such as `POST /api/room-charges`
- Authenticate with a service API key (not staff session cookies)
- Post charges onto an active reservation folio through a service — never let RMS write HMS tables directly

---

## Auth, Sessions & CSRF

### Auth

- Session-based staff authentication.
- `password_hash()` / `password_verify()` for credentials.
- `Auth::check()`, `Auth::id()`, `Auth::can('permission.key')`.
- Permission keys match `db/hms_seed_data.sql` (e.g. `frontdesk.checkin`, `billing.void`).

### Sessions

- Regenerate session ID on successful login.
- Cookie flags: `httponly`, `Secure` when HTTPS, `SameSite=Lax`.
- Idle timeout for front-desk machines left unattended.

### CSRF

- Issue a per-session token.
- Include token in forms; validate on POST/PUT/DELETE.
- Reject mismatched tokens before controller logic runs.

---

## Validator

Centralize input rules in `app/core/Validator.php` (or equivalent).

### Rules

- Controllers validate before calling services.
- Return field-level errors to the form view.
- Services may still enforce domain invariants (overlap, status transitions) after validation.

---

## Tailwind CSS

Tailwind is the styling system for HMS UI.

### Rules

- Compile once via Tailwind CLI/build into `public/assets/css` — avoid production CDN dependency for reliability.
- Use the shared layouts for consistent sidebar/topbar spacing.
- Prefer utility classes in views; extract repeated patterns into PHP partials when reused across modules.
- Support responsive breakpoints for mobile housekeeping/maintenance staff.
- Print stylesheets for invoices and reports.

### Layouts

- `app/views/layouts/app.php` — authenticated shell
- `app/views/layouts/auth.php` — login/guest shell

---

## Vanilla JavaScript

Use small feature modules under `public/assets/js/`.

### Examples

- `reservations.js` — form helpers, conflict messaging
- `calendar.js` — reservation calendar interactions
- `frontdesk.js` — check-in/out confirmations

### Rules

- No mandatory React/Vue/Angular for core HMS.
- Progressive enhancement: core flows must work with server round-trips even if JS fails.
- Keep DOM selectors scoped; avoid one global mega-file.

---

## Views & Escaping

### Rules

- Escape dynamic output: `<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>` or shared `e($value)`.
- Pass only the data the view needs.
- Reusable UI fragments (status badges, empty states, flash alerts) live as view partials; register them in `context/ui-registry.md` when added.
- Empty and error states should be first-class in list/detail screens.

### Status Labels (rooms / reservations)

Use consistent labels aligned with the schema:

- Room: Available, Occupied, Reserved, Cleaning, Maintenance
- Reservation: pending / confirmed / checked_in / checked_out / cancelled (match actual ENUM values in schema)
- Housekeeping / maintenance: scheduled, in progress, completed, cancelled as defined in schema

---

## Reporting & Print

### Rules

- Aggregate in `ReportService` from live tables — do not hardcode dashboard fake numbers once DB data exists.
- Prefer HTML + print CSS for invoices/reports first.
- Add PDF generation later only if print output is insufficient.

---

## File Uploads

Used for guest ID scans, receipts, etc.

### Rules

- Validate MIME type and size server-side.
- Store under `public/uploads/` or a non-guessable path with controlled serving for sensitive docs.
- Save metadata in `guest_documents` (or related tables).
- Never trust client-provided filenames as the final storage name.

---

## Backups

### Rules

- Dump MySQL to `storage/backups` on schedule or via Settings UI.
- Rotate old backups; document restore steps for operators.
- Permission: `backup.manage`.
- Prefer encrypted dumps if storing off-server later (open decision in architecture).

---

## Notifications

### Rules

- Persist to `notifications` via `NotificationService`.
- Create from domain events (new reservation, maintenance assigned, payment recorded) inside services — not from views.
- UI lists unread/read; marking read is a normal POST with CSRF.

---

## Audit Logging

### Rules

- Hook sensitive mutations in services (or a dedicated auditor called by services).
- Store actor staff id, action, entity type/id, before/after JSON, timestamp in `audit_logs`.
- Viewer is read-only (`audit.view`).

---

## Optional Libraries (Later)

Add only when a phase needs them:

| Library | Use |
| ------- | --- |
| PHPMailer (or similar) | Email notifications |
| Small PDF helper | Invoice PDF export if print CSS is not enough |

Do not add packages “just in case.”

---

## Libraries / Stacks Not Used

These belong to other products or prior directions and must not guide HMS:

- Kotlin, Jetpack Compose, Navigation Compose, Room, WorkManager, Retrofit
- Spring Boot + PostgreSQL as the HMS app server (HMS is PHP + MySQL)
- Next.js / React as the primary HMS UI
- InsForge, Adzuna, PostHog, Browserbase, Stagehand, OpenAI job matching

---

## Implementation Priority

1. Composer autoload + front controller + Router
2. PDO Database + schema/seed
3. Auth / CSRF / RBAC
4. Tailwind layout shell
5. Module order from `build-plan.md` (Rooms → Guests/Reservations → Front Desk → Billing → Ops → Admin)
6. Optional email/PDF/integration APIs after core modules are stable

Success: staff can run day-to-day hotel operations (book, check in/out, bill, clean, report) through the browser with real MySQL data and role-appropriate access.
)
