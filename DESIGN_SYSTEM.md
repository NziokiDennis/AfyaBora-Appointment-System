# AfyaBora Design System

Benchmark reference: the patient dashboard in `patients/dashboard.php` + `patients/sidebar.php` (built to match an external "Medora Health" reference dashboard, minus its pink accent). Every new or restyled page must match this file — if a page needs something not listed here, add it here first, then use it, so we don't drift page to page.

## 1. Color tokens

Defined once as CSS custom properties at the top of every page's `<style>` block (or a shared stylesheet once one exists). Never hardcode a hex value inline outside this list.

**These values are pixel-sampled directly from the Medora Health reference screenshots (not eyeballed/approximated)** — averaged over multiple clean sample points per color to cancel out JPEG noise. Treat them as exact; don't "round" them back toward AfyaBora's old palette.

| Token | Value | Use for |
|---|---|---|
| `--navy` | `#002d70` | Sidebar background, primary buttons, headings, page title text |
| `--navy2` | `#134589` | The reference's *mid-tone* blue — sidebar active nav item background, selected date/calendar tile background |
| `--blue` | `#0b63c3` | The reference's *bright* interactive blue — active tab fill, outline-button border/text, links, focus rings |
| `--blue2` | `#094f9e` | Hover/pressed state of `--blue` and of navy primary buttons |
| `--sky` | `#eef3fb` | Icon-chip backgrounds, light tinted panel backgrounds |
| `--canvas` | `#f5f6fa` | Page background (behind white cards) |
| `--white` | `#ffffff` | Card backgrounds |
| `--border` | `#e8ebf0` | Card borders, dividers, table row separators |
| `--muted` | `#767b80` | Secondary/supporting text (meta info, labels, timestamps) |
| `--text` | `--navy` | Primary body/heading text color (reuse `--navy`, don't use pure black) |
| `--green` | `#1fae7a` | Success states only (paid, completed, checkmarks, progress bars) |
| `--amber` | `#f59e0b` | Pending/warning states only (not present in the sampled crops — kept as a standard semantic amber) |
| `--rose` | `#dc2626` | Danger/destructive states and required-field markers only (not present in the sampled crops — kept as a standard semantic red) |

Rules:
- There are genuinely **three blues** in the reference, not two — verified by pixel sampling, not a guess: `--navy` (structure: sidebar, headings, primary button fill), `--navy2` (a mid-tone used specifically for "this is selected/active" fills — the highlighted sidebar row, the picked date tile), and `--blue` (the brightest tone, reserved for things you'd click right now — active tab, link text, outline-button accent). Keep the three distinct; don't collapse `--navy2` into `--blue` or vice versa.
- `--green` / `--amber` / `--rose` are **semantic only** — never decorative. If a badge isn't communicating success/pending/danger, it doesn't get one of these colors.
- No pink, purple, or other accent colors anywhere. The reference dashboard used one pink accent icon; we deliberately drop it. If a page needs a one-off visual accent, ask before adding a new token — don't invent one inline.

## 2. Typography

- **One font family everywhere**: `Plus Jakarta Sans` (Google Fonts), weights 400/500/600/700/800. Headings and body text are the same family, differentiated by weight and size only — never mix in a second family on the same page.
- Load once per page:
  `<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">`
- Scale:
  | Role | Size | Weight | Color |
  |---|---|---|---|
  | Page title (e.g. "Pregnancy Tracking") | 1.7rem | 700 | `--navy` |
  | Section heading (e.g. "Your Doctor") | 1.05rem | 700 | `--navy` |
  | Card title / list item name | 0.95rem | 600 | `--navy` |
  | Body text | 0.875rem | 400 | `--navy` |
  | Meta/secondary text (dates, labels) | 0.78rem | 400–500 | `--muted` |
  | Big stat number (e.g. week counter equivalent) | 1.5–2rem | 700–800 | `--navy` or `--white` on dark |
- Never use pure black (`#000`) or the browser default serif/system font.

## 3. Spacing & shape

- Card padding: 20–24px.
- Gap between sibling cards: 16–20px.
- Border radius: `--radius-sm: 8px` (small buttons, inputs), `--radius-md: 12px` (buttons, tabs, list rows), `--radius-lg: 16px` (cards, panels), `--radius-pill: 999px` (status badges, avatar circles).
- Shadows are minimal: cards use a 1px `--border` outline plus at most a very soft shadow (`0 1px 3px rgba(10,22,40,.04)`). Don't use heavy drop shadows.

## 4. Layout: sidebar app shell

Every logged-in role (patient done; doctor/admin/receptionist to follow) uses this shell:

- **Fixed left sidebar**, `--navy` background, ~250px wide:
  - Brand/logo at top.
  - Nav items: icon + label, `.nav-item`. Active/current page gets a `--navy2` background pill. Hover gets a lighter `--navy2` tint.
  - A self-contained widget card pinned near the bottom of the sidebar (e.g. Profile Completion) — background `--navy2`, rounded, matches the reference's bottom widget pattern. Only put **real, data-driven** content here, never a decorative placeholder.
  - Sign out link at the very bottom.
- **Top bar**, white background, sits beside the sidebar (not full width):
  - Left: greeting or page breadcrumb.
  - Right: primary CTA button (`--navy` solid) if the page has one obvious primary action, then a user identity chip (avatar initials + name).
- **Main content**: `--canvas` background. Every page opens with a large page title (see §2 scale) + one-line subtext, directly in the content area — never rely on the topbar greeting alone to establish what page you're on. Where there's a natural "primary content + persistent context" split (e.g. dashboard), use a **~2/3 main + 1/3 right-rail** grid (right rail ~380px), matching the reference. Otherwise single column.
- No marketing footer inside the app shell (the sidebar layout is a self-contained app, not a marketing page).
- **Centering a lone card means centering on the real screen, not on the leftover strip beside the sidebar.** `margin: 0 auto` on a card inside `.ab-content` only balances the space *after* the fixed sidebar — verified by pixel-measuring a real screenshot, that put the card ~128px right of the window's actual center on a 1920px display, which reads as visibly off-center. Use the `.ab-center-viewport` utility class (defined in `sidebar.php`) on any page with a single centered card instead of writing a page-specific `margin: auto` rule. If you add a differently-sized lone-card page, don't reuse `.ab-center-viewport` as-is (it's hardcoded to a 620px card) — extend it or make a sibling utility, and verify the fix the same way: screenshot at a real width and pixel-measure both gaps, don't eyeball it.
- **One sidebar per role, no top navbar, ever.** Every logged-in page for a role includes that role's `sidebar.php` (e.g. `patients/sidebar.php`) and nothing else for navigation — the old Bootstrap `navbar.php` per role is deprecated. Never add a `<nav>`/navbar to a page that also has the sidebar, and never build a new page against the old navbar pattern, even to "match" an unmigrated page — migrate forward, not backward. `navbar.php` files stay in the repo only until every page in that role has been migrated to the sidebar shell, then get deleted; until that happens, a role can have both patterns present across different files, but never both on the *same* page. Track migration status here: **Patients** — `dashboard.php` ✅, `book_appointment.php` ✅, `medical_history.php` ✅, `feedback.php` ✅, `update_profile.php` ✅, `payment.php` ✅, `receipt.php` ✅ (standalone printable document by design, not a sidebar page — restyled to the palette/font but intentionally has no sidebar), `cancel_appointment.php` ✅ (no view, pure redirect handler — nothing to restyle, just got the role-guard + an ownership-check security fix). All patient pages done. **Doctors** — `dashboard.php` ✅, `appointments.php` ✅, `add_medical_record.php` ✅ (also got an ownership-check IDOR fix — see §7 note below), `add_lab_result.php` ✅ (same IDOR fix), `medical_history.php` ✅, `schedule.php` ✅ (also switched two raw-interpolated `DELETE` queries to prepared statements), `view_feedback.php` ✅, `delete_medical_record.php` ✅ (no view, pure redirect handler, already had an ownership check — nothing to restyle). All doctor pages done; `doctors/navbar.php` is now unused and can be deleted. **Receptionist** — `dashboard.php` ✅, `payments.php` ✅, `pharmacy.php` ✅. All receptionist pages done; `receptionist/navbar.php` is now unused and can be deleted. **Admin** — not started.
- **Fill the layout with real data, not whitespace.** The reference is visually dense — calendar strip, two info cards, a results list, a doctor card, a next-appointment block, an image gallery, all stacked. Don't stop at one card per column if there's real, queryable data (counts, recent records, next event) that can fill the space meaningfully. An empty right rail under one card reads as unfinished, not minimal.

## 5. Component patterns

- **Icon chip**: small rounded-square (`--radius-md`), `--sky` background, `--blue` icon, ~34–42px. Reused everywhere an icon needs a container — list rows, info cards, sidebar bottom widget checklist.
- **List row** (e.g. appointments, results, records): icon chip on the left → a flexible body block containing one name line (bold primary text, `--navy`, 600, with any tag/status pills placed *inline* right next to the name — never on their own line) and one muted meta line beneath it (date, doctor, reference, etc.) → a trailing element pinned to the row's far right and vertically centered (either a compact button group for multi-action rows, or a single icon/chevron for single-action "view" rows). Keep the row to two visual lines of text; if there are multiple actions, group them tightly in the trailing column rather than letting the row grow a third stacked line. Rows are separated by a `--border` bottom rule inside one shared card, not individually boxed.
- **Status pill**: rounded-pill, small (0.7rem), colored per semantic token (`--green`/`--amber`/`--rose` backgrounds at ~15% opacity with full-strength text/icon). Use only for a true status (paid/pending/failed, etc.).
- **Neutral tag** (e.g. a doctor's specialty next to their name, a record category): rounded-pill, `--border` outline, no fill, `--muted` text, small (0.68rem). Use for descriptive/categorical labels that aren't a status — never colored.
- **Profile/entity card** (e.g. "My Profile", "Your Doctor"): avatar circle (initials on `--blue`/`--navy` gradient or photo) + name (600, `--navy`) + one or two muted meta lines + divider + supporting block (e.g. next appointment) + one primary + one secondary button pinned at the bottom.
- **Buttons**:
  - Primary: solid `--navy` background, white text, `--radius-sm`/`--radius-md`, hover → `--blue2` or a slightly lighter navy.
  - Secondary: white background, `--blue` border + text, same radius, hover → `--sky` background.
  - Danger: reserve for destructive-only actions (cancel, delete), `--rose`.
- **Tabs/segmented control**: pill-shaped buttons in a row; active = solid `--blue` fill + white text; inactive = white background + `--border` outline + `--navy` text.
- **Stat tile row**: 2–4 equal-width cards in a horizontal grid directly under the page title, each an icon chip + big bold number (`--navy`, ~1.5rem/800) + muted label underneath. Use for glanceable counts (upcoming/completed/records-on-file, etc.) — always real counts from the database, never illustrative numbers.
- **Highlight/next-event block** (e.g. "Your Next Appointment"): a compact date box (`--sky` background, `--radius-md`, big bold day-of-month + small uppercase `--blue` month underneath) sitting beside the event's key details (name, category/specialty, time). Used to surface the single most relevant upcoming record separately from its full list.
- **Form controls**: defined once in each role's `sidebar.php` (so every page that includes it gets them for free) — `.ab-form-group` (field wrapper, 18px bottom margin), `.ab-form-row` (2-column grid for pairing short fields like date+time, collapses to 1 column under 640px), `.ab-label` (600 weight, `--navy`, with a `.req` span in `--rose` for the `*`), and `.ab-input` applied to every `<input>`/`<select>`/`<textarea>` (white bg, `--border` outline, `--radius-sm`, `--blue` glow on focus). Never use raw unstyled `<input>`/Bootstrap `.form-control` on a new page — always `.ab-input`. Inline JS-driven hints/warnings use `.ab-info-banner` (persistent, e.g. live availability text) or `.ab-field-error` (validation-only, toggled via the `hidden` attribute, not `display` — pairing `hidden` with a class that also sets `display` needs an explicit `[hidden]{display:none}` override, already handled in `sidebar.php`).

## 6. Process rule

Before styling a new page, check this file first. If the page needs a component or color not listed here, decide it once, add it to this file, then build — don't improvise a one-off style inline. This file is the single source of truth for what AfyaBora's UI is supposed to look like; individual pages should never invent their own palette or font again.

## 7. Data integrity for role-joined rows

A redesign once surfaced "Dr. Muema Ngei" on a patient's own dashboard — Muema Ngei is a patient, not a doctor. The row wasn't a display bug; a legacy seed row (`appointments.appointment_id = 4`) had `doctor_id` pointing at a patient's own `user_id`. The old, plainer UI never made this obvious; the redesigned card made the wrong name look authoritative and easy to notice — which is how it got caught, but it's exactly the kind of thing a nicer layout can also paper over if we're not careful.

Rules going forward:
- Any query that joins a `*_id` column meant to reference a specific role (`doctor_id` → a doctor, `patient_id` → a patient, etc.) should filter on that role explicitly at the query level (e.g. `JOIN users u ON a.doctor_id = u.user_id AND u.role = 'doctor'`) rather than trusting the foreign key alone. A row with bad legacy data should disappear or visibly error, not silently render under someone else's name.
- When redesigning a page that shows real data, don't just eyeball the layout — pull up the actual rows the page is querying (or query them directly) and sanity-check that the values make sense (right role, right person, right status) before calling the redesign done. A component looking clean is not the same as the data being correct.
- If something looks off while reviewing a redesigned page (a name, a role, a number that doesn't add up), stop and check the underlying data before assuming it's a styling artifact.

Related but distinct: while redesigning `doctors/add_medical_record.php` and `doctors/add_lab_result.php`, both looked up an `appointment_id` from the URL without checking it belonged to the logged-in doctor (`WHERE a.appointment_id = ?` with no `AND a.doctor_id = ?`) — any logged-in doctor could add or edit another doctor's medical record or lab result by guessing/incrementing the ID in the URL. This is the same ownership-check gap as the `cancel_appointment.php` IDOR fixed on the patient side: whenever a page trusts an ID from `$_GET`/`$_POST` to fetch a row, check that the row also belongs to the current session's user, not just that the ID exists. Both are now fixed with an explicit `AND a.doctor_id = ?` bound alongside the appointment ID.

## 8. Safe testing practice (never use real accounts as test fixtures)

While verifying redesigned pages against real data, real doctor accounts got used as scratch space repeatedly: insert a schedule for `doctor_id=5` (Tony Mutunga, a real doctor) to test booking, then clean up with `DELETE FROM doctor_schedules WHERE doctor_id=5`. That delete isn't scoped to what was inserted — it wipes *everything* for that doctor. Done once, it silently erased his real schedule with no backup and no way to reconstruct it. It then happened *again* on the same account after being caught and explicitly flagged as a problem, before this rule existed to stop it.

Rules going forward, no exceptions:
- **Never insert, update, or delete rows against a real account's ID** (a real doctor, a real patient, an admin) to verify a page, even temporarily, even with a "cleanup" step planned. If a page needs a doctor with a schedule to render/test correctly, create a disposable doctor first (obviously-fake name/email, e.g. `Design QA Doctor` / `qa-doctor-<timestamp>@example.com`), give *that* account the schedule, test against it, then delete *that whole account* (cascades take the schedule with it). Same for patients.
- **Cleanup must be scoped to exactly what you inserted**, identified by the specific IDs captured at insert time (`$conn->insert_id`, or a row you just selected) — never a broad `WHERE <foreign_key> = <shared id>` delete, because that also deletes anything that was already there before you touched it.
- Before reusing any account ID for a test, ask: do I actually know this ID is a disposable one I created, or am I assuming it's fine because it happened to work last time? If there's any doubt, create a fresh disposable account instead of checking.
- This applies to every table, not just `doctor_schedules` — appointments, medical_records, feedback, payments, users themselves. The instinct of "I'll put it back after" is exactly what failed here, twice.
