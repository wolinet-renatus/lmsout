#!/usr/bin/env bash
# ==============================================================================
# LMSOUT Automated Production Deployment Script
# Target Live URL: https://lmsout.wolinet.com/
#
# PUBLIC REPO SAFE: Reads server credentials from existing .env or CI/shell env.
# Usage:
#   bash deploy/deploy.sh
#   bash deploy/deploy.sh --seed
#   bash deploy/deploy.sh --with-sql=/path/to/dump.sql.gz
#   bash deploy/deploy.sh --branch=main --seed --php=/usr/local/bin/php
# ==============================================================================
set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
cd "${APP_DIR}"

BRANCH="${DEPLOY_BRANCH:-main}"
detect_php_bin() {
  for candidate in \
    "${PHP_BIN:-}" \
    "$(command -v php || true)" \
    /usr/local/lsws/lsphp85/bin/php \
    /usr/local/lsws/lsphp84/bin/php \
    /usr/local/lsws/lsphp83/bin/php \
    /usr/local/bin/ea-php83 \
    /usr/bin/php8.3; do
    if [[ -n "${candidate}" ]] && [[ -x "${candidate}" ]]; then
      if "${candidate}" -r 'exit(version_compare(PHP_VERSION, "8.3.0", ">=") ? 0 : 1);' >/dev/null 2>&1; then
        printf '%s' "${candidate}"
        return 0
      fi
    fi
  done
  command -v php || true
}
PHP_BIN="$(detect_php_bin)"
COMPOSER_BIN="${COMPOSER_BIN:-$(command -v composer || true)}"
NPM_BIN="${NPM_BIN:-$(command -v npm || true)}"
RUN_SEED="${RUN_SEED:-false}"
FRESH_SEED="false"
FRESH_CONFIRMED="false"
SYNC_ENV="false"
NO_GIT="false"
SKIP_BUILD="true"
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
    --sync-env) SYNC_ENV="true" ;;
    --fresh-seed) FRESH_SEED="true" ;;
    --i-am-sure) FRESH_CONFIRMED="true" ;;
    --no-git) NO_GIT="true" ;;
    --build-assets) SKIP_BUILD="false" ;;
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

# Optionally bootstrap/sync .env cleanly from environment variables (e.g. GitHub Secrets via SSH)
if [[ ! -f "${APP_DIR}/.env" ]] && [[ -n "${DB_DATABASE:-}" ]] && [[ -n "${DB_USERNAME:-}" ]]; then
  cp "${APP_DIR}/.env.production.example" "${APP_DIR}/.env"
  chmod 600 "${APP_DIR}/.env"
  SYNC_ENV="true"
fi

if [[ "${SYNC_ENV}" == "true" ]] && [[ -f "${APP_DIR}/.env" ]]; then
  log "Synchronizing environment configuration to .env..."
  ENV_FILE="${APP_DIR}/.env" "${PHP_BIN}" -r '
    $file = getenv("ENV_FILE");
    $content = file_get_contents($file);
    $keys = [
      "APP_ENV", "APP_DEBUG", "APP_URL", "APP_TIMEZONE", "APP_CURRENCY",
      "SESSION_SECURE_COOKIE", "DB_CONNECTION", "DB_HOST", "DB_PORT",
      "DB_DATABASE", "DB_USERNAME", "DB_PASSWORD",
      "PESAPAL_CONSUMER_KEY", "PESAPAL_CONSUMER_SECRET", "PESAPAL_IPN_ID", "PESAPAL_MODE"
    ];
    foreach ($keys as $key) {
        $val = getenv($key);
        if ($val === false || $val === "") continue;
        $needsQuotes = preg_match("/[\s#\"'\''\\\\\$!]/", $val) === 1;
        $formatted = $needsQuotes
            ? "\"" . str_replace(["\\", "\"", "\$"], ["\\\\", "\\\"", "\\\$"], $val) . "\""
            : $val;
        $pattern = "/^" . preg_quote($key, "/") . "=.*$/m";
        $line = $key . "=" . $formatted;
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, str_replace(["\\", "\$"], ["\\\\", "\\\$"], $line), $content, 1);
        } else {
            $content = rtrim($content) . PHP_EOL . $line . PHP_EOL;
        }
    }
    file_put_contents($file, $content);
  '
  chmod 600 "${APP_DIR}/.env"
fi

if [[ ! -f "${APP_DIR}/.env" ]]; then
  echo "ERROR: .env not found in ${APP_DIR}." >&2
  echo "Run: bash deploy/first-time-server-setup.sh" >&2
  exit 1
fi

# Read parsed .env key safely via PHP dotenv rules (handles quotes & special characters)
read_env() {
  ENV_FILE="${APP_DIR}/.env" "${PHP_BIN}" -r '
    $lines = @file(getenv("ENV_FILE"), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $target = $argv[1];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === "" || str_starts_with($line, "#") || !str_contains($line, "=")) continue;
        [$k, $v] = explode("=", $line, 2);
        if (trim($k) !== $target) continue;
        $v = trim($v);
        if (strlen($v) >= 2 && (($v[0] === "\"" && str_ends_with($v, "\"")) || ($v[0] === "'\''" && str_ends_with($v, "'\''")))) {
            $v = substr($v, 1, -1);
            $v = str_replace(["\\\\", "\\\"", "\\\$"], ["\\", "\"", "\$"], $v);
        }
        echo $v;
        exit(0);
    }
  ' "$1"
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

log "Starting deployment in ${APP_DIR} (branch=${BRANCH}, url=${APP_URL})"

mkdir -p \
  storage/app/public \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/testing \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

# 1. Enter maintenance mode cleanly
if [[ -f "${APP_DIR}/vendor/autoload.php" ]]; then
  if CACHE_STORE=array "${PHP_BIN}" artisan down --retry=60 --refresh=15 >/dev/null 2>&1; then
    MAINT_ACTIVE="true"
    log "Maintenance mode enabled."
  fi
fi

# 2. Pull latest repository changes
if [[ "${NO_GIT}" != "true" ]] && [[ -d "${APP_DIR}/.git" ]]; then
  log "Fetching and syncing git origin/${BRANCH}..."
  git config --global --add safe.directory "${APP_DIR}" >/dev/null 2>&1 || true
  git fetch origin "${BRANCH}" --prune
  git checkout -B "${BRANCH}" "origin/${BRANCH}"
  git reset --hard "origin/${BRANCH}"
fi

# 3. Install Composer production dependencies (auto-fetch composer.phar if missing on VPS/CyberPanel)
export COMPOSER_ALLOW_SUPERUSER=1
if [[ -z "${COMPOSER_BIN}" ]] && [[ ! -f "${APP_DIR}/composer.phar" ]]; then
  log "Composer not found globally; downloading composer.phar locally..."
  "${PHP_BIN}" -r 'copy("https://getcomposer.org/installer", "composer-setup.php");'
  "${PHP_BIN}" composer-setup.php --quiet
  rm -f composer-setup.php
fi

if [[ -f "${APP_DIR}/composer.phar" ]] && [[ -z "${COMPOSER_BIN}" ]]; then
  COMPOSER_CMD=("${PHP_BIN}" "${APP_DIR}/composer.phar")
elif [[ -n "${COMPOSER_BIN}" ]]; then
  COMPOSER_CMD=("${PHP_BIN}" "$(command -v "${COMPOSER_BIN}")")
else
  echo "ERROR: Composer binary not found. Install composer or place composer.phar in ${APP_DIR}." >&2
  exit 1
fi

log "Installing Composer dependencies (--no-dev --optimize-autoloader)..."
CACHE_STORE=array "${COMPOSER_CMD[@]}" install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-scripts

if ! grep -qE '^APP_KEY=base64:' "${APP_DIR}/.env"; then
  log "Generating missing APP_KEY..."
  CACHE_STORE=array "${PHP_BIN}" artisan key:generate --force --no-interaction
fi

# 4. Build Vite assets if Node/npm is present (prebuilt public/assets/* & public/themes/* remain available)
if [[ "${SKIP_BUILD}" != "true" ]] && [[ -n "${NPM_BIN}" ]] && [[ -f "${APP_DIR}/package.json" ]]; then
  log "Building frontend assets with npm..."
  if [[ -f "${APP_DIR}/package-lock.json" ]]; then
    "${NPM_BIN}" ci --no-audit --no-fund
  else
    "${NPM_BIN}" install --no-audit --no-fund
  fi
  "${NPM_BIN}" run build
else
  log "Skipping npm build (prebuilt public/assets/*, public/themes/* & storage/app/public/* active)."
fi

# 5. Optional SQL snapshot import (.sql or .sql.gz)
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
  if [[ "${SQL_DUMP}" == *.gz ]]; then
    gzip -dc "${SQL_DUMP}" | MYSQL_PWD="${DB_PASS}" mysql -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USER}" "${DB_NAME}"
  else
    MYSQL_PWD="${DB_PASS}" mysql -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USER}" "${DB_NAME}" < "${SQL_DUMP}"
  fi
fi

# 6. Run database migrations & optional seeding
if [[ "${FRESH_SEED}" == "true" ]]; then
  if [[ "${FRESH_CONFIRMED}" != "true" ]]; then
    echo "ERROR: --fresh-seed drops all tables. Re-run with --fresh-seed --i-am-sure if intentional." >&2
    exit 1
  fi
  log "Running FRESH migration + seeding (migrate:fresh --seed --force)..."
  CACHE_STORE=array "${PHP_BIN}" artisan migrate:fresh --seed --force --no-interaction
else
  log "Running database migrations..."
  CACHE_STORE=array "${PHP_BIN}" artisan migrate --force --no-interaction
  CACHE_STORE=array "${PHP_BIN}" artisan package:discover --ansi --no-interaction
  if [[ "${RUN_SEED}" == "true" ]]; then
    log "Running idempotent database seeders (db:seed --force)..."
    "${PHP_BIN}" artisan db:seed --force --no-interaction
  fi
fi

# 7. Link public/storage -> storage/app/public idempotently & fix permissions on all assets
log "Linking public/storage and verifying runtime & asset permissions..."
if [[ -L public/storage ]] && [[ ! -e public/storage ]]; then
  rm -f public/storage
fi
"${PHP_BIN}" artisan storage:link --relative --force >/dev/null 2>&1 \
  || "${PHP_BIN}" artisan storage:link --force >/dev/null 2>&1 \
  || ln -sfn ../storage/app/public public/storage

# Remove leftover CyberPanel/cPanel placeholder index.html if present in root
rm -f "${APP_DIR}/index.html" "${APP_DIR}/index.htm"

chmod -R ug+rwX storage bootstrap/cache
find public/assets public/themes storage/app/public -type d -exec chmod 755 {} + 2>/dev/null || true
find public/assets public/themes storage/app/public -type f -exec chmod 644 {} + 2>/dev/null || true

# 8. Clear stale caches and warm production caches
log "Optimizing Laravel caches..."
"${PHP_BIN}" artisan optimize:clear --no-interaction
"${PHP_BIN}" artisan config:cache --no-interaction
"${PHP_BIN}" artisan route:cache --no-interaction
"${PHP_BIN}" artisan view:cache --no-interaction
"${PHP_BIN}" artisan queue:restart --no-interaction || true

# 9. Exit maintenance mode
log "Bringing application online..."
"${PHP_BIN}" artisan up --no-interaction
MAINT_ACTIVE="false"

# On CyberPanel/cPanel when SSH runs as root, restore vHost site user ownership after all caches/views are generated
if [[ "$(id -u)" -eq 0 ]] && command -v stat >/dev/null 2>&1; then
  PARENT_OWNER="$(stat -c '%U:%G' "$(dirname "${APP_DIR}")" 2>/dev/null || true)"
  if [[ -n "${PARENT_OWNER}" ]] && [[ "${PARENT_OWNER}" != "root:root" ]] && [[ "${PARENT_OWNER}" != ":" ]]; then
    log "Restoring CyberPanel/vHost ownership (${PARENT_OWNER})..."
    chown -R "${PARENT_OWNER}" "${APP_DIR}"
  fi
fi

# OpenLiteSpeed caches .htaccess in memory until gracefully restarted
if [[ "$(id -u)" -eq 0 ]] && [[ -x /usr/local/lsws/bin/lswsctrl ]]; then
  log "Reloading OpenLiteSpeed so .htaccess & PHP caches refresh cleanly..."
  /usr/local/lsws/bin/lswsctrl restart >/dev/null 2>&1 || true
fi

# 10. Post-deploy HTTP smoke check
if [[ "${SKIP_HEALTH}" != "true" ]] && command -v curl >/dev/null 2>&1; then
  HEALTH_URL="${APP_URL%/}/"
  HEALTH_HOST="$(printf '%s' "${HEALTH_URL}" | sed -E 's|^https?://([^/:]+).*|\1|')"
  log "Running HTTP health check on ${HEALTH_URL}..."
  HTTP_CODE="$(curl -k -s -L -o /dev/null -w '%{http_code}' --max-time 20 "${HEALTH_URL}" 2>/dev/null)" || true
  if [[ "${HTTP_CODE}" == "000" || -z "${HTTP_CODE}" ]]; then
    # Retry resolving domain to 127.0.0.1 in case VPS NAT/firewall blocks self-loopback via public IP
    HTTP_CODE="$(curl -k -s -L -o /dev/null -w '%{http_code}' --resolve "${HEALTH_HOST}:443:127.0.0.1" --resolve "${HEALTH_HOST}:80:127.0.0.1" --max-time 20 "${HEALTH_URL}" 2>/dev/null)" || true
  fi
  HTTP_CODE="${HTTP_CODE:-000}"
  echo "HTTP Status from ${HEALTH_URL}: ${HTTP_CODE}"
  if [[ ! "${HTTP_CODE}" =~ ^(200|302)$ ]]; then
    echo "WARNING: Unexpected HTTP status ${HTTP_CODE} from ${HEALTH_URL}. Recent laravel.log lines:" >&2
    tail -n 20 storage/logs/laravel.log 2>/dev/null || true
  fi
fi

log "Deployment completed cleanly for ${APP_URL}."
