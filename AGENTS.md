# E-Office Incoming Letters – Copilot Agent Guide (Updated 2025-10-24)

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
      ArchiveApiClient.php (stub + direct remote upload now used inline in controller)
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
- Claim feedback message (success/failure) & update disposition status to Received + received_at (Received status field present; UX message still pending).
- Follow-up & completion commands/buttons (FOLLOWUP, SELESAI).
- Redispose command for rejected letters.
- Observer-based automatic letter status timestamps (disposed_at, followed_up_at, completed_at, archived_at still manual).
- Template registry & validation.

## Profile Feature
- Profile page: user & employee data editable + password change (current_password checked).
- Automatic employee creation if missing on profile update.

## Seeders
- RolesAndPermissionsSeeder (roles & permissions).
- GradesSeeder (golongan set).
- UsersAndEmployeesSeeder (baseline users per role + staff employees).

## Integration & Logging
- Archive file handling now DIRECT remote upload (no local persistence for new/updated letters); legacy local path may remain null.
- File hash stored for integrity (sha256).
- WhatsApp sending via queued job with retry & logging.
- Integration logs table exists (permission integration_log.view seeded) – UI still pending.

## Gaps / Next Steps (Updated)
1. Automated status timestamps via observers (disposed_at, followed_up_at, completed_at, archived_at).
2. WA follow-up & completion actions (commands/buttons) and redispose command.
3. Claim feedback messaging (success/failure) – status Received already supported.
4. IntegrationLog UI + retry workflow.
5. Async/queued archive upload (currently synchronous inline in controller).
6. Model factories for IncomingLetter & Disposition.
7. Feature & unit tests (CRUD, WA flows, permissions, profile, archive remote upload).
8. Larastan + CI pipeline.
9. Localization: ensure all new labels (Integration Logs, Disposition History columns, scanner UI) present in lang files.
10. Advanced rate limiting algorithm & user-friendly messages.
11. Template registry + validation for WhatsApp.
12. DRY scanner JS (shared partial) & loader/error UI.
13. Document remote file access (view link construction) & optional fallback if remote down.

## Disposition History (Implemented)
- Letter detail view now includes a table of dispositions (sequence, target, status, claimed/received/followed_up/completed timestamps, instruction / rejection note).
- Enhancements pending: pagination for large history, aging metrics, and highlight current active disposition.

## Flow Coverage Summary (Updated)
Implemented: direct archive upload (no local file), create/update letter, WA notify pimpinan, disposition create (unit/employee), claim atomic (status moves to Received), notes (disposition/reject/archive), multi-letter switch, help command, rate limiting (fixed window), profile management auto-employee, disposition history table, permission CRUD seeding (roles & permissions), menu translation & icons, scanner auto-init.
Missing: follow-up/completion WA path, automated status observers, integration log UI & retry, tests & factories, advanced rate limits, template registry, claim feedback message, redispose command.
Risk: Synchronous archive upload may slow create/update; absence of tests leaves regression window; no observer can lead to inconsistent timestamps.

## Status Enums
IncomingLetterStatus: new, disposed, followed_up, rejected, completed, archived.
DispositionStatus: new, sent, received, rejected, followed_up, completed.

## Security Notes
- Private storage for scans.
- Token & secrets not logged.
- Permissions enforced; role-specific capabilities defined.

## Recommended Immediate Actions (Revised)
1. Add model observers for disposition-driven status timestamps & archive events.
2. Add claim feedback messaging (success / already claimed) in WA flow.
3. Implement follow-up & completion WA commands.
4. Introduce basic feature tests (letters CRUD, disposition claim flow, profile update, permission gating).
5. Queue/archive upload async to decouple latency (job + retry).
6. IntegrationLog UI with filter & retry action.
7. DRY scanner script into shared partial + user-facing loader/error states.

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
Updated on 2025-10-24.
