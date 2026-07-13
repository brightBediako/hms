---
name: Hospitality Command
colors:
  surface: '#f8faf7'
  surface-dim: '#d8dbd8'
  surface-bright: '#f8faf7'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f2f4f1'
  surface-container: '#eceeec'
  surface-container-high: '#e7e9e6'
  surface-container-highest: '#e1e3e0'
  on-surface: '#191c1b'
  on-surface-variant: '#3f4945'
  inverse-surface: '#2e3130'
  inverse-on-surface: '#eff1ef'
  outline: '#707975'
  outline-variant: '#bfc9c4'
  surface-tint: '#29695b'
  primary: '#00342b'
  on-primary: '#ffffff'
  primary-container: '#004d40'
  on-primary-container: '#7ebdac'
  inverse-primary: '#94d3c1'
  secondary: '#7e5700'
  on-secondary: '#ffffff'
  secondary-container: '#feb300'
  on-secondary-container: '#6a4800'
  tertiary: '#4e2013'
  on-tertiary: '#ffffff'
  tertiary-container: '#693527'
  on-tertiary-container: '#e89f8c'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#afefdd'
  primary-fixed-dim: '#94d3c1'
  on-primary-fixed: '#00201a'
  on-primary-fixed-variant: '#065043'
  secondary-fixed: '#ffdeac'
  secondary-fixed-dim: '#ffba38'
  on-secondary-fixed: '#281900'
  on-secondary-fixed-variant: '#604100'
  tertiary-fixed: '#ffdbd1'
  tertiary-fixed-dim: '#ffb5a1'
  on-tertiary-fixed: '#370e04'
  on-tertiary-fixed-variant: '#6d382a'
  background: '#f8faf7'
  on-background: '#191c1b'
  surface-variant: '#e1e3e0'
typography:
  display-lg:
    fontFamily: Inter
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
    letterSpacing: -0.01em
  title-sm:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '600'
    lineHeight: 24px
  body-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  body-sm:
    fontFamily: Inter
    fontSize: 13px
    fontWeight: '400'
    lineHeight: 18px
  label-caps:
    fontFamily: Inter
    fontSize: 11px
    fontWeight: '700'
    lineHeight: 16px
    letterSpacing: 0.05em
  data-mono:
    fontFamily: JetBrains Mono
    fontSize: 12px
    fontWeight: '500'
    lineHeight: 16px
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  sidebar_width: 240px
  topbar_height: 56px
  container_margin: 24px
  cell_padding_v: 8px
  cell_padding_h: 12px
  stack_gap: 16px
---

## Brand & Style

The design system is engineered for the high-stakes environment of property management. The brand personality is **authoritative, precise, and reliable**, prioritizing utility over decoration. It targets professional hoteliers and operations staff who require immediate access to complex data.

The style is **Corporate Modern**, drawing heavily from fintech and enterprise SaaS patterns. It emphasizes a "single source of truth" through high information density, strict alignment, and a sober aesthetic that mirrors the seriousness of a banking interface. The emotional response should be one of competence and control—reducing the cognitive load of a busy front desk through systematic clarity.

## Colors

The palette is anchored by **Deep Teal (#004D40)** to establish trust and professional depth. **Warm Gold (#FFB300)** is used sparingly for primary actions (e.g., "Check-In", "New Reservation") to provide a high-contrast focal point without sacrificing the professional tone.

Status colors are mission-critical in this design system. They use a slightly desaturated "Pro" spectrum to ensure they remain legible within dense tables and Gantt charts without causing visual fatigue. Neutral tones favor cool grays to keep the interface feeling crisp and clinical.

## Typography

**Inter** is the primary typeface, chosen for its exceptional legibility in small-scale, data-heavy environments. The scale is intentionally compact; `body-sm (13px)` is the standard for data tables and sidebars to maximize vertical information density.

A secondary typeface, **JetBrains Mono**, is introduced for numerical data—specifically room numbers, rates, and folio IDs—to ensure digits align perfectly in columns, facilitating rapid scanning of financial and inventory data.

## Layout & Spacing

The layout utilizes a **Fixed Sidebar / Fluid Content** model. The 240px persistent sidebar contains the primary navigation, while the main stage utilizes a 12-column fluid grid. 

To achieve high density, the spacing rhythm follows a tight 4px baseline. Table rows are constrained to a `32px` or `40px` height, using `cell_padding_v: 8px` to maintain a professional look while showing 20+ rows per screen. Gutters are kept at a consistent `16px` to maximize screen real estate.

## Elevation & Depth

This design system avoids heavy shadows, favoring **Tonal Layers** and **Low-Contrast Outlines** to preserve the "flat" professional aesthetic. 

- **Level 0 (Base):** Background / surface (`#f8faf7`).
- **Level 1 (Cards/Tables):** White surface (`surface-container-lowest`) with a 1px `outline-variant` border (`#bfc9c4`).
- **Level 2 (Dropdowns/Modals):** White surface with a crisp 1px border and a subtle 4px blur ambient shadow (0, 2, 4, rgba(0,0,0,0.05)).

Interactive states are indicated by subtle background shifts (e.g., a row highlight using a 2% primary color tint) rather than depth changes.

## Shapes

The shape language is **Soft (0.25rem)**. This provides a subtle modern touch that softens the "grid-heavy" nature of a PMS without feeling overly casual or "bubbly." 

- Standard components (Inputs, Buttons): `4px` radius.
- Large containers (Cards, Modals): `8px` radius.
- Status Chips: `2px` radius (nearly square) to maintain the technical, high-density feel.

## Components

### Buttons
- **Primary:** Deep Teal background with White text. Bold, 13px weight.
- **Secondary/Action:** Warm Gold background with Black text. Used only for "conversion" actions (Check-in, Save).
- **Ghost:** Borderless with Deep Teal text, used for secondary table actions.

### Data Tables
Tables are the core of the system. Use `data-mono` for all currency and ID columns. Headers should be `label-caps` with a light gray background (#F1F3F5). Zebra striping is not used; instead, use a 1px bottom border on rows.

### Status Chips
Small, rectangular badges with a subtle tinted background (15% opacity of the status color) and a 100% opacity text color. No borders.

### Input Fields
Strict, rectangular fields with 1px borders. Focused state uses a 2px Deep Teal border with no outer glow. Labels are always positioned above the input in `label-caps`.

### Room Grid (Gantt)
The specialized "Tape Chart" component uses a dense grid where each cell represents a room/date. Occupied cells are solid blocks of the status color, while "Reserved" blocks use a diagonal hatched pattern to distinguish from checked-in guests.