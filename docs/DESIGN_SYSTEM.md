# InternMatch Design System

The rules every redesigned page follows. Tokens live in `frontend/src/styles.css`; shared components in `frontend/src/components/im/ui.tsx`, `ds.tsx` and `charts.tsx`; status colours in `frontend/src/lib/status.ts`.

**Colours are the system's existing colours.** The redesign changes layout, components, states and charts, not the palette. No colour value in `styles.css` was changed.

## 1. Principles

1. Each screen answers "what is the current status?" and "what do I do next?".
2. Every number comes from an existing role-scoped endpoint. No sample values, invented trends or generated commentary.
3. Plain, specific university language in sentence case. No emojis, no hype, no exclamation marks.
4. Icons (Lucide only) appear where they carry meaning: navigation, KPI cards, icon-only buttons. Not on every heading.
5. Quiet surfaces: white cards on a light grey page, one maroon header bar. No gradients or glow effects on application screens.

## 2. Colour (existing tokens)

| Role | Token | Light value |
|---|---|---|
| Brand (UM maroon) | `--brand` | `oklch(0.35 0.11 18)` |
| Brand soft | `--brand-soft` | `oklch(0.955 0.018 20)` |
| Page background | `--background` | `oklch(0.968 0.003 250)` |
| Card | `--card` | white |
| Text | `--foreground` | `oklch(0.22 0.01 260)` |
| Secondary text | `--muted-foreground` | `oklch(0.53 0.015 258)` |
| Border | `--border` | `oklch(0.915 0.006 255)` |
| Success | `--success` / `--success-soft` | `oklch(0.48 0.11 165)` / `oklch(0.95 0.03 165)` |
| Warning | `--warn` / `--warn-soft` | `oklch(0.62 0.13 70)` / `oklch(0.96 0.045 85)` |
| Info | `--info` / `--info-soft` | `oklch(0.55 0.13 250)` / `oklch(0.95 0.03 250)` |
| Destructive actions | `--destructive` | `oklch(0.55 0.2 27)` |
| Navigation rail (collapsed) | fixed values | `#e8eff8`, active `#d2e3fc`, text `#17385e` |

Dark mode uses the existing `.dark` token set (already defined in `styles.css`). The theme menu in the header offers Light, Dark and Match device; the choice is stored per browser and applied before the first paint (`lib/theme.ts`).

## 3. Status colours

One mapping, `statusTone()` in `lib/status.ts`, used by badges (`Pill`) and charts (`TONE_COLOR`), so a chart segment always matches its badge. The rules are the existing ones:

| Tone | Statuses | Badge | Chart colour |
|---|---|---|---|
| success | approved, active, verified, deployed, on track, submitted, published, completed | green on light green | `--success` |
| warn | pending, draft, expiring, under review, flagged | amber on light amber | `--warn` |
| brand (problem) | missing, disabled, at risk, closed, failed, unplaced, expired, terminated | maroon on light maroon | `--brand` |
| info | used explicitly for "in progress" chart parts | blue on light blue | `--info` |
| muted | anything else | grey | `--muted-foreground` |

Badges always show a text label (and a coloured dot), so status is never conveyed by colour alone. `"danger"` is accepted as an alias of the brand problem tone.

## 4. Charts

- Library: Recharts 3.10.1. Components: `BarChartView` (vertical, horizontal, stacked), `LineChartView`, `DonutChartView`, all inside `ChartCard`.
- Chart type by data: bar to compare categories, stacked bar for parts across categories, line for change over time (only with real dated data), donut for one share split of 2 to 5 parts. No pie charts.
- Every chart: title, axis labels with units, legend when there is more than one series, exact-value tooltip, and a screen-reader table with the same numbers.
- Series colours: the existing `--chart-1` to `--chart-5`. Status charts use the status colours above.
- States: `ChartCard` shows a skeleton while loading, an empty state that says why there is no data, and an error state with Retry.
- Charts resize with their container; animation is off when the user prefers reduced motion.

## 5. Typography, spacing, shape

- Font: Public Sans (already loaded). Sizes: page title 24 px semibold; section title 16 px semibold; card and chart title 14 px semibold; body 14 px; captions 12 px. KPI values 30 px semibold with tabular figures. No all-caps labels.
- Spacing: 4 px grid. Page padding 16 / 24 / 32 px (mobile / tablet / desktop); 32 px between dashboard sections; 16 px between cards; 20 px card padding.
- Radius: 8 px controls and badges' containers, 12 px cards and dialogs, full for badges and avatars.
- Elevation: cards use a 1 px hairline shadow; menus a medium shadow; dialogs a large shadow.
- Motion: 150 to 200 ms for menus, dialogs and the sidebar; disabled entirely under `prefers-reduced-motion`.

## 6. Components

| Component | File | Use |
|---|---|---|
| `AppShell` | `AppShell.tsx` | Maroon header (breadcrumb, theme menu, chat, notifications, account menu); left navigation with icons, collapsible to an icon rail on desktop (remembered per browser), drawer on mobile; skip link |
| `PageHeader` | `ui.tsx` | Page title, one-line description, actions |
| `SectionHeading` | `ds.tsx` | Section title inside a page |
| `Card`, `CardTitle` | `ui.tsx` | Content container |
| `Button` | `ui.tsx` | Variants primary, outline, ghost, danger; sizes md, sm |
| `IconButton` | `ds.tsx` | Icon-only action with accessible name and tooltip |
| `Pill` | `ui.tsx` | Status badge (see section 3) |
| `KpiCard` | `ds.tsx` | One key number; links to its list when a route exists; no trend unless real history exists |
| `ActionList` | `ds.tsx` | "Needs your attention": rule-based items with real counts, each linking to the page that resolves it |
| `Modal` | `ui.tsx` | Centred, at most viewport height, scrolls inside, closes on Escape / close button / backdrop, returns focus |
| `ConfirmDialog` | `ui.tsx` | Destructive or irreversible actions; the confirm button names the action |
| `ActionDialog` | `ui.tsx` | Simple form in a modal |
| `Table`, `Row` | `ui.tsx` | Tables scroll horizontally on small screens |
| `EmptyState`, `ErrorState`, `LoadingBlock`, `Skeleton` | `ds.tsx` | Empty, error and loading states |
| Charts | `charts.tsx` | See section 4 |

## 7. Dashboard layout

Top to bottom: (1) Needs your attention, (2) 4 to 6 KPI cards, (3) 2 to 4 charts, (4) a table of recent or relevant records with a link to the full page. Each block loads independently, so one failing endpoint does not blank the page.

## 8. Writing

- Name things by what the user does: "Placement status", "Open placement slots".
- State facts with real counts: "3 requirement submissions awaiting review".
- Empty states say why and what happens next: "Placements appear here after you approve a proposal."
- Errors say what failed and offer Retry; they do not apologise.
- No emojis anywhere (UI, code comments, logs, documentation, commit messages).

## 9. Administration and the Filament panel

- The administration shell (`admin-reference-shell.blade.php`) uses the same tokens as the portal, written as CSS variables on the shell root, with the same light and dark values. The header stays UM maroon in both modes.
- The theme choice is stored under the same key as the portal (`internmatch:theme`) and is shared with the embedded workspace through the storage event.
- Filament's own screens (for example Student Enrollments) take their colour from the maroon primary palette in `ManagementPanelProvider`, the Public Sans font, and `public/css/manage-theme.css`. They follow Filament's own light and dark switch.

## 10. Modals

All modals come from `components/im/ui.tsx`. Do not use a native `<dialog>` or build an overlay by hand.

| Component | Use for | Footer |
|---|---|---|
| `FormModal` | Any form that creates or edits a record. Wraps a `<form>`; keeps browser validation. | Cancel and a primary submit button |
| `ViewModal` | Read-only details | Close, plus optional actions |
| `ConfirmDialog` | A yes or no decision, especially destructive. The title or description must name the item. | Cancel (focused first) and the confirm button |
| `Modal` | Anything else, such as a multi-action decision or a progress dialog | Whatever `footer` provides |

**Sizes.** `sm` 400 px for confirmations and one or two fields. `md` 560 px for most forms. `lg` 880 px for long forms, many fields or wide read-only content. Below 640 px every size is a full-screen sheet with the header and footer fixed and the body scrolling.

**Form fields.** Use `FormField` (label above the control, required marker, optional helper text), `FormSection` for grouped fields and `FormGrid` for two columns of short fields. Give every control a `name` equal to the API field. For repeated groups use the dotted key Laravel returns (`capacities.0.capacity`). For a group of checkboxes put `data-field="<api field>"` on the wrapper so the error appears under the group.

**Errors.** Throw the caught error through `toFormError(cause, fallback)` and pass the result to `FormModal`. Field messages from a 422 response appear under the matching control and the first one is focused; the rest go in a summary at the top. `ApiError.errors` in `lib/api.ts` carries the field map.

**Behaviour.** The primary button is disabled and shows `busyLabel` while saving. Close the modal, call `toast.success("...")` and refresh the list on success. Pass `dismissible={false}` while a long action runs. The backdrop closes only `ViewModal` and `ConfirmDialog`. Esc and the close button show "Discard changes?" when a form was edited.

**Wording.** Sentence case. Name the item: "Edit MOA for Acme Corp", "Remove Communication?". Use a Lucide icon for the opener instead of "+". No exclamation marks.

**Action helpers.** `components/im/actions.tsx` wraps the two most common patterns so pages do not rebuild them:

- `ConfirmButton` for an action that changes data with no input (approve, certify, send, archive, restore, delete, enable, disable, sign out). Name the item in `title`, say what happens and whether it can be undone in `description`, and set `confirmLabel` to the action ("Approve", "Archive"), never "OK". Use `tone="danger"` only for destructive or hard-to-undo actions. `onConfirm` throws on failure; the error stays in the modal.
- `ReasonButton` for reject, return and withdraw. The reason is required and sent as `field` (for example `comments` or `notes`), so a Laravel error for it appears under the textarea.

Do not use a modal for search, filters, sorting, tabs, pagination, navigation, the "Include synthetic data" switch or the "Show archived" switch.

**Accessibility.** The panel has `role="dialog"`, `aria-modal` and `aria-labelledby`. Focus moves in, is trapped and returns to the opener. Animations are disabled under `prefers-reduced-motion`.
