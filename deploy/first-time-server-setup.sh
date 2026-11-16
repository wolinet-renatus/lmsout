#!/usr/bin/env bash
# ==============================================================================
# LMSOUT First-Time Live Server Setup Script
# Target Live URL: https://lmsout.wolinet.com/
#
# Run once on a fresh Linux VPS / cPanel SSH shell:
#   bash deploy/first-time-server-setup.sh \
#     --app-url="https://lmsout.wolinet.com" \
#     --db-host="127.0.0.1" \
#     --db-port="3306" \
#     --db-name="lmsout" \
#     --db-user="lmsout_user" \
#     --db-pass="YOUR_DB_PASSWORD"
#
# Or export variables in your shell and run:
#   bash deploy/first-time-server-setup.sh
# ==============================================================================
set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
cd "${APP_DIR}"

PHP_BIN="${PHP_BIN:-$(command -v php || true)}"
APP_URL="${APP_URL:-https://lmsout.wolinet.com}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-}"
DB_USER="${DB_USER:-}"
DB_PASS="${DB_PASS:-}"
SQL_DUMP="${SQL_DUMP:-}"
SKIP_CRON="false"
SKIP_SEED="false"

for arg in "$@"; do
  case "${arg}" in
    --php=*) PHP_BIN="${arg#*=}" ;;
    --app-url=*) APP_URL="${arg#*=}" ;;
    --db-host=*) DB_HOST="${arg#*=}" ;;
    --db-port=*) DB_PORT="${arg#*=}" ;;
    --db-name=*) DB_NAME="${arg#*=}" ;;
    --db-user=*) DB_USER="${arg#*=}" ;;
    --db-pass=*) DB_PASS="${arg#*=}" ;;
    --with-sql=*) SQL_DUMP="${arg#*=}" ;;
    --skip-cron) SKIP_CRON="true" ;;
    --skip-seed) SKIP_SEED="true" ;;
    -h|--help)
      sed -n '2,18p' "$0"
      exit 0
      ;;
    *)
      echo "Unknown option: ${arg}" >&2
      exit 1
      ;;
  esac
done

if [[ -z "${PHP_BIN}" ]] || ! command -v "${PHP_BIN}" >/dev/null 2>&1; then
  echo "ERROR: PHP binary not found. Pass --php=/path/to/php." >&2
  exit 1
fi

echo "==> Checking PHP extensions (8.3+ recommended)..."
"${PHP_BIN}" -v
REQUIRED_EXTS=(mbstring openssl pdo pdo_mysql tokenizer xml ctype json bcmath fileinfo curl zip gd)
for ext in "${REQUIRED_EXTS[@]}"; do
  if ! "${PHP_BIN}" -m | grep -iq "^${ext}$"; then
    echo "WARNING: PHP extension '${ext}' may not be loaded." >&2
  fi
done

set_env_key() {
  local key="$1"
  local val="$2"
  local file="${APP_DIR}/.env"
  local escaped
  escaped="$(printf '%s' "${val}" | sed 's/[&/\]/\\&/g')"
  if grep -qE "^${key}=" "${file}"; then
    sed -i.bak "s|^${key}=.*|${key}=${escaped}|" "${file}" && rm -f "${file}.bak"
  else
    printf '\n%s=%s\n' "${key}" "${val}" >> "${file}"
  fi
}

if [[ ! -f "${APP_DIR}/.env" ]]; then
  echo "==> Creating .env from .env.production.example..."
  cp "${APP_DIR}/.env.production.example" "${APP_DIR}/.env"
  chmod 600 "${APP_DIR}/.env"
fi

set_env_key "APP_ENV" "production"
set_env_key "APP_DEBUG" "false"
set_env_key "APP_URL" "${APP_URL}"
set_env_key "DB_CONNECTION" "mysql"
set_env_key "DB_HOST" "${DB_HOST}"
set_env_key "DB_PORT" "${DB_PORT}"
[[ -n "${DB_NAME}" ]] && set_env_key "DB_DATABASE" "${DB_NAME}"
[[ -n "${DB_USER}" ]] && set_env_key "DB_USERNAME" "${DB_USER}"
[[ -n "${DB_PASS}" ]] && set_env_key "DB_PASSWORD" "${DB_PASS}"

if grep -qE '^APP_KEY=$' "${APP_DIR}/.env" || ! grep -qE '^APP_KEY=base64:' "${APP_DIR}/.env"; then
  echo "==> Preparing storage directories & installing Composer packages before key:generate..."
  mkdir -p storage/framework/{cache/data,sessions,views,testing} storage/logs storage/app/public bootstrap/cache
  chmod -R ug+rwX storage bootstrap/cache
  if [[ ! -f "${APP_DIR}/vendor/autoload.php" ]]; then
    COMPOSER_BIN="$(command -v composer || true)"
    if [[ -n "${COMPOSER_BIN}" ]]; then
      "${PHP_BIN}" "${COMPOSER_BIN}" install --no-dev --prefer-dist --optimize-autoloader --no-interaction
    elif [[ -f "${APP_DIR}/composer.phar" ]]; then
      "${PHP_BIN}" "${APP_DIR}/composer.phar" install --no-dev --prefer-dist --optimize-autoloader --no-interaction
    fi
  fi
  if [[ -f "${APP_DIR}/vendor/autoload.php" ]]; then
    "${PHP_BIN}" artisan key:generate --force --no-interaction
  fi
fi

# Optionally create database if credentials have CREATE DATABASE rights
if [[ -n "${DB_NAME}" ]] && [[ -n "${DB_USER}" ]] && command -v mysql >/dev/null 2>&1; then
  MYSQL_PWD="${DB_PASS}" mysql -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USER}" \
    -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" \
    >/dev/null 2>&1 || true
fi

DEPLOY_ARGS=(--no-git --php="${PHP_BIN}")
[[ -n "${SQL_DUMP}" ]] && DEPLOY_ARGS+=(--with-sql="${SQL_DUMP}")
[[ "${SKIP_SEED}" != "true" ]] && DEPLOY_ARGS+=(--seed)

echo "==> Running deploy/deploy.sh ${DEPLOY_ARGS[*]} ..."
bash "${SCRIPT_DIR}/deploy.sh" "${DEPLOY_ARGS[@]}"

if [[ "${SKIP_CRON}" != "true" ]] && command -v crontab >/dev/null 2>&1; then
  CRON_LINE="* * * * * cd ${APP_DIR} && ${PHP_BIN} artisan schedule:run >> /dev/null 2>&1"
  EXISTING_CRON="$(crontab -l 2>/dev/null || true)"
  if ! printf '%s\n' "${EXISTING_CRON}" | grep -Fq "${APP_DIR} && ${PHP_BIN} artisan schedule:run"; then
    (printf '%s\n' "${EXISTING_CRON}"; printf '%s\n' "${CRON_LINE}") | awk 'NF' | crontab - || true
    echo "==> Installed Laravel scheduler cron entry."
  fi
fi

echo ""
echo "Setup complete! Live URL: ${APP_URL}"
echo "Seeded Demo Accounts (password: password — change on production after testing!):"
echo "  - Super Admin : superadmin@lsmout.ac.tz"
echo "  - Admin       : admin@lsmout.ac.tz"
echo "  - Instructor  : instructor@lsmout.ac.tz"
echo "  - Student     : student@lsmout.ac.tz"
