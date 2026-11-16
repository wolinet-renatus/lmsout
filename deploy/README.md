# LMSOUT Deployment & CI/CD Guide (`https://lmsout.wolinet.com/`)

> **Security Notice (Public Repository):** This repository is public. Never commit live `.env` credentials, database passwords, API keys, or real student/applicant SQL dumps to Git. All scripts below configure credentials strictly on your server or via encrypted GitHub Secrets.

---

## 1. Automated CI/CD Workflows (GitHub Actions)

Two workflows live under `.github/workflows/`:

- **`ci.yml`** — runs on every `push` and `pull_request` to `main`:
  - Boots PHP `8.3`, Node `20`, and a fresh MySQL `8.0` service (`lmsout_ci`).
  - Installs Composer and npm dependencies and runs `npm run build`.
  - Runs `php -l` across application files, runs `php artisan migrate --force && php artisan db:seed --force`, warms `php artisan view:cache`, and executes all 5 core feature test suites.
- **`deploy.yml`** — deploys automatically to `https://lmsout.wolinet.com/` when `CI` succeeds on `main` (or manually via **Actions -> Deploy to Production -> Run workflow**).

### GitHub Secrets & Variables (`Settings -> Secrets and variables -> Actions`)

| Kind | Name | Purpose |
| :--- | :--- | :--- |
| Secret | `SSH_HOST` | Live server IP or hostname (e.g. `lmsout.wolinet.com`) |
| Secret | `SSH_USER` | SSH username on server |
| Secret | `SSH_PORT` | SSH port (default `22`) |
| Secret | `SSH_PRIVATE_KEY` | Private SSH key paired with server `~/.ssh/authorized_keys` |
| Secret | `DEPLOY_PATH` | Absolute server path (e.g. `/home/<user>/public_html` or `/var/www/lmsout`) |
| Secret *(optional)* | `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Lets CD bootstrap/sync `.env` securely over SSH without storing credentials in Git |
| Secret *(optional)* | `PESAPAL_CONSUMER_KEY`, `PESAPAL_CONSUMER_SECRET`, `PESAPAL_IPN_ID` | Production Pesapal credentials |
| Variable *(optional)* | `RUN_SEED`, `SYNC_ENV`, `APP_URL`, `PESAPAL_MODE` | Set `RUN_SEED=true` for first deploy or `SYNC_ENV=true` when updating secrets |

---

## 2. Interactive Server Setup (Configures `.env`, Migrates & Seeds Everything)

SSH into your server (`lmsout.wolinet.com`), clone the repository, and run the provisioning script interactively (so your DB password stays hidden and out of Git/shell history):

```bash
git clone -b main https://github.com/wolinet-renatus/lmsout.git /var/www/lmsout
cd /var/www/lmsout
bash deploy/first-time-server-setup.sh --app-url="https://lmsout.wolinet.com"
```

What it configures and deploys automatically:
1. Prompts securely (`read -s`) for **Database Name**, **Database User**, and **Database Password** (or reads them from flags/env vars) and writes `.env` safely (`chmod 600`), properly quoting special characters like `!` or `$`.
2. Installs Composer dependencies (`--no-dev --optimize-autoloader`), generates `APP_KEY`, and builds Vite frontend assets (`npm ci && npm run build`) while preserving tracked `public/assets/*`, `public/themes/*`, and `storage/app/public/*`.
3. Verifies MySQL PDO connectivity before migrating.
4. Runs `php artisan migrate --force` and `php artisan db:seed --force` (all 12 RBAC roles + 42 permissions, 239 countries, 17 OUT regional branches & 17 computer labs, all 16 official OUT face-to-face short courses in TZS + 6 online courses = 22 courses, payment gateways including Pesapal, OUT branding assets, menus, custom pages, demo users, and sample paid + pending GePG invoices & payment schedules).
5. Links `public/storage -> ../storage/app/public`, sets writable permissions on `storage/` & `bootstrap/cache/`, caches config/routes/views, installs the Laravel scheduler cron job, and verifies HTTP response from `https://lmsout.wolinet.com/`.

---

## 3. Private Mac-to-Server Sync of Exact Local Database & Uploaded Assets

If you want your **exact local MAMP MySQL dataset** (every local record & setting) and `storage/app/public` assets mirrored to the live server privately without ever uploading data to public GitHub, run this from your local Mac workspace:

```bash
bash deploy/push-db-and-assets-to-server.sh \
  --ssh="YOUR_SSH_USER@lmsout.wolinet.com" \
  --port="22" \
  --deploy-path="/path/on/server/lmsout"
```

---

## 4. Routine Server Terminal Redeploy

```bash
cd /path/on/server/lmsout
bash deploy/deploy.sh --seed
```
