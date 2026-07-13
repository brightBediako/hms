# UI Registry

Living document for the Hotel Management System (HMS). Updated after every reusable UI partial or shared pattern is built.

Visual system: **Hospitality Command** — see `context/ui-tokens.md`.  
Behavior rules: `context/ui-rules.md`.  
Reference mockups: `context/designs/`.

Read this before building any new component — match existing patterns exactly before inventing new ones.

---

## How to Use

Before building any component:

1. Check if a similar partial already exists here
2. If yes — reuse it and match its classes/structure
3. If no — build it following `ui-rules.md` and `ui-tokens.md`, then add it here

After building — update this file with name, path, and key classes/tokens used.

---

## Theme Tokens

Wire `context/ui-tokens.md` into Tailwind (compiled CSS in production). Design HTML under `context/designs/` shows the working `tailwind.config` color map.

| Token set | Intended location | Notes |
| --------- | ----------------- | ----- |
| Colors | Tailwind theme `extend.colors` + CSS variables if needed | Surface scale, primary teal, secondary gold, tertiary, error — exact hex from `ui-tokens.md` |
| Typography | Tailwind `fontFamily` + utility classes | Inter (`sans`); JetBrains Mono (`mono` / `.data-mono`) |
| Type roles | Shared CSS or `@apply` utilities | `display-lg`, `headline-md`, `title-sm`, `body-md`, `body-sm`, `label-caps`, `data-mono` |
| Radius | Tailwind `borderRadius` | sm 0.125rem, DEFAULT 0.25rem, md 0.375rem, lg 0.5rem, xl 0.75rem |
| Spacing | Layout + utilities | sidebar 240px, topbar 56px, container margin 24px, stack gap 16px, cell padding 8×12 |
| Status colors | Shared partial / CSS | Desaturated Pro tints for room, reservation, invoice, HK, maintenance |

**Fonts (load in layout):** Inter 400/500/600/700 · JetBrains Mono 500 · Material Symbols Outlined

---

## Design References

| Mockup | Path | Covers |
| ------ | ---- | ------ |
| Dashboard | `context/designs/dashboard.html` | KPIs, arrivals/departures, occupancy snapshot, shell |
| Room Management | `context/designs/room_management.html` | Room list, status chips, filters, types/rates cues |
| Reservations Timeline | `context/designs/reservations_timeline.html` | Tape chart / Gantt, reserved hatch vs occupied |
| Front Desk Operations | `context/designs/front_desk_operations.html` | Arrivals/departures queues, check-in CTAs, gold actions |
| Housekeeping | `context/designs/housekeeping_management.html` | Task board/list, room status handoff |
| Billing & Invoices | `context/designs/BillingInvoices.html` | Dense tables, mono IDs/amounts, invoice toolbar |

Use these as visual targets when implementing layouts and partials. Prefer compiled tokens over leaving CDN Tailwind in production views.

---

## Layouts

| Layout | Path | Status | Notes |
| ------ | ---- | ------ | ----- |
| App shell | `app/views/layouts/app.php` | Planned | 240px sidebar + 56px topbar + fluid main; permission-scoped nav |
| Auth shell | `app/views/layouts/auth.php` | Planned | Login only; no sidebar |
| Print | `app/views/layouts/print.php` (or print CSS) | Planned | Invoices/reports; hide chrome |

---

## Shared Partials (Components)

Nothing built in `app/views` yet. Register each partial here when created.

### Planned — Shell

| Component | Intended path | Notes |
| --------- | ------------- | ----- |
| Sidebar nav | `app/views/partials/sidebar.php` | Module links filtered by `Auth::can()` |
| Topbar | `app/views/partials/topbar.php` | Hotel name, quick actions (New Reservation, Check-In), staff menu |
| Flash alerts | `app/views/partials/flash.php` | Success / error / warning banners |
| Breadcrumbs | `app/views/partials/breadcrumbs.php` | Optional detail-page context |

### Planned — Actions & Forms

| Component | Intended path | Notes |
| --------- | ------------- | ----- |
| Button primary | `app/views/partials/button.php` (or CSS classes) | Teal bg, white text, ~13px bold, radius DEFAULT |
| Button action (gold) | same | `secondary-container` bg; Check-In / New Reservation / key saves |
| Button ghost | same | Teal text, no border fill; table row actions |
| Button destructive | same | Error palette; confirm before submit |
| Text field | `app/views/partials/input.php` | Label-caps above; 1px border; focus 2px teal |
| Select / textarea | `app/views/partials/…` | Match input geometry |
| CSRF field | helper or partial | Required on all state-changing forms |
| Search field | `app/views/partials/search.php` | Toolbar search for tables |

### Planned — Data Display

| Component | Intended path | Notes |
| --------- | ------------- | ----- |
| Status chip | `app/views/partials/status-chip.php` | Near-square radius sm; tinted bg + solid label; all domain statuses |
| Data table | `app/views/partials/data-table.php` (or conventions) | Header label-caps; row border; no zebra; mono for money/IDs |
| Metric tile | `app/views/partials/metric-tile.php` | Dashboard KPI; Level-1 surface + outline |
| Empty state | `app/views/partials/empty-state.php` | Title, help text, optional CTA |
| Error state | `app/views/partials/error-state.php` | User-safe message + retry |
| Loading state | `app/views/partials/loading-state.php` | Skeleton table rows or compact spinner |
| Pagination | `app/views/partials/pagination.php` | Dense, outline style |
| Modal / drawer | `app/views/partials/modal.php` | Level-2 elevation |

### Planned — Domain-Specific

| Component | Intended path | Notes |
| --------- | ------------- | ----- |
| Room tape chart | `app/views/partials/room-tape-chart.php` + `public/assets/js/calendar.js` | Room × date Gantt; solid occupied; diagonal hatch for reserved |
| Reservation row | module view or partial | Guest, room (mono), dates, status chip |
| Arrival / departure card or row | Front Desk views | Queue density over large cards |
| Invoice line table | Billing views | Item type, amount mono, taxes/discounts |
| Folio balance summary | Billing / Front Desk | Outstanding vs paid |
| Housekeeping task row | Housekeeping views | Room, task type, assignee, status |
| Maintenance request row | Maintenance views | Priority chip + status chip |

---

## Built Components

_None yet. Move items from Planned → Built with exact classes when implemented._

<!-- Example row once built:
| Status chip | `app/views/partials/status-chip.php` | `rounded-sm`, tint bg, `text-xs font-semibold`; maps room/reservation/invoice enums |
-->

---

## Screens / Views

Register module views as they ship (path under `app/views/…`).

| Screen | Path | Status | Notes |
| ------ | ---- | ------ | ----- |
| Login | `app/views/auth/login.php` | Planned | Auth layout |
| Dashboard | `app/views/dashboard/index.php` | Planned | Occupancy, arrivals, revenue, balances |
| Reservations index | `app/views/reservations/index.php` | Planned | Table + filters |
| Reservation form | `app/views/reservations/create.php` / `edit.php` | Planned | Availability conflict states |
| Front Desk | `app/views/frontdesk/index.php` | Planned | Align with `front_desk_operations.html` |
| Rooms index | `app/views/rooms/index.php` | Planned | Status filters |
| Room types | `app/views/rooms/types.php` | Planned | Rates, capacity |
| Guests | `app/views/guests/…` | Planned | Search + profile |
| Billing / invoices | `app/views/billing/…` | Planned | Align with `BillingInvoices.html` |
| Payments | `app/views/payments/…` | Planned | Methods + partial pay |
| Housekeeping | `app/views/housekeeping/…` | Planned | |
| Maintenance | `app/views/maintenance/…` | Planned | |
| Expenses | `app/views/expenses/…` | Planned | |
| Staff | `app/views/staff/…` | Planned | Roles assignment |
| Reports | `app/views/reports/…` | Planned | Printable |
| Notifications | `app/views/notifications/…` | Planned | |
| Audit logs | `app/views/audit/…` | Planned | Read-only |
| Settings / backup | `app/views/settings/…` | Planned | |

---

## JS Feature Modules

| Script | Path | Status | Notes |
| ------ | ---- | ------ | ----- |
| Reservations | `public/assets/js/reservations.js` | Planned | Form helpers, conflict UI |
| Calendar / tape | `public/assets/js/calendar.js` | Planned | Tape chart interactions |
| Front desk | `public/assets/js/frontdesk.js` | Planned | Confirm check-in/out |
| Billing | `public/assets/js/billing.js` | Planned | Optional line-item helpers |

---

## Status Chip Catalog

Single component must support these labels (schema-aligned):

**Rooms:** Available · Occupied · Reserved · Cleaning · Maintenance  

**Reservations:** Booked · Checked in · Checked out · Cancelled · No show  

**Invoices:** Draft · Issued · Partially paid · Paid · Void  

**Housekeeping:** Pending · In progress · Completed · Verified  

**Maintenance:** Open · In progress · Resolved · Cancelled (+ priority Low/Medium/High/Urgent)

---

## Notes

- Prefer partials over copy-pasted Tailwind class blocks across modules.
- When a design mock and tokens disagree, prefer `ui-tokens.md` for color/type/radius; use designs for composition density.
- Update `context/progress-tracker.md` when a feature ships; update **this** file when a reusable partial ships.
)
