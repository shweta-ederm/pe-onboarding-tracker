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

### 2. Where it lives on the server

**Chosen: a subfolder**, serving the app at `https://yourdomain.com/onboarding`.
Deploy target:

```
/home/USERNAME/public_html/onboarding
```

The root `.htaccess` forwards every request that is not a real file into
`public/`, so `/public/` never appears in the URL. The `.htaccess` file in each
of `src/`, `config/`, `templates/`, `db/`, and `tools/` denies direct access.

Two things are worth knowing about this layout. First, protection of the source
folders comes from Apache config rather than from directory structure, so it is
worth running the checks at the end of this document once. Second, even if
those deny rules were ignored, a request for a `.php` file would execute it
rather than print it, and every file under `src/` only defines classes, so
nothing would be disclosed. The files that would genuinely leak as plain text
are the `.sql`, `.md`, and `.yml` ones, which is why the root `.htaccess` blocks
those extensions outright as a second layer.

**If you later want the safer structure**, create the subdomain
`onboarding.yourdomain.com` with its document root set to
`/home/USERNAME/onboarding/public`, change `DEPLOYPATH` in `.cpanel.yml` to
`/home/USERNAME/onboarding`, and redeploy. Nothing in the application code has
to change.

### 2b. Set the PHP version

cPanel → **Select PHP Version**. GoDaddy still defaults some accounts to PHP
7.4, and this application needs **8.0 or newer**. Pick 8.1 or 8.2.

While you are on that screen, confirm the `pdo_mysql` extension is ticked. It
normally is. If PHP is too old the app says so in plain language rather than
failing with a blank page, so this is easy to spot later, but setting it now
saves a confusing first load.

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
- export DEPLOYPATH=/home/USERNAME/public_html/onboarding
```

Replace `USERNAME` with your real cPanel username, which is shown in cPanel's
General Information panel. Commit and push that change before deploying, since
cPanel reads `.cpanel.yml` from the server's copy of the repository, not from
your machine.

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

- Visit these four URLs directly. Every one must return 403 or 404, and none
  may show you file contents:
  `/onboarding/db/schema.sql`, `/onboarding/README.md`,
  `/onboarding/.cpanel.yml`, `/onboarding/config/config.sample.php`.
  If any of them downloads or displays, `.htaccess` is not being honoured on
  this account and you should move to the subdomain layout described in step 2.
- Visit `/onboarding/public/index.php` directly. It will work, which is
  expected and harmless; it is the same application at a second URL.
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
