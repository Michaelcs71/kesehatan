# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

**Kesehatan** — a Laravel 12 / PHP 8.2 web app for diabetes patient care: scheduling and reminders for medication intake (*Minum Obat* / **MO**) and blood-sugar checks (*Cek Gula Darah* / **CGD**), plus the master data and user/role management around them. Domain language is **Indonesian** — entity names, methods, comments, validation messages, and UI strings are all in Indonesian; follow this convention when adding code. The folder is named `kesehatanmysql` because the project was migrated from SQLite to MySQL (see `app_backup_before_mysql/`).

## Commands

```bash
composer setup          # install deps, copy .env, key:generate, migrate, npm install + build
composer dev            # run server + queue + pail (logs) + vite concurrently — the dev loop
composer test           # config:clear then artisan test (PHPUnit, sqlite :memory:)
php artisan test --filter=SomeTest   # run a single test / method
vendor/bin/pint         # format code (Laravel Pint) — the linter

php artisan migrate:fresh --seed     # rebuild DB and seed roles/permissions/users
npm run dev / npm run build          # vite assets only
```

DB config lives in `.env` (`DB_CONNECTION=mysql`, database `kesehatan`). Seeding order matters and is fixed in `DatabaseSeeder`: `UserSeeder` → `RolePermissionSeeder` → `UserRoleAssignmentSeeder`.

## Architecture: the layered CRUD pattern

Every master/transaction module follows the same four-layer pipeline. Understand this before touching any feature — it is the single most important thing in the codebase.

**Controller → Service → Repository → Model.** Controllers are thin; **Services and Repositories use STATIC methods exclusively.**

1. **Controller** (`app/Http/Controllers/`) extends `BaseController` and uses the `HasCrudViews` + `HasCrudOperations` traits. It only implements three/four abstract hooks — `getViewPath()`, `getRouteName()`, `getService()`, optionally `getEntityName()` — and delegates each action to a trait helper (`handleStore`, `handleUpdate`, `handleDestroy`, `handleGetData`, `indexView`, `editView`, etc.). See `MasterKategoriObatController` as the canonical example.

2. **Service** (`app/Services/`) uses the `HasStandardizedMethods` trait. The trait exposes generic `getAll/getById/create/update/delete` that **magically delegate** to entity-specific methods by name: `getAll{Plural}`, `find{Singular}ById`, `create{Singular}`, `update{Singular}`, `delete{Singular}` (the singular/plural come from `getEntityName()`/`getEntityPluralName()`). So `MasterKategoriObatService::getAll($p)` actually calls `getAllMasterKategoriObats($p)`. Services own business rules (e.g. block delete if still referenced) and stamp `created_by`/`updated_by` via `Auth::id()`.

3. **Repository** (`app/Repos/`) holds all Eloquent queries and wraps writes in `DB::transaction(...)`. Pagination returns a `LengthAwarePaginator`; the service reshapes it into `['TotalRows' => n, 'Rows' => [...]]` for the front-end grid.

4. **Model** (`app/Models/`) uses `HasUuids` (string UUID primary keys), often `SoftDeletes`, query scopes (`scopeActive`, `scopeSearch`, …), and audit relations (`creator`/`updater`/`deleter`).

**Validation:** one FormRequest class per action under `app/Http/Requests/<Domain>/` (e.g. `IndexRequest`, `StoreRequest`/`StoreUpdateRequest`, `UpdateRequest`). Controllers always pass `$request->validated()` down — never raw input.

### CRUD is AJAX/JSON, not form-POST-redirect

For a given module the routes split by response type:
- `index`, `create`, `edit`, `show` → return **Blade views** (`{viewPath}/index|form|show.blade.php`; `form.blade.php` serves both create and edit).
- `getData` (`/data`), `showData` (`/{id}/data`), `store`, `update`, `destroy`, `options` → return **JSON** via `BaseController`'s `successResponse`/`errorResponse`.

So forms submit through JavaScript (axios/jQuery + DataTables + SweetAlert2), the grid loads via the `/data` endpoint, and detail/edit views fetch their record from `/{id}/data`. `BaseController::executeTransaction()` and `handleException()` wrap mutations with rollback + logging.

To add a new CRUD module, mirror an existing one end-to-end: Model (+migration) → Repository → Service (define the entity-specific methods + the two name hooks) → FormRequests → Controller (the four hooks + thin actions) → route group → blades (`index`/`form`/`show`).

## Authorization (two systems that must stay in sync)

There are **two parallel role mechanisms**, and routes use both:

- **`UserRole` enum** (`app/Enums/UserRole.php`: `pengunjung`, `pasien`, `pmo`, `admin`, `superadmin`) stored as the `users.role` column. Guarded by the **custom `role:` middleware** (`EnsureUserHasRole`), which also enforces `is_active` (deactivated users are logged out). Helpers: `$user->isAdmin()`, `isSuperadmin()`, `isPasien()`, `isPmo()`, `homeRoute()`.
- **Spatie permissions** (`spatie/laravel-permission`) — fine-grained `module.action` permissions (e.g. `master-obat.index`, `jadwal-mo.create`). Guarded by the Spatie **`permission:` middleware**. Middleware aliases are registered in `bootstrap/app.php`.

Most routes in `routes/web.php` are wrapped in `['auth','verified']` plus a `prefix()`/`name()` group, then gated per-action with `permission:` (and sometimes `role:`). UUID route params are constrained with `->where('id', '[0-9a-f\-]+')`.

The full permission catalogue and per-role grants live in `RolePermissionSeeder` (`superadmin` = `'*'`). `User` **overrides Spatie's `hasPermissionTo`/`getAllPermissions`**: superadmin always passes; if `permission_overridden` is true the user uses only direct permissions, otherwise role ∪ direct. `PermissionHelper` groups permissions by module (with UI metadata) for the permission-management screens. The `users.role` enum and the user's Spatie role are kept synced (the seeder does this; preserve it when changing roles).

Auth scaffolding is **Laravel Breeze** (`routes/auth.php`, `app/Http/Controllers/Auth/*`). Users have **UUID** ids; only `pasien` and `pmo` self-register (`UserRole::selfRegisterable()`) and require biodata.

## Domain notes

- **PMO** = *Pendamping Minum Obat* (a patient's medication companion/supervisor). `PasienPmo` maps a patient user (`id_user`) to a PMO user (`pmo_user_id`); `User` exposes `pasienPmoAsPasien` / `pasienPmoAsPmo` / `pasienBinaan`.
- Dashboards and home routes are role-specific (`superadmin.dashboard`, `admin.dashboard`, `pmo.dashboard`, `pasien.dashboard`); `/dashboard` redirects via `homeRoute()`.
- `pengingat-mo` / `pengingat-cgd` are **confirmation logs** (patient/PMO confirm they took meds / checked sugar); `jadwal-mo` / `jadwal-cgd` are the schedules. `master-pasien`, `master-pmo`, `konten-*`, `laporan-*` are routed to a `placeholder` view — permissions exist but the modules aren't built yet.
- User bulk import (`maatwebsite/excel`) is a multi-step flow: `template` → `preview` → `validate-row` → `confirm` (`UserImportController` + `UserImport` + `UserImportService`).

## Frontend

Blade + Vite. Authenticated pages extend `layouts/app.blade.php` (CoreUI sidebar + `partials/header`, flash-message handling, CSRF meta); public pages use `layouts/landing`, auth pages `layouts/guest`. Stack: Tailwind 4, Bootstrap 5.3, CoreUI, DataTables (bs5), SweetAlert2, Alpine, jQuery, Chart.js, RemixIcon. Shared Blade components are in `resources/views/components/` (e.g. `data-grid`, `modal`, `card`, `sidebar`). A `window.whenKesehatanReady(cb)` hook gates JS init on a `kesehatan:ready` event.
