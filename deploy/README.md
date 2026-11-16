# LMSOUT Deployment & CI/CD Guide (`https://lmsout.wolinet.com/`)

## 1. Automated CI/CD Workflows (GitHub Actions)

Two workflows are included under `.github/workflows/`:

- **`ci.yml`** — runs automatically on every `push` and `pull_request` to `main`:
  - Boots PHP `8.3`, Node `20`, and a fresh MySQL `8.0` service database.
  - Installs Composer and npm dependencies and runs `npm run build`.
  - Runs `php artisan migrate --force && php artisan db:seed --force` on the blank CI MySQL database.
  - Runs PHP syntax checks (`php -l`), `php artisan view:cache`, and the core feature test suites (`CampusUnifiedWorkflowTest`, `ScmsFullParityAndRbacTest`, `CurrencyExchangeSwitchTest`, `PesapalIntegrationTest`, `StudentCourseAccessTest`).
- **`deploy.yml`** — runs automatically after `CI` succeeds on `main` (or manually via **Actions -> Deploy to Production -> Run workflow**):
  - Connects via SSH and runs `bash deploy/deploy.sh` inside your live server directory.

### Required GitHub Repository Secrets

Add these in **GitHub -> Settings -> Secrets and variables -> Actions**:

| Secret | Example / Description |
| :--- | :--- |
| `SSH_HOST` | Server IP or hostname (e.g. `lmsout.wolinet.com`) |
| `SSH_USER` | SSH username (e.g. `wolinet` or `deploy`) |
| `SSH_PORT` | SSH port (default `22`) |
| `SSH_PRIVATE_KEY` | Private SSH key authorized in `~/.ssh/authorized_keys` on the server |
| `DEPLOY_PATH` | Absolute server path to app root (e.g. `/home/wolinet/public_html` or `/var/www/lmsout`) |

Optional repository variable (`Settings -> Variables -> Actions`):
- `RUN_SEED`: set to `true` if you want automated CD runs to re-run idempotent seeders (`php artisan db:seed --force`). Leave unset / `false` for routine code updates after initial seeding.

---

## 2. One-Command First-Time Terminal Deployment (on Live Server)

SSH into your server (`lmsout.wolinet.com`), navigate to your target directory (e.g. `/var/www/lmsout` or `/home/wolinet/lmsout`), clone the repository, and run the provisioning script:

```bash
git clone -b main https://github.com/wolinet-renatus/lmsout.git /var/www/lmsout
cd /var/www/lmsout
bash deploy/first-time-server-setup.sh \
  --app-url="https://lmsout.wolinet.com" \
  --db-host="127.0.0.1" \
  --db-port="3306" \
  --db-name="YOUR_DB_NAME" \
  --db-user="YOUR_DB_USER" \
  --db-pass="YOUR_DB_PASSWORD"
```

What `first-time-server-setup.sh` does automatically:
1. Creates `.env` from `.env.production.example` for `https://lmsout.wolinet.com`.
2. Installs Composer dependencies (`--no-dev --optimize-autoloader`) and generates `APP_KEY`.
3. Builds frontend assets (`npm ci && npm run build`) if Node/npm is present (prebuilt `public/assets/*`, `public/themes/*`, and `storage/app/public/*` are also tracked in Git).
4. Runs `php artisan migrate --force` and `php artisan db:seed --force` (seeds roles, 239 countries, 17 OUT regional branches & venues, demo accounts across all RBAC roles, payment gateways, platform & frontend settings, menus, custom pages, demo courses, and a sample GePG campus invoice/timetable).
5. Creates `public/storage -> ../storage/app/public` symlink, sets permissions on `storage/` and `bootstrap/cache/`, caches config/routes/views, and installs the Laravel scheduler cron job.

---

## 3. Subsequent Terminal Deployments (Manual Pull + Assets + Migrate)

Anytime you want to deploy manually from the server terminal:

```bash
cd /var/www/lmsout
bash deploy/deploy.sh
```

Useful flags:
- `bash deploy/deploy.sh --seed` — also runs `php artisan db:seed --force` (safe & idempotent).
- `bash deploy/deploy.sh --with-sql=/path/to/snapshot.sql` — imports a MySQL dump before running pending migrations.
- `bash deploy/deploy.sh --php=/usr/local/bin/ea-php83` — specifies an alternate cPanel/EasyApache PHP binary.

---

## 4. Web Server Document Root

- **Nginx / VPS Apache (recommended):** point your virtual host `DocumentRoot` to `<DEPLOY_PATH>/public` and enable HTTPS for `lmsout.wolinet.com`.
- **cPanel Shared Hosting (`public_html`):** root `.htaccess` is included and forwards requests cleanly into `public/`. Ensure PHP `8.3+` is selected in MultiPHP Manager.
