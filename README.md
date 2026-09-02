# Project Initialization System

> Automated Project Initialization System (PIF/APIS) for BFC Group — routes farm project requests through a role-based approval chain, from submission to engineer assignment.

![Laravel](https://img.shields.io/badge/Laravel-12.x-red?logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.2+-blue?logo=php)
![Livewire](https://img.shields.io/badge/Livewire-3.x-4E56A6?logo=livewire)
![Tests](https://img.shields.io/badge/Tests-Passing-brightgreen)

---

## Table of Contents

- [About](#about)
- [Tech Stack](#tech-stack)
- [Roles & Workflow](#roles--workflow)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Environment Variables](#environment-variables)
- [Seeding](#seeding)
- [Running Locally](#running-locally)
- [Testing](#testing)
- [Notifications (Reverb)](#notifications-reverb)
- [Folder Structure](#folder-structure)

---

## About

Project Initialization System is a Project Initialization Form (PIF) system: Farm Managers submit project requests (with budget-driven auto-calculated timelines), which are routed through an approval chain — Division Head → VP General Services → ED Manager → DH General Services → Engineer — before landing back with the requestor as an initialized project. Requests with an unacceptable timeline go through a Justification Letter (JL) sub-flow instead, which reorders the same approval chain around a dual DH/VP review.

There are no REST/API controllers beyond authentication — the UI is built entirely from full-page Livewire components rendered inside a shared Blade layout, with Alpine.js for client-side interactivity (dropdowns, dark mode, toasts).

**Key features:**

- Budget-category-driven timeline auto-calculation on new requests (business-day start/completion dates via `App\Support\ProjectTimelineCalculator`)
- Multi-role approval chain with owner-based request routing (`current_owner_role` / `current_owner_id`)
- Justification Letter (JL) exception flow for requests with unacceptable timelines
- Project Request Summary view (with Assigned Engineer column, shown only when applicable) and full audit/history trail per request
- Project Calendar: a Gantt-style timeline of every project's Start → Completion window, scoped per role (own requests for Farm Manager, assigned projects for Engineer, full oversight for reviewer roles)
- Reference Links: attach any number of external URLs to a project request (e.g. links into another tracking system) so reviewers can jump straight to related progress info
- Settings-Change sub-flow (separate from the main approval chain) for VP Gen Services, DH Gen Services, and ED Manager
- IT Admin console: user management, audit trail, status override, danger zone, pending settings-changes
- DH Gen Services & IT Admin can both manage Engineer accounts (Administration Facility / Assigned Engineers)
- Guest viewer role with read-only visibility into finished requests
- Dark mode with a flash-free (FOUC-safe) load
- Live in-app notification bell (Laravel Reverb WebSocket + database notifications)

---

## Tech Stack

| Layer         | Technology                          |
|---------------|--------------------------------------|
| Framework     | Laravel 12.x                        |
| Language      | PHP 8.2+                            |
| UI            | Livewire 3, Blade, Alpine.js         |
| Realtime      | Laravel Reverb + Laravel Echo/Pusher-js |
| Database      | MySQL 8.0                            |
| Sessions/Cache/Queue | Database driver (no Redis required) |
| CSS           | Tailwind CSS 4                       |
| Build         | Vite 7                               |
| Testing       | PHPUnit (Livewire feature tests)     |
| Backups       | spatie/laravel-backup                |
| Storage       | Local disk / Google Drive (Flysystem adapter) |

---

## Roles & Workflow

| Role | Landing area |
|------|--------------|
| `farm_manager` | Submit new requests, assessment meeting scheduling, my requests, request summary, project calendar |
| `division_head` | Inbox (recommend/reject), history, request summary, project calendar |
| `vp_gen_services` | Inbox (approve/reject), settings change-requests, history, request summary, project calendar |
| `dh_gen_services` | Noting (assign engineer), settings change-request, history, request summary, administration facility (engineer accounts), project calendar |
| `ed_manager` | Inbox (accept/return), settings change-request, history, request summary, project calendar |
| `it_admin` | All requests, users, audit trail, status override, pending changes, settings, danger zone, assigned engineers, project calendar |
| `engineer` | Inbox (mark initialized), request summary, project calendar |
| `guest` | Finished requests (read-only) |

Project Calendar (`/{role}/calendar`) is registered for every role above and reuses the same `App\Livewire\Shared\ProjectCalendarPage` component: Farm Manager sees only requests they submitted, Engineer sees only requests assigned to them (`assigned_engineer_id`), and every other role sees the full set for oversight.

Roles and route/middleware protection are defined in [routes/web.php](routes/web.php); the `role:` middleware is `App\Http\Middleware\EnsureUserHasRole`.

---

## Prerequisites

- **PHP** >= 8.2 with extensions: `mbstring`, `xml`, `pdo_mysql`, `curl`, `zip`
- **Composer** >= 2.x
- **Node.js** >= 20.x and **npm** >= 10.x
- **MySQL** >= 8.0
- A local dev stack such as Laragon/Herd (no Docker/Sail requirement, though `laravel/sail` is available as a dev dependency)

---

## Installation

```bash
# 1. Clone the repository
git clone <repo-url>
cd EngistartSystem

# 2. Install PHP dependencies
composer install

# 3. Copy environment file
cp .env.example .env
php artisan key:generate

# 4. Configure your database in .env, then run migrations + seed the clean state
php artisan migrate:fresh --seed

# 5. Load the dummy accounts and sample data (local/staging only)
php artisan db:seed --class=TestSeeder

# 6. Install Node dependencies and build assets
npm install
npm run build

# 7. Install & configure Reverb (WebSocket server for notifications)
php artisan reverb:install
```

---

## Environment Variables

Key variables beyond Laravel defaults:

```env
# Auth: no Turnstile secret -> local test accounts; secret set -> BFC Group's external auth API
AUTH_API_BASE_URI=https://bfcgroup.ph
AUTH_API_KEY=
AUTH_USER_API_KEY=
AUTH_VERIFY_SSL=true

# Cloudflare Turnstile. A blank secret key also means "testing mode" — see Seeding below.
TURNSTILE_SITE_KEY=
TURNSTILE_SECRET_KEY=

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=engistart_db
DB_USERNAME=root
DB_PASSWORD=

# Sessions / cache / queue — database-backed, no Redis required
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Broadcasting (notification bell)
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

> **Note:** Never commit your `.env` file.

---

## Seeding

Three seeders, each with one job:

| Seeder | Holds | Run when |
|---|---|---|
| `DatabaseSeeder` | Reference/lookup data only — the clean state | Every `migrate:fresh --seed` |
| `TestSeeder` | Dummy accounts and sample requests | Local and staging, on top of the clean state |
| `DeploymentSeeder` | The real accounts a live system starts with | Once, by hand, at go-live |

The normal dev reset is these two, in this order:

```bash
php artisan migrate:fresh --seed              # clean, usable, zero users, zero requests
php artisan db:seed --class=TestSeeder        # + dummy accounts and sample data
```

`DatabaseSeeder` never creates users and never calls the other two, so `migrate:fresh --seed`
always lands on an empty-but-usable system. `TestSeeder` refuses to run when `APP_ENV=production`.
`DeploymentSeeder` is idempotent and deliberately not wired into `--seed`:

```bash
php artisan db:seed --class=DeploymentSeeder  # real initial data, go-live only
```

Seeders live under `database/seeders/` in `Reference/`, `Test/` and `Deployment/`; the three
top-level seeders are orchestrators that do nothing but call into those folders.

### Testing mode

The system works out for itself whether it is a real deployment, using the Turnstile secret key
as the signal — that key only exists on a properly set-up environment.

| `TURNSTILE_SECRET_KEY` | Login behaves as |
|---|---|
| Blank / unset | **Testing mode on.** The Auth API is never called; the login page lists the `TestSeeder` accounts and authenticates against them locally. |
| Set | **Testing mode off.** Normal login goes through the external Auth API; the test-account panel is gone. |

The Turnstile widget itself renders whenever `TURNSTILE_SITE_KEY` is set — independent of the mode
switch above, since a site key with no matching secret is a misconfiguration, not a mode.

Filling in the secret is the flip — there is no separate switch to remember at go-live, and no flag
that can leave a live system on dummy accounts. Under `APP_ENV=production` testing mode is always
off regardless of the secret, so a misconfigured production system fails closed rather than falling
back to dummy accounts.

Test accounts (one per role, password `1234`) are defined once in
[app/Support/TestAccounts.php](app/Support/TestAccounts.php) — the seeder and the login panel
both read from there, so they cannot drift.

---

## Running Locally

`composer run dev` starts everything concurrently (server, queue listener, log tailing via Pail, Vite):

```bash
composer run dev
```

Or start each piece manually in separate terminals:

```bash
php artisan serve
php artisan queue:listen
php artisan reverb:start   # required for live notification bell updates
npm run dev
```

---

## Testing

Feature tests drive the workflow through Livewire component calls (`tests/Feature/WorkflowSmokeTest.php`) rather than HTTP requests, since the app has no API layer to hit.

```bash
php artisan test
php artisan test --filter=WorkflowSmokeTest
```

If `pdo_sqlite` isn't available in your PHP install (this project's `phpunit.xml` defaults to sqlite `:memory:`), point tests at an isolated MySQL database instead without touching `phpunit.xml`:

```bash
DB_CONNECTION=mysql DB_DATABASE=engistart_test php artisan test
```

---

## Notifications (Reverb)

Every ownership-changing transition in the approval chain (submit, recommend, approve, accept, note-forward, mark-initialized, return-to-requestor) fires a `WorkflowNotification` via `App\Support\WorkflowNotifier`, delivered over both the `database` and `broadcast` channels. The bell (`App\Livewire\Shared\NotificationBell`) subscribes to the recipient's private Echo channel (`App.Models.User.{id}`, registered in [routes/channels.php](routes/channels.php)) and updates live with no page refresh.

`php artisan reverb:start` must be running for live delivery; without it, notifications still land in the `notifications` table and appear on next load.

---

## Folder Structure

```
app/
├── Http/
│   ├── Controllers/AuthController.php   # only controller in the app — login/logout, role→route map
│   └── Middleware/
├── Livewire/
│   ├── FarmManager/       # new request, assessment meeting, my requests
│   ├── DivisionHead/      # inbox
│   ├── VPGenServices/     # inbox, change-requests
│   ├── DHGenServices/     # noting, settings change-request
│   ├── EDManager/         # inbox, settings change-request
│   ├── ITAdmin/           # all-requests, users, audit, override, settings, danger zone
│   ├── Engineer/          # inbox
│   ├── Guest/             # finished requests
│   └── Shared/            # request summary, project calendar, assigned engineers, notification bell, confirmation modal
├── Models/                # ProjectRequest, ProjectReferenceLink, User, etc.
├── Notifications/         # WorkflowNotification
└── Support/               # WorkflowNotifier, ProjectTimelineCalculator, and other helpers

routes/
├── web.php
└── channels.php           # broadcast channel authorization

resources/
├── views/
│   ├── layouts/app.blade.php
│   └── livewire/
└── js/
    ├── app.js
    └── echo.js            # Laravel Echo/Reverb client init

tests/
└── Feature/
    └── WorkflowSmokeTest.php
```
