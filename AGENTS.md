# E-Office Incoming Letters – Copilot Agent Guide (Updated 2025-10-19)

## Project Structure (Current & Planned)
```
app/
  Http/
    Controllers/
    Middleware/
    Requests/
  Models/
    User.php
    IncomingLetter.php
    Disposition.php (planned status transitions)
  Services/
    Integrations/
      ArchiveApiClient.php
      WhatsappClient.php
  Actions/ (planned)
  Jobs/
    SendWhatsappMessageJob.php
  Support/
    helpers.php
bootstrap/
config/
database/
  migrations/
  factories/
  seeders/
public/
resources/
  views/
  lang/
routes/
  web.php
storage/
tests/
vendor/
```

## Technology Used
- PHP 8.2, Laravel 12
- AdminLTE for admin UI
- Queue (database driver) for WhatsApp sending
- Formatter: `laravel/pint`
- Auditing: `owen-it/laravel-auditing`
- Permissions: `spatie/laravel-permission`
- DataTables: Yajra
- Recommended (not yet): Larastan for static analysis

## Domain Overview
- Single active role per user. Roles:
  - superadmin: all permissions, system management.
  - admin: users/employees/grades/work units, create/edit/delete/reject/archive letters (no dispose).
  - pimpinan: dispose/reject/archive letters; create/complete/reject dispositions.
  - staff: view letters; claim & follow up dispositions.
- Permissions use dot notation (`user.view`, `incoming_letter.dispose`, `disposition.claim`).
- Letter lifecycle (target): new → disposed → followed_up → completed → archived | rejected (can redispose).
- Dispositions represent routing/instruction, created via WhatsApp interactive messages (unit or individual employee). Claim process ensures first employee wins (atomic update).

## WhatsApp Flow Implemented
1. Letter creation sends template to pimpinan (variables: number, sender, subject, date, link).
2. Multi-letter tracking + SWITCH command for context.
3. BANTUAN command lists pending letters.
4. Disposition creation via interactive list (unit or employee).
5. Claim button (AMBIL) for employees; first claim stored.
6. Notes collection for disposition, archive, rejection.
7. Rate limiting (switch/help/claim/note) fixed window.

Pending WA Enhancements
- Claim feedback message (success/failure) & update disposition status to Received + received_at.
- Follow-up & completion commands/buttons (FOLLOWUP, SELESAI).
- Redispose command for rejected letters.
- Observer-based automatic letter status timestamps.
- Template registry & validation.

## Profile Feature
- Profile page: user & employee data editable + password change (current_password checked).
- Automatic employee creation if missing on profile update.

## Seeders
- RolesAndPermissionsSeeder (roles & permissions).
- GradesSeeder (golongan set).
- UsersAndEmployeesSeeder (baseline users per role + staff employees).

## Integration & Logging
- Archive sync (synchronous) during create/update letter file.
- WhatsApp sending via queued job with retry & logging.
- Integration logs persisted (model/UI still pending).

## Gaps / Next Steps
1. Automated disposition → letter status transitions (disposed_at, followed_up_at, completed_at, archived_at).
2. Follow-up & completion WA actions.
3. Claim feedback UX & status update (disposition received_at).
4. IntegrationLog model + UI.
5. Async Archive send (queue + retry) to detach latency.
6. Factories for IncomingLetter & Disposition (testing support).
7. Feature & unit tests (CRUD, WA flows, profile, permissions).
8. Larastan + CI for static analysis.
9. Localization expansion (users, roles, permissions, letters, dispositions, profile flashes).
10. Advanced rate limiting (sliding window, token bucket) + user-friendly messages.
11. Redispose command after rejection.
12. Consistent permission check usage (`can()` vs `hasPermissionTo()` standardize to `can`).

## Disposition History (Planned Addition)
- Letter detail view will display a table of dispositions (sequence, target, status, timestamps, instruction/note).
- Purpose: Transparency & audit quick view.

## Flow Coverage Summary
Implemented: create letter, archive sync (sync), WA notify pimpinan, disposition create (unit/employee), claim atomic, notes (disposition/reject/archive), multi-letter switch, help, rate limiting, profile management, seeders baseline.
Missing: follow-up/completion path, automated status timestamps, integration log UI, tests, factories, advanced rate limits, localization completeness.
Risk: Manual lifecycle updates can cause inconsistent timestamps; no tests increases regression risk.

## Status Enums
IncomingLetterStatus: new, disposed, followed_up, rejected, completed, archived.
DispositionStatus: new, sent, received, rejected, followed_up, completed.

## Security Notes
- Private storage for scans.
- Token & secrets not logged.
- Permissions enforced; role-specific capabilities defined.

## Recommended Immediate Actions
1. Add observers for first disposition (set disposed_at) & archive (archived_at).
2. Implement claim feedback & disposition received status.
3. Add disposition history partial to letter show view.
4. Introduce basic feature tests.

## Dashboard Feature (Added 2025-10-23)
Path: `/` now served by `DashboardController@index`.

### Metrics Displayed
- Total Letters & per status (new, disposed, followed_up, completed, archived, rejected).
- Total Dispositions & per status (received, followed_up, completed).
- Recent 8 letters (number, sender, subject, status, received date) with quick link.
- 7-Day Letter Throughput (simple list; candidate for sparkline chart later).

### View File
`resources/views/dashboard/index.blade.php` – uses AdminLTE small-box components for KPI cards.

### Future Enhancements
1. Replace throughput list with chart.js mini line chart.
2. Add role-based quick actions (Create Letter for admins, Disposition for pimpinan, Pending Claims for staff).
3. Include failed integration logs count & retry shortcut.
4. Add SLA indicators (if deadlines added later).
5. Cache metrics (e.g., `cache()->remember('dashboard.metrics', 30, fn()=> ...)`) to reduce query load.
6. Permission-aware visibility (hide sections user lacks permission for).
7. Add disposition aging (average hours from sent → received → completed).
8. Internationalize remaining static labels (e.g., box headings if not yet wrapped).

Styling Update (2025-10-23): Initially added custom gradient metric cards; reverted to native AdminLTE `small-box` components for consistency and lighter maintenance. Sparkline placeholder still present for future Chart.js integration.

### Potential Queries Optimization
Current per-status `COUNT` queries could be consolidated using a single `GROUP BY status` if performance becomes an issue (letters & dispositions).

---

---
Updated on 2025-10-19.
