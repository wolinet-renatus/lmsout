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

## 2. CyberPanel One-Shot Terminal Setup (`/home/lmsout.wolinet.com/public_html`)

CyberPanel's `public_html` directory already exists with a default placeholder file, so initialize Git directly inside `/home/lmsout.wolinet.com/public_html` and run `first-time-server-setup.sh`:

```bash
cd /home/lmsout.wolinet.com/public_html
git init -b main
git remote add origin https://github.com/wolinet-renatus/lmsout.git
git fetch origin main --prune
git checkout -f -B main origin/main
bash deploy/first-time-server-setup.sh --app-url="https://lmsout.wolinet.com"
```

What `first-time-server-setup.sh` + `deploy.sh` do automatically on CyberPanel:
1. Auto-detects CyberPanel PHP `8.3+` (`/usr/local/lsws/lsphp83/bin/php`, `lsphp84`, `lsphp85`, or `php`).
2. Prompts securely (`read -s`) for **Database Name**, **Database User**, and **Database Password** and writes `.env` (`chmod 600`), safely quoting special characters (`!`, `$`, `#`).
3. Installs Composer (`composer` or auto-fetched `composer.phar`) dependencies (`--no-dev --optimize-autoloader`), generates `APP_KEY`, and runs `npm run build` if npm is installed (preserving git-tracked `public/assets/*`, `public/themes/*`, and `storage/app/public/*`).
4. Verifies MySQL PDO connectivity before migrating.
5. Runs `php artisan migrate --force` and `php artisan db:seed --force` (all 12 RBAC roles + 42 permissions, 239 countries, 17 OUT regional branches & 17 labs, all 16 OUT face-to-face short courses in `TZS` + 6 online courses = 22 courses, payment gateways including Pesapal, OUT branding assets, menus, custom pages, demo accounts, and paid + pending GePG invoices).
6. Links `public/storage -> ../storage/app/public`, sets writable permissions on `storage/` & `bootstrap/cache/`, restores CyberPanel vHost ownership if run as `root`, caches config/routes/views, installs cron, and tests `https://lmsout.wolinet.com/`.

---

## 3. Private Mac-to-Server Sync of Exact Local Database & Assets

If you want your **exact local MAMP MySQL dataset** and uploaded files mirrored privately over SSH without ever exposing PII/credentials on public GitHub, run from your local Mac:

```bash
bash deploy/push-db-and-assets-to-server.sh \
  --ssh="root@lmsout.wolinet.com" \
  --port="22" \
  --deploy-path="/home/lmsout.wolinet.com/public_html" \
  --remote-php="/usr/local/lsws/lsphp83/bin/php"
```

---

## 4. Routine Server Terminal Redeploy

```bash
cd /home/lmsout.wolinet.com/public_html
bash deploy/deploy.sh --seed
```
