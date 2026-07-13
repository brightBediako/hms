# UI Rules

Concise rules for building the Hotel Management System (HMS) UI. HMS is a **server-rendered PHP + Tailwind** web app for front desk and hotel operations staff.

Source of visual truth: `context/ui-tokens.md` (**Hospitality Command**).  
Reference mockups: `context/designs/` (e.g. Front Desk, Billing).  
Read this file before building any screen or shared partial.

---

## Product Feel

Hospitality Command should feel:

- Authoritative, precise, and reliable
- High information density without clutter
- Corporate Modern (fintech / enterprise SaaS)
- Built for a busy front desk — competence and control

The UI should help staff quickly answer:

- Which rooms are free tonight?
- Who is arriving / departing today?
- What is this guest’s folio balance?
- Which rooms need cleaning or maintenance?
- What action needs attention right now?

Utility over decoration. Prefer systematic clarity over marketing layouts.

---

## Platform

- PHP views + compiled Tailwind CSS + vanilla JS (per feature).
- Map `ui-tokens.md` colors, type, radius, and spacing into Tailwind theme config (see designs for the token → class mapping).
- Fonts: **Inter** (UI), **JetBrains Mono** (room numbers, rates, invoice/folio IDs).
- Icons: Material Symbols Outlined (as in design HTML), used sparingly.
- Default theme: **Light**. Do not ship a dark-first UI unless Settings later require it.
- Production CSS is compiled into `public/assets/css` — designs may use Tailwind CDN; the app must not depend on CDN in production.
- Do not use Android/Compose, React/Next.js, or AssetFlow mobile patterns.

---

## Layout Shell

Use the **Fixed Sidebar / Fluid Content** model from tokens:

| Token | Value | Use |
| ----- | ----- | --- |
| `sidebar_width` | 240px | Persistent primary nav |
| `topbar_height` | 56px | Hotel context, search, quick actions, staff menu |
| `container_margin` | 24px | Main content inset |
| `stack_gap` | 16px | Section/stack spacing |
| `cell_padding_v` / `cell_padding_h` | 8px / 12px | Dense table cells |

Rules:

- Authenticated pages use `app/views/layouts/app.php`.
- Login uses `app/views/layouts/auth.php`.
- Main stage uses a fluid content area (12-column mental model); prefer tables and tight stacks over large marketing cards.
- Table row height target: ~32–40px so ~20+ rows fit on a desktop screen.
- Spacing rhythm: tight **4px baseline**; gutters **16px**.
- Mobile: collapse sidebar to a drawer/overlay; keep topbar actions reachable. Front desk is primarily desktop; housekeeping/maintenance may be mobile.

---

## Elevation & Depth

Prefer **tonal layers** and **low-contrast outlines** — not heavy shadows.

| Level | Treatment |
| ----- | --------- |
| 0 Base | `background` / `surface` (`#f8faf7`) |
| 1 Cards / tables | White (`surface-container-lowest`) + 1px `outline-variant` border |
| 2 Dropdowns / modals | White + 1px border + subtle ambient shadow `0 2px 4px rgba(0,0,0,0.05)` |

Interactive rows: light primary tint (~2%), not lift/elevation changes.

---

## Shapes

Shape language is **Soft** but technical:

- Inputs / buttons: ~`0.25rem` (4px) — token `rounded.DEFAULT`
- Cards / modals: ~`0.5rem` (8px) — token `rounded.lg`
- Status chips: ~`0.125rem` (2px) — nearly square (`rounded.sm`); **not** full pills
- Avoid bubbly `rounded-full` chips for operational status

---

## Typography

Use token roles consistently:

| Role | Use |
| ---- | --- |
| `display-lg` | Rare page hero titles (auth, empty marketing-style pages only) |
| `headline-md` | Page titles in content area |
| `title-sm` | Card/section titles, modal titles |
| `body-md` | Form body, descriptions |
| `body-sm` | **Default for tables, sidebar, dense lists** (13px) |
| `label-caps` | Field labels, table headers (uppercase tracking) |
| `data-mono` | Room numbers, currency, folio/invoice IDs, rates |

Rules:

- Keep list/table text short; long notes belong on detail screens.
- Align numeric columns with mono so rates and balances scan vertically.

---

## Colors and Brand Usage

Anchors from tokens:

- **Primary / Deep Teal** (`primary` `#00342b`, `primary-container` `#004d40`) — trust, nav active states, primary buttons, focus rings.
- **Warm Gold** (`secondary-container` `#feb300`) — sparse **conversion** actions only (Check-In, New Reservation, Save payment). Do not gold-wash the UI.
- Neutrals — cool gray-greens (`surface-*`, `outline`) for clinical density.
- **Error** — validation and destructive confirms only.

Rules:

- One gold CTA per primary workflow region when possible.
- Status color must include text labels — never color alone.
- Desaturated “Pro” status tints so dense tables and the room tape chart stay readable.

---

## Status Chips

Use small rectangular badges: tinted background (~15% of status color) + solid text. **No borders. No full pills.**

### Room status (schema)

| Status | Meaning |
| ------ | ------- |
| Available | Ready to sell / assign |
| Occupied | Guest in-house |
| Reserved | Booked, not yet checked in |
| Cleaning | Housekeeping in progress |
| Maintenance | Out of order / work request |

### Reservation status

Booked · Checked in · Checked out · Cancelled · No show

### Invoice status

Draft · Issued · Partially paid · Paid · Void

### Housekeeping / maintenance

Pending · In progress · Completed · Verified (HK)  
Open · In progress · Resolved · Cancelled (maintenance)  
Priority: Low · Medium · High · Urgent

Reuse one shared partial (e.g. `status-chip.php`) for all of the above.

---

## Buttons and Actions

From tokens:

| Variant | Style | When |
| ------- | ----- | ---- |
| Primary | Deep teal bg, white text, bold ~13px | Default module actions (Save, Create guest) |
| Secondary / Action | Warm gold bg, dark text | Conversion: Check-In, New Reservation, Record payment |
| Ghost | Borderless, teal text | Secondary table row actions (View, Edit) |
| Destructive | Error palette | Void invoice, cancel reservation (with confirm) |

Rules:

- One primary (or one gold conversion) action per toolbar/section.
- Disabled state must be obvious.
- Important flows show success, conflict (e.g. room overlap), loading, and error feedback.

---

## Forms & Inputs

- Labels **above** fields in `label-caps`.
- 1px border; focus = **2px Deep Teal border**, no outer glow/ring blur.
- Soft rectangular radius (4px).
- Show field errors beside/under the control.
- Always include CSRF token on state-changing forms.
- Keep reservation and check-in forms short; use sections for long guest/billing forms.

---

## Data Tables

Tables are the **core** of HMS UI (not mobile card feeds).

Rules:

- Header row: `label-caps` on light gray (`surface-container-low` / ~`#F1F3F5`).
- Body: `body-sm`; currency and IDs in `data-mono`.
- 1px bottom border per row — **no zebra striping**.
- Row hover: subtle primary tint.
- Toolbar above table: search, filters, primary/gold action aligned right.
- Empty table → shared empty-state partial with optional CTA.

---

## Room Grid (Tape Chart / Gantt)

Specialized occupancy view:

- Dense room × date cells.
- Occupied: solid status-colored blocks.
- Reserved: **diagonal hatch** pattern (see designs: `.diagonal-hash` / `.diagonal-hatched`) so reserved ≠ checked-in.
- Keep cell padding tight; mono for room labels on the axis.

---

## Navigation

Sidebar (permission-scoped) should cover PRD modules as built:

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

Topbar:

- Hotel / property name
- Optional global search
- Quick actions: **New Reservation**, **Check-In** (gold where they are the conversion path)
- Staff avatar / profile / logout

Rules:

- Hide modules the role cannot access (still enforce RBAC server-side).
- Detail screens need a clear back link/breadcrumb.
- Do not bury check-in/out behind deep menus.

---

## Module Screen Expectations

### Dashboard

Occupancy, availability, today’s arrivals/departures, revenue snapshot, outstanding balances, recent reservations, quick actions. Metrics dense and scannable — not large marketing hero cards.

### Reservations

List + calendar/tape entry points; create/edit/cancel; conflict messaging when dates overlap.

### Front Desk

Arrivals/departures queues; check-in/out; room assignment; transfer; stay extension.

### Rooms

Room list with status chips; room type/rate management; filters by status/floor/type.

### Guests

Searchable profiles; ID details; stay history link.

### Billing & Payments

Invoice tables with mono IDs and amounts; line items; void gated; payment methods (cash, mobile money, card); partial pay and balance due.

### Housekeeping & Maintenance

Task/request tables; priority/status chips; assignment; room status impact visible.

### Reports

Filterable date ranges; printable layouts (print CSS). Same token typography in print where practical.

### Notifications / Audit / Settings

Dense lists; read/unread; audit is read-only chronology; settings as labeled forms, not consumer-app preference chrome.

---

## Empty, Loading, and Error States

Major list/detail screens need:

- Normal
- Empty (title + short help + CTA when logical)
- Loading (skeleton rows or compact spinner — keep density)
- Error (user-safe message + retry)

Examples: No reservations found · No arrivals today · No open housekeeping tasks · Could not load invoice.

---

## Accessibility

- Do not rely on color alone for room/reservation status.
- Maintain contrast between `on-surface` text and surfaces.
- Inputs and buttons must be keyboard-focusable; focus style = teal border as specified.
- Icon-only controls need `aria-label` or visible text.
- Comfortable hit targets on mobile drawer/nav.

---

## Print

Invoices and reports: dedicated print stylesheet; hide sidebar/topbar; keep mono for money columns.

---

## Do Nots

- Do not use AssetFlow mobile cards/bottom-nav as the primary pattern.
- Do not use full-pill status badges or purple/glow marketing aesthetics.
- Do not put gold on every button.
- Do not use heavy multi-layer shadows or decorative gradients as the main visual idea.
- Do not invent one-off status styles per module — extend the shared chip.
- Do not show raw SQL/exception messages to users.
- Do not bypass the app layout with one-off full-page designs except auth and print.
- Do not copy JobPilot / job-search language or UI.
)
