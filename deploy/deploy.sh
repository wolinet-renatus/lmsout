#!/usr/bin/env bash
# ==============================================================================
# LMSOUT Automated Production Deployment Script
# Target Live URL: https://lmsout.wolinet.com/
# Usage:
#   bash deploy/deploy.sh
#   bash deploy/deploy.sh --seed
#   bash deploy/deploy.sh --branch=main --seed --php=/usr/local/bin/php
#   bash deploy/deploy.sh --with-sql=/path/to/seed.sql
# ==============================================================================
set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
cd "${APP_DIR}"

BRANCH="${DEPLOY_BRANCH:-main}"
PHP_BIN="${PHP_BIN:-$(command -v php || true)}"
COMPOSER_BIN="${COMPOSER_BIN:-$(command -v composer || true)}"
NPM_BIN="${NPM_BIN:-$(command -v npm || true)}"
RUN_SEED="${RUN_SEED:-false}"
FRESH_SEED="false"
FRESH_CONFIRMED="false"
NO_GIT="false"
SKIP_BUILD="false"
SKIP_HEALTH="false"
SQL_DUMP=""
APP_URL_DEFAULT="https://lmsout.wolinet.com"

for arg in "$@"; do
  case "${arg}" in
    --branch=*) BRANCH="${arg#*=}" ;;
    --php=*) PHP_BIN="${arg#*=}" ;;
    --composer=*) COMPOSER_BIN="${arg#*=}" ;;
    --npm=*) NPM_BIN="${arg#*=}" ;;
    --with-sql=*) SQL_DUMP="${arg#*=}" ;;
    --seed) RUN_SEED="true" ;;
    --fresh-seed) FRESH_SEED="true" ;;
    --i-am-sure) FRESH_CONFIRMED="true" ;;
    --no-git) NO_GIT="true" ;;
    --skip-build) SKIP_BUILD="true" ;;
    --skip-health-check) SKIP_HEALTH="true" ;;
    -h|--help)
      sed -n '2,12p' "$0"
      exit 0
      ;;
    *)
      echo "Unknown option: ${arg}" >&2
      exit 1
      ;;
  esac
done

log() {
  printf '\n[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*"
}

if [[ -z "${PHP_BIN}" ]] || ! command -v "${PHP_BIN}" >/dev/null 2>&1; then
  echo "ERROR: PHP binary not found. Pass --php=/path/to/php or set PHP_BIN." >&2
  exit 1
fi

if [[ ! -f "${APP_DIR}/.env" ]]; then
  echo "ERROR: .env not found in ${APP_DIR}." >&2
  echo "Run first-time setup first: bash deploy/first-time-server-setup.sh" >&2
  exit 1
fi

read_env() {
  local key="$1"
  local val
  val="$(grep -E "^${key}=" "${APP_DIR}/.env" | tail -n 1 | cut -d '=' -f2- || true)"
  val="${val%\"}"
  val="${val#\"}"
  val="${val%\'}"
  val="${val#\'}"
  printf '%s' "${val}"
}

APP_URL="$(read_env APP_URL)"
APP_URL="${APP_URL:-${APP_URL_DEFAULT}}"

MAINT_ACTIVE="false"
cleanup() {
  if [[ "${MAINT_ACTIVE}" == "true" ]]; then
    "${PHP_BIN}" artisan up >/dev/null 2>&1 || true
  fi
}
trap cleanup EXIT

log "Starting deployment in ${APP_DIR} (branch=${BRANCH}, php=${PHP_BIN})"

# Ensure writable framework & cache directories exist upfront
mkdir -p \
  storage/app/public \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/testing \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

# 1. Enter maintenance mode cleanly (tolerates unbuilt vendor on initial run)
if [[ -f "${APP_DIR}/vendor/autoload.php" ]]; then
  if "${PHP_BIN}" artisan down --retry=60 --refresh=15 >/dev/null 2>&1; then
    MAINT_ACTIVE="true"
    log "Maintenance mode enabled."
  fi
fi

# 2. Pull latest repository changes
if [[ "${NO_GIT}" != "true" ]] && [[ -d "${APP_DIR}/.git" ]]; then
  log "Fetching and syncing git origin/${BRANCH}..."
  git fetch origin "${BRANCH}" --prune
  git checkout -B "${BRANCH}" "origin/${BRANCH}"
  git reset --hard "origin/${BRANCH}"
fi

# 3. Install production PHP dependencies
if [[ -z "${COMPOSER_BIN}" ]] && command -v "${PHP_BIN}" >/dev/null 2>&1 && [[ -f "${APP_DIR}/composer.phar" ]]; then
  COMPOSER_CMD=("${PHP_BIN}" "${APP_DIR}/composer.phar")
elif [[ -n "${COMPOSER_BIN}" ]]; then
  COMPOSER_CMD=("${PHP_BIN}" "$(command -v "${COMPOSER_BIN}")")
else
  echo "ERROR: Composer binary not found. Install composer or place composer.phar in ${APP_DIR}." >&2
  exit 1
fi

log "Installing Composer dependencies (--no-dev --optimize-autoloader)..."
"${COMPOSER_CMD[@]}" install --no-dev --prefer-dist --optimize-autoloader --no-interaction

# 4. Build frontend Vite assets if Node/npm is present (Blade also ships compiled public/assets/*)
if [[ "${SKIP_BUILD}" != "true" ]] && [[ -n "${NPM_BIN}" ]] && [[ -f "${APP_DIR}/package.json" ]]; then
  log "Building frontend assets with npm..."
  if [[ -f "${APP_DIR}/package-lock.json" ]]; then
    "${NPM_BIN}" ci --no-audit --no-fund
  else
    "${NPM_BIN}" install --no-audit --no-fund
  fi
  "${NPM_BIN}" run build
else
  log "Skipping npm asset build (prebuilt public/assets/* and public/themes/* are preserved)."
fi

# 5. Optional SQL snapshot import (e.g., first live test load)
if [[ -n "${SQL_DUMP}" ]]; then
  if [[ ! -f "${SQL_DUMP}" ]]; then
    echo "ERROR: SQL dump file not found: ${SQL_DUMP}" >&2
    exit 1
  fi
  DB_CONN="$(read_env DB_CONNECTION)"
  if [[ "${DB_CONN}" != "mysql" ]] && [[ "${DB_CONN}" != "mariadb" ]]; then
    echo "ERROR: --with-sql only supports mysql/mariadb connections (found ${DB_CONN})." >&2
    exit 1
  fi
  DB_HOST="$(read_env DB_HOST)"; DB_HOST="${DB_HOST:-127.0.0.1}"
  DB_PORT="$(read_env DB_PORT)"; DB_PORT="${DB_PORT:-3306}"
  DB_NAME="$(read_env DB_DATABASE)"
  DB_USER="$(read_env DB_USERNAME)"
  DB_PASS="$(read_env DB_PASSWORD)"
  if ! command -v mysql >/dev/null 2>&1; then
    echo "ERROR: mysql client not installed; cannot import ${SQL_DUMP}." >&2
    exit 1
  fi
  log "Importing SQL snapshot ${SQL_DUMP} into database ${DB_NAME}..."
  MYSQL_PWD="${DB_PASS}" mysql -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USER}" "${DB_NAME}" < "${SQL_DUMP}"
fi

# 6. Run database migrations & optional seeding
if [[ "${FRESH_SEED}" == "true" ]]; then
  if [[ "${FRESH_CONFIRMED}" != "true" ]]; then
    echo "ERROR: --fresh-seed drops all tables. Re-run with --fresh-seed --i-am-sure if intentional." >&2
    exit 1
  fi
  log "Running FRESH migration + seeding (migrate:fresh --seed --force)..."
  "${PHP_BIN}" artisan migrate:fresh --seed --force --no-interaction
else
  log "Running database migrations..."
  "${PHP_BIN}" artisan migrate --force --no-interaction
  if [[ "${RUN_SEED}" == "true" ]]; then
    log "Running idempotent database seeders (db:seed --force)..."
    "${PHP_BIN}" artisan db:seed --force --no-interaction
  fi
fi

# 7. Link public/storage -> storage/app/public idempotently & fix asset permissions
log "Ensuring public/storage symlink and writable runtime permissions..."
if [[ -L public/storage ]] && [[ ! -e public/storage ]]; then
  rm -f public/storage
fi
"${PHP_BIN}" artisan storage:link --relative --force >/dev/null 2>&1 \
  || "${PHP_BIN}" artisan storage:link --force >/dev/null 2>&1 \
  || ln -sfn ../storage/app/public public/storage

chmod -R ug+rwX storage bootstrap/cache
find public/assets public/themes storage/app/public -type d -exec chmod 755 {} + 2>/dev/null || true
find public/assets public/themes storage/app/public -type f -exec chmod 644 {} + 2>/dev/null || true

# 8. Clear stale caches and warm production caches
log "Optimizing Laravel configuration, routes, views, and events..."
"${PHP_BIN}" artisan optimize:clear --no-interaction
"${PHP_BIN}" artisan config:cache --no-interaction
"${PHP_BIN}" artisan route:cache --no-interaction
"${PHP_BIN}" artisan view:cache --no-interaction
"${PHP_BIN}" artisan queue:restart --no-interaction || true

# 9. Exit maintenance mode
log "Bringing application online..."
"${PHP_BIN}" artisan up --no-interaction
MAINT_ACTIVE="false"

# 10. Post-deploy HTTP smoke check
if [[ "${SKIP_HEALTH}" != "true" ]] && command -v curl >/dev/null 2>&1; then
  HEALTH_URL="${APP_URL%/}/"
  log "Running health check on ${HEALTH_URL}..."
  HTTP_CODE="$(curl -k -s -L -o /dev/null -w '%{http_code}' --max-time 20 "${HEALTH_URL}" || echo "000")"
  echo "HTTP Status from ${HEALTH_URL}: ${HTTP_CODE}"
  if [[ ! "${HTTP_CODE}" =~ ^(200|301|302)$ ]]; then
    echo "WARNING: Unexpected HTTP status ${HTTP_CODE} from ${HEALTH_URL}. Check Apache/Nginx & storage/logs/laravel.log." >&2
  fi
fi

log "Deployment completed cleanly for ${APP_URL}."
