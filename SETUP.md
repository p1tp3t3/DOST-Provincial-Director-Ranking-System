# Running PRISM

How to get the system running, from a fresh clone to the background workers.
PRISM is a Laravel 13 + Inertia (Vue 3, Vuetify 4) app on MySQL, with Laravel
Reverb for live updates and browser web push for reminders.

1. [Requirements](#1-requirements)
2. [Install](#2-install)
3. [Configure `.env`](#3-configure-env)
4. [Database and seed data](#4-database-and-seed-data)
5. [Frontend assets](#5-frontend-assets)
6. [Run the app](#6-run-the-app)
7. [Background workers](#7-background-workers)
8. [Production](#8-production)
9. [Troubleshooting](#9-troubleshooting)

---

## 1. Requirements

| Tool | Version | Notes |
|---|---|---|
| PHP | 8.3+ | Extensions: `pdo_mysql`, `mbstring`, `openssl`, `curl`, `zip`, `bcmath`. `zip` is needed for the Excel template/upload; `gmp` is optional (faster web push encryption). |
| Composer | 2.x | |
| Node.js + npm | Node 20+ | Builds the Vue frontend. |
| MySQL | 8.x | Default database. |

On Windows, [Laravel Herd](https://herd.laravel.com) provides PHP, Composer and
Node and serves the site at `http://<folder-name>.test`.

## 2. Install

```bash
git clone <repo-url> director-ranking-information-system
cd director-ranking-information-system

composer install
npm install

cp .env.example .env
php artisan key:generate
```

## 3. Configure `.env`

Only the settings PRISM depends on are listed; everything else can stay as in `.env.example`.

### App and database

```dotenv
APP_NAME=PRISM
APP_URL=http://localhost          # the URL you open in the browser

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=prism
DB_USERNAME=root
DB_PASSWORD=
```

Create the database first (e.g. `CREATE DATABASE prism;`).

### Queue, cache and sessions

These all use the database — keep them as they are. The tables are created by the migrations.

```dotenv
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
```

### Mail

Generated accounts are emailed their login details (queued). Point this at a
real SMTP server, or leave `MAIL_MAILER=log` locally to write emails to
`storage/logs/laravel.log` instead.

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="noreply@your-domain"
MAIL_FROM_NAME="${APP_NAME}"
```

### Reverb (live updates)

Reverb pushes live changes to open pages (the notification bell, KPI catalog
changes). Generate the app credentials once:

```bash
php artisan reverb:install    # or fill REVERB_APP_ID / KEY / SECRET yourself
```

Local defaults:

```dotenv
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=...
REVERB_APP_KEY=...
REVERB_APP_SECRET=...
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

`REVERB_*` is how the **server** reaches Reverb; `VITE_REVERB_*` is how the
**browser** reaches it. For production values see [section 8](#8-production).

### Web push (browser notifications)

Generate the VAPID key pair (writes `VAPID_PUBLIC_KEY` / `VAPID_PRIVATE_KEY` to `.env`):

```bash
php artisan webpush:vapid
```

Then set a contact address:

```dotenv
VAPID_SUBJECT=mailto:admin@your-domain
```

Keep the same keys once users have subscribed — changing them stops existing
subscriptions from receiving notifications.

### Optional

```dotenv
SUPER_ADMIN_BREAKGLASS_PASSWORD_HASH=   # set from Super Admin > Maintenance
```

## 4. Database and seed data

```bash
php artisan migrate --seed
php artisan storage:link      # serves uploaded images (blog, provinces, profiles)
```

The seeders create the regions, provinces, KPI catalog and scores, CSTC
clusters, linkages and Facebook posts, plus these logins (password: `password`):

| Role | Employee no. / email |
|---|---|
| Super admin | `vinzmuloc` |
| Sub admin | `subadmin` / `subadmin@dost.test` |
| Regional director | `rd-<region id>` or `regdir_<region>@dost.test` — e.g. `regdir_region_xi@dost.test` |
| Provincial admin | `provadmin_<province>` — e.g. `provadmin_batanes` |
| Provincial director | `provdir_<province>@dost.test` — e.g. `provdir_batanes@dost.test` |

There are no usernames: everyone signs in with their employee no. or email.
Province and region parts are lower-case with underscores
(`Davao del Sur` → `davao_del_sur`). These logins stay the same on every reseed.

`php artisan db:seed --class=TestUsersSeeder` adds one extra test account per role.

To start over: `php artisan migrate:fresh --seed` (**deletes all data**).

## 5. Frontend assets

```bash
npm run dev      # development: Vite dev server with hot reload
npm run build    # production: compiled files in public/build
```

`VITE_*` values from `.env` are baked in when Vite starts or builds — restart
`npm run dev` or re-run `npm run build` after changing them.

## 6. Run the app

- **Herd:** the site is already served at `http://<folder-name>.test`.
- **Without Herd:** `php artisan serve` → `http://127.0.0.1:8000`.

Browsers only allow push notifications on **HTTPS** or **localhost /
127.0.0.1**. With Herd, run `herd secure <folder-name>` to use `https://`.

## 7. Background workers

Besides the web server, PRISM needs three processes:

| Process | Command | What breaks without it |
|---|---|---|
| Queue worker | `php artisan queue:work` | Account generation (CSV/Excel batches), account emails, KPI reminder jobs never run. |
| Reverb | `php artisan reverb:start` | No live bell updates or live KPI catalog refresh. Notifications are still saved and appear on the next page load. |
| Scheduler | `php artisan schedule:work` (local) / cron (production) | KPI deadline and missing-accomplishment reminders are never sent. |

### Run everything locally

Open one terminal per process in the project folder (with Herd, skip
terminal 1 — Herd already serves the site):

| # | Terminal | Command |
|---|---|---|
| 1 | Web server | `php artisan serve` |
| 2 | Vite (frontend) | `npm run dev` |
| 3 | Reverb | `php artisan reverb:start --debug` |
| 4 | Queue worker | `php artisan queue:listen --tries=1` |
| 5 | Scheduler | `php artisan schedule:work` |

Leave them running while you work; stop each with `Ctrl+C`.

#### Local Reverb

1. Make sure `.env` has the local values from [section 3](#reverb-live-updates)
   (`REVERB_HOST="localhost"`, `REVERB_PORT=8080`, `REVERB_SCHEME=http`, and the
   `VITE_REVERB_*` lines pointing at them).
2. Start it: `php artisan reverb:start --debug` — it listens on port 8080 and
   `--debug` prints every connection and message.
3. Restart `npm run dev` if you changed any `VITE_REVERB_*` value.
4. Check it: log in, open the browser console and look for
   `[Echo test] connection state changed: connecting -> connected`.
5. Send a test event (shows up in the console as `TestPing received` for any
   signed-in user):

   ```bash
   php artisan tinker --execute="broadcast(new App\Events\TestPing('hi'));"
   ```

#### Local queue worker

- `php artisan queue:listen --tries=1` reloads your code on every job — use it
  while developing.
- `php artisan queue:work` is faster but keeps old code in memory; restart it
  (`Ctrl+C`, run again) after changing PHP files.
- Watch what's waiting / failed:

  ```bash
  php artisan queue:monitor database:default
  php artisan queue:failed
  php artisan queue:retry all
  ```

- Run whatever is queued once and stop (handy for a quick test):
  `php artisan queue:work --stop-when-empty`

#### Local scheduler

- `php artisan schedule:work` checks every minute and runs tasks when they're due.
- Run the scheduled reminders right now instead of waiting for 8:00 AM:

  ```bash
  php artisan app:check-kpi-edit-submission-deadline-command
  php artisan app:notify-missing-accomplishments
  ```

  Then let the queue worker send them (or `php artisan queue:work --stop-when-empty`).
  To see a deadline reminder, set a quarter's deadline 3 or 1 days from today
  first (KPI Data → deadlines).

#### Test a notification end to end (local)

1. Terminals 2–4 running (Vite, Reverb, queue worker).
2. Open the site on `localhost` / `127.0.0.1` (or Herd over `https://`), log in
   as e.g. `provadmin_batanes` / `password`, open the bell and click **Turn on**.
3. Send yourself one:

   ```bash
   php artisan tinker --execute="App\Helpers\NotificationHelper::notify(App\Models\User::where('dost_employee_id','provadmin_batanes')->first(), 'Test', 'Hello from PRISM', '/dashboard');"
   ```

   It appears in the bell immediately (Reverb) and as a browser notification (web push).

### All at once (development)

```bash
composer dev
```

Runs the web server, queue worker, log viewer, Reverb and Vite together — the
same as terminals 1–4 above. It does **not** run the scheduler — start
`php artisan schedule:work` in another terminal.

### Scheduled tasks

Defined in `bootstrap/app.php` (times are Philippine time):

| Command | When | What it does |
|---|---|---|
| `app:check-kpi-edit-submission-deadline-command` | Daily, 8:00 AM | 3 days and 1 day before a quarter's deadline, reminds regional directors, provincial admins and provincial directors. |
| `app:notify-missing-accomplishments` | Mondays, 8:00 AM | Reminds provincial admins and directors about quarterly accomplishments still blank. |

Each run queues one job per user as a named batch (`SendKpiDeadlineReminder`,
`SendMissingAccomplishmentsReminder`), so the queue worker must be running. A
command run twice the same day (or week) doesn't send twice.

Check them without sending anything:

```bash
php artisan schedule:list
php artisan app:check-kpi-edit-submission-deadline-command --dry-run
php artisan app:notify-missing-accomplishments --dry-run
```

Reminders reach users in the notification bell (and live over Reverb), and as a
browser notification for users who clicked **Turn on** in the bell.

## 8. Production

### Deploy / update

```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm ci && npm run build
php artisan optimize          # caches config, routes, views
php artisan queue:restart     # workers pick up the new code
```

### Production Reverb

In production the browser reaches Reverb through the site's HTTPS domain under
a `/ws` path, while the server talks to Reverb locally:

```dotenv
REVERB_HOST=127.0.0.1
REVERB_PORT=6060
REVERB_SCHEME=http

VITE_REVERB_HOST="prism.dost9.ph"
VITE_REVERB_PORT="443"
VITE_REVERB_SCHEME="https"
VITE_REVERB_PATH="/ws"
```

Two code settings are commented out in the repo for local development and must
be **uncommented on production** before `npm run build`:

- `resources/js/echo.js` — `wsPath: import.meta.env.VITE_REVERB_PATH ?? ''`
- `config/broadcasting.php` — the `connect_timeout` / `timeout` client options
  (so a save fails fast instead of hanging when Reverb is down)

The web server must proxy `/ws` to Reverb (WebSocket upgrade), and Reverb runs
with `php artisan reverb:start --port=6060`.

### Keep the workers running (Supervisor)

`/etc/supervisor/conf.d/prism.conf`:

```ini
[program:prism-queue]
command=php /var/www/prism/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/prism/storage/logs/queue.log
stopwaitsecs=3600

[program:prism-reverb]
command=php /var/www/prism/artisan reverb:start --port=6060
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/prism/storage/logs/reverb.log
```

```bash
sudo supervisorctl reread && sudo supervisorctl update
```

### Scheduler (cron)

```cron
* * * * * cd /var/www/prism && php artisan schedule:run >> /dev/null 2>&1
```

## 9. Troubleshooting

| Symptom | Check |
|---|---|
| Accounts / reminders stuck as "queued" | Is `queue:work` running? `php artisan queue:failed` lists failures. |
| Bell doesn't update live | Is Reverb running, and do `VITE_REVERB_*` match how the browser reaches it? Rebuild after changing them. |
| No browser notifications | HTTPS (or localhost)? User clicked **Turn on** in the bell? `VAPID_*` set? Browser site settings allow notifications? |
| Reminders never sent | Scheduler running (`schedule:work` or cron)? `php artisan schedule:list`. Is a deadline set exactly 3 or 1 days ahead? |
| Images don't load | `php artisan storage:link` |
| Changed `.env` has no effect | `php artisan optimize:clear` (and restart `npm run dev` for `VITE_*`). |
| Logs | `storage/logs/laravel.log`, or `php artisan pail` |
