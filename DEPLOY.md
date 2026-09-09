# Deploying to GoDaddy from Git

Using cPanel's built-in **Git Version Control**. You push to GitHub, then click
Deploy in cPanel, and a script copies the files into place. No FTP credentials
live in the repo, and `config/config.php` on the server is never overwritten.

Nothing here is run for you. Commits and pushes happen only when you ask.

---

## One-time setup

### 1. Put the project on GitHub

From this folder:

```bash
git init
git add .
git commit -m "Practice Onboarding Tracker"
git branch -M main
git remote add origin git@github.com:YOURNAME/practice-onboarding.git
git push -u origin main
```

Make the GitHub repo **private**. `.gitignore` already excludes
`config/config.php`, so your database password never reaches GitHub. Keep it
that way.

### 2. Decide where it lives on the server

**Option A, a subdomain (cleaner, recommended).** In cPanel → Domains, create
`onboarding.yourdomain.com` and set its document root to:

```
/home/USERNAME/onboarding/public
```

Only `public/` is web-reachable. `src/`, `config/`, `templates/`, `db/`, and
`tools/` sit outside the web root where nobody can request them.

**Option B, a subfolder** (`https://yourdomain.com/onboarding`). Deploy to
`/home/USERNAME/public_html/onboarding`. The `.htaccess` files in each
non-public folder deny direct access, and the root `.htaccess` forwards
requests into `public/`. This works, but Option A is structurally safer.

### 3. Clone the repo in cPanel

cPanel → **Git Version Control** → *Create*:

- Clone URL: your GitHub repo (HTTPS is easiest; for SSH, add the deploy key
  cPanel shows you to GitHub → Settings → Deploy keys)
- Repository path: `/home/USERNAME/repos/practice-onboarding`
- Branch: `main`

Keep the clone path **outside** `public_html`. It is a working copy, not the
live site.

### 4. Point the deploy script at your folder

Edit `.cpanel.yml` and change the one marked line:

```yaml
- export DEPLOYPATH=/home/USERNAME/onboarding
```

Use the path from step 2, with your real cPanel username. Commit and push that
change.

> cPanel only runs `.cpanel.yml` if the repository is on the branch you have
> checked out and the file is valid YAML. Two spaces of indentation, no tabs.

### 5. Create the database

cPanel → **MySQL Databases**:

1. Create a database, e.g. `onboarding`. The real name gets your username
   prefixed: `cpaneluser_onboarding`.
2. Create a user with a strong generated password.
3. Add the user to the database with **All Privileges**.

Write down all three values, prefixes included.

### 6. Configure and install

Over SFTP or cPanel File Manager, in your `DEPLOYPATH`:

1. Copy `config/config.sample.php` to `config/config.php`.
2. Fill in `db_name`, `db_user`, `db_pass`. `db_host` stays `localhost`.
3. Set your timezone.
4. Visit `https://onboarding.yourdomain.com/install.php` and work through the
   five steps. It creates the tables, loads the starter data, and hashes your
   admin PIN.
5. Paste the hash into `config/config.php`, set `allow_install` to `false`, and
   confirm `install.php` now refuses to run.

Then open the dashboard and sign in with your PIN.

---

## Deploying a change

```bash
git add -A
git commit -m "What changed"
git push
```

Then in cPanel → Git Version Control, on the repository row:

1. **Update from Remote** — pulls your push into the server's working copy.
2. **Deploy HEAD Commit** — runs `.cpanel.yml`, copying files into the live
   folder.

The deploy log appears in cPanel. Both steps are needed; pulling alone does not
publish.

### Optional: deploy on push

Add a GitHub webhook so step 1 happens automatically. GitHub → repo → Settings
→ Webhooks → Add webhook, with the payload URL cPanel shows in Git Version
Control. You still click Deploy, unless your plan supports auto-deployment, in
which case cPanel offers a checkbox for it.

### If the schema changed

Add the change as `db/migrations/003_whatever.sql`, deploy, then run:

```bash
php /home/USERNAME/onboarding/tools/migrate.php
```

from cPanel → Terminal. It applies only what has not run yet and records each
file in `schema_migrations`. If your plan has no Terminal or SSH, paste the
migration into phpMyAdmin instead and add the filename to `schema_migrations`
manually so it is not applied twice later.

---

## Checks worth doing once

- Visit `/src/Repo.php` and `/config/config.sample.php` directly in a browser.
  Both must return 403 or 404. If either downloads as text, the `.htaccess`
  files are not being honoured and you should switch to the subdomain layout.
- Confirm the site loads over `https://`. The session cookie is only marked
  Secure when the request is HTTPS, so serving the app over plain HTTP weakens
  the admin session. Force HTTPS in cPanel.
- Sign out and confirm the read-only view has no editable controls.
- Note that the read-only dashboard is public to anyone with the link. If the
  domain is discoverable and that is not acceptable, the smallest fix is a
  second view-only PIN, or cPanel's Directory Privacy on the folder.

## Backups

`db/schema.sql` and `db/seed.sql` rebuild an empty app, not your data. Your
data lives only in MySQL. Either enable a scheduled backup in cPanel, or export
the database from phpMyAdmin periodically. One export before any schema
migration is a habit worth having.

## Rolling back

The live folder is a copy, so redeploying an older commit restores the old code:

```bash
git revert HEAD
git push
```

Then Update from Remote and Deploy again. Code rollbacks do not undo database
changes, which is why migrations should be additive wherever possible.
