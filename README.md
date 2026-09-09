# Practice Onboarding Tracker

A lightweight internal web app for tracking how far each medical practice has
progressed through onboarding for each Practice Engine product.

Plain PHP 8 and MySQL. No framework, no Composer, no build step, so it runs on
standard GoDaddy shared hosting and deploys by copying files.

**This application holds operational onboarding data only. It is not built for,
and must not be used to store, protected health information.**

---

## The one design decision worth understanding

A task is defined **once, globally**, against a product. A practice's task list
is not stored anywhere. It is *derived* at read time:

```
practices -> practice_products -> products -> tasks
                                                |
                        LEFT JOIN practice_tasks (the per-practice state)
```

A `practice_tasks` row is written **only when someone actually touches the
task** by setting a status, assignee, due date, or note. No row means Not
Started.

That single choice gives you everything the spec asked for, for free:

| Requirement | Why it just works |
|---|---|
| Add a global task, every practice on that product gets it | The task list is a join, so the new row is picked up on the next page load. Nothing is fanned out. |
| Status, assignee, due date, notes are per practice | They live in `practice_tasks`, keyed on `(practice_id, task_id)`. |
| Only selected products appear for a practice | The join starts at `practice_products`. |
| Removing a product from a practice | Its tasks vanish from the view, but the recorded state survives, so re-adding the product restores the history. |
| Deactivating a task | `tasks.is_active = 0` hides it from every practice at once. |
| Progress is always accurate | Percentages are computed in the query, never stored, so they cannot drift. |

Progress is `completed / (all tasks except Not Applicable)`. Marking something
Not Applicable removes it from both sides of the fraction rather than counting
as incomplete.

---

## Layout

```
.cpanel.yml                 what cPanel runs on deploy
config/config.sample.php    copy to config.php on the server
db/schema.sql               tables
db/seed.sql                 starter products, tasks, categories, people, sample practices
db/migrations/              future schema changes, NNN_description.sql
src/
  bootstrap.php             config, session, error handling
  Database.php              PDO wrapper, prepared statements only
  Auth.php                  shared-PIN admin auth, throttled
  Csrf.php                  per-session token, required on every write
  Activity.php              audit trail
  Repo.php                  every query in the app
  helpers.php               escaping, formatting, status constants
templates/                  views, no logic beyond loops and escaping
public/
  index.php                 front controller, all routes and writes
  install.php               one-time setup, disable when live
  assets/app.css            all styling
  assets/app.js             inline editing, bulk select
tools/migrate.php           applies pending migrations
```

Routes are query parameters (`index.php?p=dashboard`), so nothing depends on
`mod_rewrite` being available.

---

## Access model

| Who | How | Can do |
|---|---|---|
| Viewer | No sign-in | Read the dashboard, practice pages, and the all-tasks view. Sees progress, statuses, assignees, due dates, blockers, and overdue flags. Cannot change anything. |
| Admin | Shared PIN | Everything: practices, products, tasks, categories, people, statuses, assignees, due dates, notes, ordering. |

The PIN is stored as a `password_hash`, never in plain text. Sign-in is
throttled to eight attempts per fifteen minutes. Every write requires a CSRF
token. Every query uses a prepared statement.

---

## Screens

**Dashboard** — one row per practice: products, progress bar, done, remaining,
blocked, overdue, target go-live with a countdown, and last updated. Rows with
a blocker or an overdue task get a red edge. Sortable by name, progress,
go-live, last updated, blocked count, or overdue count. Filterable by product,
onboarding state, search, and needs-attention.

**Practice detail** — overall progress, plus progress by product and by
category. Tasks grouped by product, then by category. Status, assignee, due
date, and notes are all editable in place: change a dropdown and it saves, no
page reload, and the progress bars update themselves. Select several rows
(shift-click works) and the bulk bar applies a status, assignee, or due date to
all of them. Below the tasks: practice notes and a recent-activity feed.

Filtering the task list never changes the reported percentages. The figures
always describe every task; a note tells you when the view is filtered.

**All tasks** — every task across every practice, with the full filter set:
practice, product, category, status, assignee, blocked only, overdue only.
This is the "what is blocked everywhere" and "what is on my plate" view. Exports
to CSV.

**Admin** — practices (with the product picker), the global task library per
product with reordering, products, categories, people, and the activity log.

---

## First-time setup

1. Create a MySQL database and user in cPanel. Note the names; GoDaddy prefixes
   both with your cPanel username.
2. Copy `config/config.sample.php` to `config/config.php` and fill in the
   credentials. Leave `allow_install` as `true` for now.
3. Open `install.php` in a browser. It checks the connection, creates the
   tables, optionally loads the starter data, and hashes an admin PIN for you.
4. Paste the generated `admin_pin_hash` line into `config/config.php`.
5. Set `allow_install` to `false`, or delete `public/install.php`.

`DEPLOY.md` covers getting the files onto GoDaddy from Git.

## Starter data

Seeded, and all editable in the admin screens without touching code:

- **5 products**: Recall Health, Online Scheduler, AI Voice Agent, Payment
  Portal, Patient Intake. Add the rest through Products.
- **7 categories**: Administrative, Technical, Configuration, Cosmetic /
  Design, Training, Testing, Go-Live.
- **102 global tasks**, roughly 20 per product, spread across the categories.
- **5 role-based assignees**. Rename them to real people under People.
- **4 sample practices** with part-filled progress, one blocker and one overdue
  task, so the dashboard is not empty on day one. Delete them once your real
  practices are in.

## Verification

`db/schema.sql` and `db/seed.sql`, and the derived-list and rollup queries in
`Repo.php`, were checked against a live SQL engine: the derived task counts, the
dashboard aggregates, product scoping, the no-fan-out behaviour when a global
task is added, state preservation when a product is removed and re-added, task
deactivation, Not Applicable handling, and the percentage arithmetic. The PHP
itself was checked statically for delimiter balance, missing includes, missing
templates, undefined methods, and links to routes that do not exist. It has not
yet been executed against a live PHP runtime; `install.php` is the smoke test
for that, and it reports exactly which step fails.

## Deliberate non-features

Kept out to stay lightweight; each is a small addition later if you want it:

- Per-product go-live dates, in addition to the practice-level one.
- Required vs optional tasks, so optional work does not drag the percentage.
- Due dates derived from a kickoff date plus a per-task day offset.
- A blocker-reason field, separate from the notes.
- Practice-side contacts.
- Emailed digests of blocked and overdue work.
