# E-Office Incoming Letters – Copilot Agent Guide

## Project Structure (Current & Planned)
```
app/
  Http/
    Controllers/
    Middleware/
    Requests/              # (planned form requests)
  Models/
    User.php
    IncomingLetter.php     # (planned)
    Disposition.php        # (planned)
  Services/                # business/service layer (planned)
    Integrations/
      ArchiveApiClient.php # (planned external archive API)
      WhatsappClient.php   # (planned WhatsApp API)
  Actions/                 # single-purpose domain actions (planned)
  Jobs/                    # queue jobs (planned)
  Support/                 # helpers like TemplateRenderer (planned)
bootstrap/
config/
database/
  migrations/
  factories/
  seeders/
public/
resources/
  views/
  js/
  css/
routes/
  web.php
  api.php (planned)
storage/
tests/
vendor/
```

## Technology Used
- PHP 8.2
- Laravel 12
- Admin UI: `jeroennoten/laravel-adminlte`
- Queue: Laravel default (jobs table present) – driver configurable
- Formatter: `laravel/pint`
- Testing: PHPUnit
- Potential additions (recommended): Larastan (static analysis), Activity Log

## Domain Overview
- User Management: Single-role (enforced at business logic) using `spatie/laravel-permission` tables (`roles`, `permissions`, pivots). Permissions granular (user.view, user.create, user.edit, user.delete). Audit logging via `owen-it/laravel-auditing` capturing before/after for create/update/delete/restore across auditable models.
- IncomingLetter (surat masuk) with lifecycle: new → disposed → (followed_up | rejected) → completed → archived
- Disposition: routing/instruction entries, snapshot of sender & target identities, channel (WhatsApp default)
- Integration points: External Archive API (store metadata/file), WhatsApp messaging (template-based)

### Clarifications (Latest Decisions)
- Archive action (`archived_at`) is triggered manually by user (e.g., leader decides to archive directly without follow-up or rejection).
- Letter number comes from the physical/incoming letter; no internal auto agenda number required currently.
- Only one scanned file per incoming letter (no multi-attachments for now). Field `primary_file` is sufficient.
- No classification master list yet; classification/security/speed fields remain optional metadata (nullable).
- A rejected letter can later be disposed again (reset flow) – initial route always goes to leader first.
- SLA/deadline per disposition not required (no `deadline_at`).
- Integration logs will be stored in database (table planned) for Archive & WhatsApp, not just flat files.
- WhatsApp is the only active notification channel (no email/manual UI channel messaging needed now – others remain enum placeholders if needed later but can be trimmed).
- Disposition actions (forwarding to unit or individual employee) are initiated via WhatsApp interactive flow: operator → leader → leader chooses target (unit or staff) → recipient notified → actions continue in WA.
- Front-end requires visual refresh/notification of status changes but real-time WebSocket broadcasting not required initially (periodic AJAX refresh acceptable).

## Database Schema (Work in Progress)
### Table: `incoming_letters`
- Spatie Permission tables: `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`
- Auditing table: `audits` (provided by owen-it package)
- Table: `grades` (id, code, category, rank, timestamps)
- Table: `work_units` (id, name unique, description, timestamps)
- Table: `employees` (id, user_id, grade_id, work_unit_id, name, nip unique, position, email, phone_number, status enum active/inactive, timestamps)
Key columns:
- `letter_number` (unique) – original letter number
- `letter_date`, `received_date`
- `sender`, `subject`, `summary`
- `primary_file` (path scan)
- `archive_external_id` (unique nullable)
- Status enum: new, disposed, followed_up, rejected, completed, archived
- Tracking: `last_disposition`, timestamps for `disposed_at`, `completed_at`, `archived_at`
- Metadata placeholders: classification_code, security_level, speed_level, origin_agency, physical_location
- Optimizations: disposition_count, file_hash

### Incoming Letter CRUD Implementation
Implemented Features:
- Form Requests: `IncomingLetterStoreRequest` (creation validation) and `IncomingLetterUpdateRequest` (update validation with unique ignore).
- Controller: `IncomingLetterController` provides index (DataTables), create, store, show, edit, update, destroy plus a `datatable` endpoint for server-side listing.
- Views: Blade templates (`incoming_letters/index`, `_form`, `create`, `edit`, `show`) using AdminLTE layout, translation helper `__()`, dark mode compatible, and permission gating for actions.
- Routes: Named routes (`incoming_letters.*`) for all CRUD operations and datatable endpoint.
- File Handling: Uploaded `primary_file` stored on private disk under `incoming_letters/`; SHA-256 hash captured in `file_hash`. On update, previous file deleted if replaced.
- External Archive Integration: On create, the scanned file is synchronously POSTed to the external archive API (`/api/v1/dokumen-arsip`) with metadata (judul=subject, nomor_dokumen=letter_number, pengirim=sender, kategori=Surat Masuk, keterangan=summary). Successful responses store returned `id` in `archive_external_id`. Failure returns validation error and aborts create.
- Default Status: Newly created letters start at status `new` via enum `IncomingLetterStatus::New`.
- Security: File restricted to single attachment (pdf/jpg/jpeg/png, max 2MB); unique letter number enforced at validation & DB.
- Actions Column: DataTables supplies action buttons (detail/edit/delete) based on user permissions.

Refactor Update (2025-10-19):
- Index & listing unified: `IncomingLetterController@index` now handles both normal view rendering and AJAX DataTables (Yajra) responses (removed separate `datatable` method) aligning with Grade/WorkUnit patterns.
- Added status filter (dropdown) on index page with client-side reload injecting `status` parameter.
- Actions column generation extracted into private `buildActions` helper for readability and reuse.
- DataTables now includes an index column (`DT_RowIndex`) for consistent row numbering.
- Simplified custom manual pagination to leverage Yajra server-side features (search, order, index column) reducing bespoke code.

Pending / Next Steps:
- Add disposition creation flow and status transitions (disposed, followed_up, rejected, completed, archived) with dedicated actions or UI buttons.
- Add observer/events to set `disposed_at`, `completed_at`, `archived_at` timestamps automatically and increment `disposition_count`.
- Integration with Archive API & WhatsApp notifications (queued jobs) after initial CRUD approval.
- Expand translations to additional fields/messages (success flash) and DataTables UI labels.

### Table: `dispositions`
- FK `incoming_letter_id`
- Snapshot sender: from_user_id, from_name, from_nip, from_phone
- Target: to_user_id (nullable), to_unit_id (future FK), to_name, to_nip, to_phone, to_unit_name
- Instruction text, template_code
- Status enum: new, sent, received, rejected, followed_up, completed
- Channel: manual | whatsapp | email | system (default whatsapp)
- WhatsApp tracking: whatsapp_message_id, whatsapp_sent_at
- Timestamps: received_at, rejected_at, followed_up_at, completed_at
- Sequence ordering

### Index Strategy
- Filter/query heavy: status, incoming_letter_id, to_user_id, dates
- Unique: letter_number & archive_external_id
- Future: fulltext (subject, summary, sender) if MySQL/InnoDB

## Code Preferences
- Use English snake_case in DB; PHP properties typed.
- Controllers remain thin; move business logic to Actions/Services.
- Auditing via `owen-it/laravel-auditing` (model implements Auditable contract).
- Validation via Form Request classes.
- Use Laravel events/observers for side-effects (increment disposition_count, update status timestamps).
- Queued jobs for external API calls (Archive sync, WhatsApp send) with retry & logging.
- Consider PHP Enums for statuses mapped via casts.

## Linting & Type Checking
- Formatting: `./vendor/bin/pint` (add CI step with `pint --test`).
- Recommended addition: Larastan (`nunomaduro/larastan`) for static analysis.
  - phpstan.neon.dist sample config (level 6–8) after install.

## Testing Strategy
- Unit: Template rendering, status transitions, ArchiveApiClient stubs.
- Feature: CRUD incoming letters, disposition creation & status changes.
- Integration: WhatsApp send (HTTP fakes), Archive sync (HTTP fake + persistence check).

## Ajax & Server-side Processing
- List pages will use server-side DataTables (AJAX) – search handled by DataTables queries (no separate search endpoint beyond the datatable JSON source).
- Provide dedicated API route (e.g., `/api/incoming-letters`) returning JSON (filter, paginate, search) to feed DataTables.
- Columns to display: all principal fields (letter_number, letter_date, sender, subject, status, disposed/completed/archived timestamps as applicable, last_disposition).
- User index uses DataTables server-side: name, email, role, created_at, last_login_at.
- Standard user index columns (DataTables): index (DT_RowIndex), name, email, phone_number (from employees), role(s), actions (edit/delete buttons).
- Helper function `user()` returns currently authenticated user for permission checks (see `app/Support/helpers.php`).
- Employees index (DataTables): index, name, nip, position, grade(code), work_unit(name), email, phone_number, status, user_email, role(s), actions.
- Employee CRUD screens share a form partial `employees/_form.blade.php` for create & edit.
 - User CRUD screens now implemented: views `users/index`, `users/create`, `users/edit`, `users/show` with shared partial `users/_form`. Form fields: name, email, password (+ confirmation, optional on edit), role (single enforced selection). Actions column includes detail, edit, delete buttons gated by permissions.
 - Permissions management: bulk create & bulk edit implemented (name only, guard fixed to `web`). Views: `permissions/index` (DataTables list), `permissions/create` (dynamic multi-row add), `permissions/edit` (bulk inline edit/removal). Controller supports batch store (`permissions[]`) and batch update. Routes under prefix `permissions`.
 - Roles CRUD implemented with immediate permission assignment via checkbox list (checkbox value = permission name, using Spatie's name-based sync). Views: `roles/index` (DataTables listing roles & their permissions), `roles/create` (role name + permission checkboxes), `roles/edit` (edit name + modify permissions), `roles/show` (detail). Controller handles syncPermissions on create/update. Routes under prefix `roles`.

## Pending Items / Next Steps
1. Implement `IncomingLetter` & `Disposition` factories.
2. Create seeders for initial users & roles & base organizational data (grades, work units).
3. API routes for DataTables JSON (currently using same index endpoints with AJAX detection; consider dedicated `/api/*`).
4. Services: ArchiveApiClient, WhatsappClient (stubs present – expand with real HTTP + logging + retry).
5. Observers: disposition_count increment, status timestamp maintenance (planned – not yet coded).
6. Tests: CRUD + status transitions + permission gates (none yet – add PHPUnit feature tests & unit tests for enums/service stubs).
7. Fulltext search evaluation (subject/summary/sender) – deferred.
8. AdminLTE menu entries for newly added modules (Grades, Work Units) & existing (Users, Employees, Roles, Permissions, Letters).
9. Integration logs table & model (archive/whatsapp) – design & implement.
10. Optional: Larastan static analysis integration (phpstan.neon.dist) & CI.

### Organizational CRUD Status
Implemented:
- Grade (golongan) CRUD: controller, form requests (`GradeStoreRequest`, `GradeUpdateRequest`), views (index with DataTables, create, edit, show, `_form` partial). Permissions used: `view grades`, `create grades`, `edit grades`, `delete grades`.
- Work Unit (unit kerja) CRUD: controller, form requests (`WorkUnitStoreRequest`, `WorkUnitUpdateRequest`), views (index DataTables, create, edit, show, `_form`). Permissions: `view work_units`, `create work_units`, `edit work_units`, `delete work_units`.
Routing:
- Added route groups with prefixes `grades` and `work-units` mapping to their respective controllers; AJAX DataTables served via same index route (checks `$request->ajax()`).
Notes:
- DataTables initialization client-side; consider extracting JS to asset bundles for reuse.
- Unique constraints enforced at validation layer (grade code; work unit name) and via DB migrations.

## Improvements Considered (Optional)
- Separate attachment table if multi-file requirement arises.
- Activity log for compliance & auditing.
- Correlation IDs in logs for tracing external API calls.

## Localization & Internationalization
Default Locale: Indonesian (`id`) with fallback to English (`en`).

Implementation Details:
- `config/app.php` sets `locale` to `id` and `fallback_locale` to `en`.
- Session-based locale switching via route `GET /locale/{locale}` (allowed: `id`, `en`). Selection persisted in session; applied globally in `AppServiceProvider` boot (`App::setLocale(session('locale', config('app.locale')))`).
- Navbar language dropdown added in `config/adminlte.php` (submenu items: Indonesia, English) for quick switching.
- All new and updated Blade views (Grades, Work Units) wrap visible static text in the translation helper `__('...')`.
- JSON translation files: `resources/lang/id.json` and `resources/lang/en.json` contain UI phrases (buttons, headings, field labels, breadcrumbs).
- Dark mode support remains unaffected by localization.

Guidelines for Future Views:
1. Wrap all user-facing strings with `__()`.
2. Add new keys to both `id.json` and `en.json` for parity.
3. For validation/custom messages, place them in `resources/lang/{locale}/validation.php` if diverging from defaults.
4. Localize JavaScript DataTables UI by feeding language object using existing keys (e.g., `search` → `__('Search')`).
5. Avoid concatenating translatable strings; prefer placeholders: `__('Grade :code saved', ['code' => $grade->code])`.

Pending Localization Enhancements:
- Extend translations to Users, Employees, Roles, Permissions, Incoming Letters & Dispositions views.
- DataTables language configuration (pagination, info, search placeholder) using localized JSON.
- Middleware `SetLocale` for cleaner locale application (replace ServiceProvider logic).
- Consider storing user preference in database (user profile) for cross-device persistence.

## Status Enums (Proposed PHP Enum)
```php
enum IncomingLetterStatus: string {
  case New = 'new';
  case Disposed = 'disposed';
  case FollowedUp = 'followed_up';
  case Rejected = 'rejected';
  case Completed = 'completed';
  case Archived = 'archived';
}

enum DispositionStatus: string {
  case New = 'new';
  case Sent = 'sent';
  case Received = 'received';
  case Rejected = 'rejected';
  case FollowedUp = 'followed_up';
  case Completed = 'completed';
}
```

## Logging & Monitoring
- Dedicated log channels: archive, whatsapp.
- Store request/response in `log_integrasi` (planned) for debugging.
- Audit events stored in `audits` table for create/update/delete/restore with before/after diffs.
 - All primary domain models (`User`, `Employee`, `Grade`, `WorkUnit`, `IncomingLetter`, `Disposition`) now implement the `OwenIt\Auditing` Auditable contract & trait. Exclusions: `User` hides password & remember_token inherently; optional `$auditExclude` placeholders added to others for future noise reduction (e.g., large file hash or static template_code).

### WhatsApp Integration (2025-10-19)
Configuration:
- `config/e-office.php` now contains `whatsapp` settings: `base_url`, `phone_number_id`, `access_token`, `default_language`, `default_template`, `timeout` (env-driven `E_OFFICE_WA_*`).
- Logging channel `whatsapp` added to `config/logging.php` writing daily logs (`storage/logs/whatsapp.log`).

Service:
- `App/Services/Integrations/WhatsappClient` implements `sendTemplate`, `sendText`, and `sendDisposition` (wrapper) calling Meta Cloud API endpoint `{base_url}/{phone_number_id}/messages` with bearer token.

Job:
- `SendWhatsappMessageJob` queue job sends template or text, stores result in `integration_logs` table, retries (3 attempts, 30s backoff) and logs failures to `whatsapp` channel.

Database Logging:
- Migration `create_integration_logs_table` adds structured storage of integrations (service, endpoint, request_payload, response_body, status_code, success, attempt, message_id, correlation_id).

Translations:
- Added id/en keys: `WhatsApp message queued`, `Failed to send WhatsApp message`, `WhatsApp retry scheduled`, `WhatsApp template not configured`.

Next Enhancements:
- Add IntegrationLog model + UI.
- Resend action for failed messages.
- Correlation ID propagation across dispositions.
- Template registry & validation pre-send.

## Security Notes
- Validate file uploads (MIME, size, hash) before storage.
- Keep scanned files in non-public storage (`storage/app/private`).
- Avoid logging credentials/tokens.
- Limit permissions by role; superadmin can manage users & roles; operator restricted.

---
Generated on 2025-10-19.
