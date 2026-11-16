#!/usr/bin/env bash
# ==============================================================================
# LMSOUT First-Time Live Server Provisioning & Credential Setup Script
# Target Live URL: https://lmsout.wolinet.com/
#
# PUBLIC REPO SAFE: Never commit real passwords or keys to Git.
# Run directly on the live server over SSH:
#   bash deploy/first-time-server-setup.sh
# Or supply flags / environment variables (use single quotes if password has '!'):
#   DB_DATABASE='...' DB_USERNAME='...' DB_PASSWORD='...' \
#     bash deploy/first-time-server-setup.sh --app-url="https://lmsout.wolinet.com"
# ==============================================================================
set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
cd "${APP_DIR}"

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
APP_URL="${APP_URL:-https://lmsout.wolinet.com}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_DATABASE:-${DB_NAME:-}}"
DB_USER="${DB_USERNAME:-${DB_USER:-}}"
DB_PASS="${DB_PASSWORD:-${DB_PASS:-}}"
PESAPAL_KEY="${PESAPAL_CONSUMER_KEY:-}"
PESAPAL_SECRET="${PESAPAL_CONSUMER_SECRET:-}"
PESAPAL_IPN="${PESAPAL_IPN_ID:-}"
PESAPAL_MODE="${PESAPAL_MODE:-sandbox}"
SQL_DUMP="${SQL_DUMP:-}"
SKIP_CRON="false"
SKIP_SEED="false"
NON_INTERACTIVE="false"

for arg in "$@"; do
  case "${arg}" in
    --php=*) PHP_BIN="${arg#*=}" ;;
    --app-url=*) APP_URL="${arg#*=}" ;;
    --db-host=*) DB_HOST="${arg#*=}" ;;
    --db-port=*) DB_PORT="${arg#*=}" ;;
    --db-name=*) DB_NAME="${arg#*=}" ;;
    --db-user=*) DB_USER="${arg#*=}" ;;
    --db-pass=*) DB_PASS="${arg#*=}" ;;
    --pesapal-key=*) PESAPAL_KEY="${arg#*=}" ;;
    --pesapal-secret=*) PESAPAL_SECRET="${arg#*=}" ;;
    --pesapal-ipn=*) PESAPAL_IPN="${arg#*=}" ;;
    --pesapal-mode=*) PESAPAL_MODE="${arg#*=}" ;;
    --with-sql=*) SQL_DUMP="${arg#*=}" ;;
    --skip-cron) SKIP_CRON="true" ;;
    --skip-seed) SKIP_SEED="true" ;;
    --non-interactive) NON_INTERACTIVE="true" ;;
    -h|--help)
      sed -n '2,15p' "$0"
      exit 0
      ;;
    *)
      echo "Unknown option: ${arg}" >&2
      exit 1
      ;;
  esac
done

if [[ -z "${PHP_BIN}" ]] || ! command -v "${PHP_BIN}" >/dev/null 2>&1; then
  echo "ERROR: PHP binary not found. Pass --php=/path/to/php (e.g., /usr/local/bin/ea-php83)." >&2
  exit 1
fi

# Interactive prompt when running in terminal & credentials were not pre-supplied
if [[ "${NON_INTERACTIVE}" != "true" ]] && [[ -t 0 ]]; then
  echo "=== LMSOUT Server Environment Setup (${APP_URL}) ==="
  if [[ -z "${DB_NAME}" ]]; then
    read -r -p "MySQL Database Name: " DB_NAME
  fi
  if [[ -z "${DB_USER}" ]]; then
    read -r -p "MySQL Database User: " DB_USER
  fi
  if [[ -z "${DB_PASS}" ]]; then
    read -r -s -p "MySQL Database Password (hidden): " DB_PASS
    echo ""
  fi
fi

if [[ -z "${DB_NAME}" ]] || [[ -z "${DB_USER}" ]]; then
  echo "ERROR: Database name and username are required. Pass --db-name/--db-user or run interactively." >&2
  exit 1
fi

echo "==> Checking PHP runtime & required extensions (PHP 8.3+)..."
"${PHP_BIN}" -v
REQUIRED_EXTS=(mbstring openssl pdo pdo_mysql tokenizer xml ctype json bcmath fileinfo curl zip gd)
for ext in "${REQUIRED_EXTS[@]}"; do
  if ! "${PHP_BIN}" -m | grep -iq "^${ext}$"; then
    echo "WARNING: PHP extension '${ext}' may not be loaded." >&2
  fi
done

if [[ ! -f "${APP_DIR}/.env" ]]; then
  echo "==> Creating .env from .env.production.example..."
  cp "${APP_DIR}/.env.production.example" "${APP_DIR}/.env"
  chmod 600 "${APP_DIR}/.env"
fi

# Safe .env writer using PHP so special characters (!, $, #, &, spaces) never corrupt .env
write_env_vars() {
  ENV_FILE="${APP_DIR}/.env" "${PHP_BIN}" -r '
    $file = getenv("ENV_FILE");
    $content = file_get_contents($file);
    $updates = json_decode(stream_get_contents(STDIN), true);
    foreach ($updates as $key => $val) {
        if ($val === null) continue;
        $str = (string) $val;
        $needsQuotes = $str !== "" && (preg_match("/[\s#\"'\''\\\\\$!]/", $str) === 1);
        $formatted = $needsQuotes
            ? "\"" . str_replace(["\\", "\"", "\$"], ["\\\\", "\\\"", "\\\$"], $str) . "\""
            : $str;
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
}

JSON_PAYLOAD="$("${PHP_BIN}" -r '
  echo json_encode(array_filter([
    "APP_ENV" => "production",
    "APP_DEBUG" => "false",
    "APP_URL" => $argv[1],
    "APP_TIMEZONE" => "Africa/Dar_es_Salaam",
    "APP_CURRENCY" => "TZS",
    "SESSION_SECURE_COOKIE" => str_starts_with($argv[1], "https://") ? "true" : "false",
    "DB_CONNECTION" => "mysql",
    "DB_HOST" => $argv[2],
    "DB_PORT" => $argv[3],
    "DB_DATABASE" => $argv[4],
    "DB_USERNAME" => $argv[5],
    "DB_PASSWORD" => $argv[6],
    "PESAPAL_CONSUMER_KEY" => $argv[7] !== "" ? $argv[7] : null,
    "PESAPAL_CONSUMER_SECRET" => $argv[8] !== "" ? $argv[8] : null,
    "PESAPAL_IPN_ID" => $argv[9] !== "" ? $argv[9] : null,
    "PESAPAL_MODE" => $argv[10],
  ], fn($v) => $v !== null));
' "${APP_URL}" "${DB_HOST}" "${DB_PORT}" "${DB_NAME}" "${DB_USER}" "${DB_PASS}" "${PESAPAL_KEY}" "${PESAPAL_SECRET}" "${PESAPAL_IPN}" "${PESAPAL_MODE}")"

printf '%s' "${JSON_PAYLOAD}" | write_env_vars
chmod 600 "${APP_DIR}/.env"
echo "==> Wrote production database & app credentials into .env (chmod 600)."

# Prepare storage & cache folders and ensure Composer autoloader exists for key:generate
mkdir -p storage/framework/{cache/data,sessions,views,testing} storage/logs storage/app/public bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

if [[ ! -f "${APP_DIR}/vendor/autoload.php" ]]; then
  COMPOSER_BIN="${COMPOSER_BIN:-$(command -v composer || true)}"
  if [[ -z "${COMPOSER_BIN}" ]] && [[ ! -f "${APP_DIR}/composer.phar" ]]; then
    echo "==> Composer not found globally; downloading composer.phar locally..."
    "${PHP_BIN}" -r 'copy("https://getcomposer.org/installer", "composer-setup.php");'
    "${PHP_BIN}" composer-setup.php --quiet
    rm -f composer-setup.php
  fi
  if [[ -f "${APP_DIR}/composer.phar" ]] && [[ -z "${COMPOSER_BIN}" ]]; then
    echo "==> Installing Composer production dependencies via composer.phar..."
    "${PHP_BIN}" "${APP_DIR}/composer.phar" install --no-dev --prefer-dist --optimize-autoloader --no-interaction
  elif [[ -n "${COMPOSER_BIN}" ]]; then
    echo "==> Installing Composer production dependencies..."
    "${PHP_BIN}" "$(command -v "${COMPOSER_BIN}")" install --no-dev --prefer-dist --optimize-autoloader --no-interaction
  else
    echo "ERROR: composer not found. Install composer before running setup." >&2
    exit 1
  fi
fi

if ! grep -qE '^APP_KEY=base64:' "${APP_DIR}/.env"; then
  echo "==> Generating Laravel APP_KEY..."
  "${PHP_BIN}" artisan key:generate --force --no-interaction
fi

# Verify MySQL connection via PHP PDO before running migrations
echo "==> Verifying MySQL connection to ${DB_USER}@${DB_HOST}:${DB_PORT}/${DB_NAME}..."
if ! "${PHP_BIN}" -r '
  $host = $argv[1]; $port = $argv[2]; $db = $argv[3]; $user = $argv[4]; $pass = $argv[5];
  try {
    new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "MySQL connection verified successfully.\n";
  } catch (Throwable $e) {
    fwrite(STDERR, "ERROR: MySQL connection failed: " . $e->getMessage() . "\n");
    exit(1);
  }
' "${DB_HOST}" "${DB_PORT}" "${DB_NAME}" "${DB_USER}" "${DB_PASS}"; then
  echo "Ensure database '${DB_NAME}' exists and user '${DB_USER}' has ALL PRIVILEGES on it in cPanel/MySQL." >&2
  exit 1
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
    echo "==> Installed Laravel scheduler cron job."
  fi
fi

echo ""
echo "======================================================================"
echo " LMSOUT Provisioning Complete!"
echo " Live URL : ${APP_URL}"
echo " Seeded Super Admin : superadmin@lsmout.ac.tz / password"
echo " Seeded Admin       : admin@lsmout.ac.tz / password"
echo " Seeded Instructor  : instructor@lsmout.ac.tz / password"
echo " Seeded Student     : student@lsmout.ac.tz / password"
echo " IMPORTANT: Change seeded passwords after live testing!"
echo "======================================================================"
