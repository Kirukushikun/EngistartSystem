# EngiStart

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
- [Running Locally](#running-locally)
- [Testing](#testing)
- [Notifications (Reverb)](#notifications-reverb)
- [Folder Structure](#folder-structure)

---

## About

EngiStart is a Project Initialization Form (PIF) system: Farm Managers submit project requests (with budget-driven auto-calculated timelines), which are routed through an approval chain — Division Head → VP General Services → ED Manager → DH General Services → Engineer — before landing back with the requestor as an initialized project. Requests with an unacceptable timeline go through a Justification Letter (JL) sub-flow instead, which reorders the same approval chain around a dual DH/VP review.

There are no REST/API controllers beyond authentication — the UI is built entirely from full-page Livewire components rendered inside a shared Blade layout, with Alpine.js for client-side interactivity (dropdowns, dark mode, toasts).

**Key features:**

- Budget-category-driven timeline auto-calculation on new requests
- Multi-role approval chain with owner-based request routing (`current_owner_role` / `current_owner_id`)
- Justification Letter (JL) exception flow for requests with unacceptable timelines
- Project Request Summary view and full audit/history trail per request
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
| `farm_manager` | Submit new requests, assessment meeting scheduling, my requests |
| `division_head` | Inbox (recommend/reject), history, request summary |
| `vp_gen_services` | Inbox (approve/reject), settings change-requests, history, request summary |
| `dh_gen_services` | Noting (assign engineer), settings change-request, history, request summary, administration facility (engineer accounts) |
| `ed_manager` | Inbox (accept/return), settings change-request, history, request summary |
| `it_admin` | All requests, users, audit trail, status override, pending changes, settings, danger zone, assigned engineers |
| `engineer` | Inbox (mark initialized) |
| `guest` | Finished requests (read-only) |

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

# 4. Configure your database in .env, then run migrations
php artisan migrate

# 5. Install Node dependencies and build assets
npm install
npm run build

# 6. Install & configure Reverb (WebSocket server for notifications)
php artisan reverb:install
```

---

## Environment Variables

Key variables beyond Laravel defaults:

```env
# Auth mode: local DB auth, or delegate to BFC Group's external auth API
ENGISTART_AUTH_MODE=local
# local/api

AUTH_API_BASE_URI=https://bfcgroup.ph
AUTH_API_KEY=
AUTH_USER_API_KEY=
AUTH_VERIFY_SSL=true

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
│   └── Shared/            # request summary, assigned engineers, notification bell, confirmation modal
├── Models/                # ProjectRequest, User, etc.
├── Notifications/         # WorkflowNotification
└── Support/               # WorkflowNotifier and other helpers

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
