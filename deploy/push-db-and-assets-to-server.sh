#!/usr/bin/env bash
# ==============================================================================
# Private Mac-to-Server Database & Assets Transfer Helper
# Target Live Server: https://lmsout.wolinet.com/
#
# Use this from your local machine when you want an EXACT copy of your local
# MySQL database + local uploaded assets synced privately over SSH/SCP to the
# live server — WITHOUT ever committing personal/live DB data to public Git!
#
# Usage:
#   bash deploy/push-db-and-assets-to-server.sh \
#     --ssh="user@lmsout.wolinet.com" \
#     --port="22" \
#     --deploy-path="/home/user/lmsout"
# ==============================================================================
set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
cd "${APP_DIR}"

SSH_TARGET=""
SSH_PORT="${SSH_PORT:-22}"
DEPLOY_PATH=""
PHP_BIN="${PHP_BIN:-$(command -v php || ls /Applications/MAMP/bin/php/php8.*/bin/php 2>/dev/null | tail -n 1 || true)}"
MYSQLDUMP_BIN="${MYSQLDUMP_BIN:-$(ls /Applications/MAMP/Library/bin/mysql80/bin/mysqldump 2>/dev/null || command -v mysqldump || true)}"
REMOTE_PHP="php"

for arg in "$@"; do
  case "${arg}" in
    --ssh=*) SSH_TARGET="${arg#*=}" ;;
    --port=*) SSH_PORT="${arg#*=}" ;;
    --deploy-path=*) DEPLOY_PATH="${arg#*=}" ;;
    --remote-php=*) REMOTE_PHP="${arg#*=}" ;;
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

if [[ -z "${SSH_TARGET}" ]] && [[ -t 0 ]]; then
  read -r -p "SSH target (e.g. user@lmsout.wolinet.com): " SSH_TARGET
fi
if [[ -z "${DEPLOY_PATH}" ]] && [[ -t 0 ]]; then
  read -r -p "Remote project path (e.g. /home/user/public_html): " DEPLOY_PATH
fi

if [[ -z "${SSH_TARGET}" ]] || [[ -z "${DEPLOY_PATH}" ]]; then
  echo "ERROR: --ssh and --deploy-path are required." >&2
  exit 1
fi

if [[ ! -f "${APP_DIR}/.env" ]]; then
  echo "ERROR: Local .env not found in ${APP_DIR}." >&2
  exit 1
fi

if [[ -z "${MYSQLDUMP_BIN}" ]] || [[ ! -x "${MYSQLDUMP_BIN}" ]]; then
  echo "ERROR: mysqldump not found locally. Set MYSQLDUMP_BIN." >&2
  exit 1
fi

read_local_env() {
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
        }
        echo $v;
        exit(0);
    }
  ' "$1"
}

LOCAL_DB_HOST="$(read_local_env DB_HOST)"; LOCAL_DB_HOST="${LOCAL_DB_HOST:-127.0.0.1}"
LOCAL_DB_PORT="$(read_local_env DB_PORT)"; LOCAL_DB_PORT="${LOCAL_DB_PORT:-3306}"
LOCAL_DB_NAME="$(read_local_env DB_DATABASE)"
LOCAL_DB_USER="$(read_local_env DB_USERNAME)"
LOCAL_DB_PASS="$(read_local_env DB_PASSWORD)"

TMP_DUMP="$(mktemp -t lmsout-db-XXXXXX).sql.gz"
REMOTE_DUMP="/tmp/lmsout-import-$(date +%s).sql.gz"

cleanup() {
  rm -f "${TMP_DUMP}"
}
trap cleanup EXIT

echo "==> Exporting local database '${LOCAL_DB_NAME}' from ${LOCAL_DB_HOST}:${LOCAL_DB_PORT}..."
MYSQL_PWD="${LOCAL_DB_PASS}" "${MYSQLDUMP_BIN}" \
  -h "${LOCAL_DB_HOST}" \
  -P "${LOCAL_DB_PORT}" \
  -u "${LOCAL_DB_USER}" \
  --single-transaction \
  --quick \
  --no-tablespaces \
  --set-gtid-purged=OFF \
  "${LOCAL_DB_NAME}" 2>/dev/null \
  | sed -E 's/DEFINER=`[^`]+`@`[^`]+`//g' \
  | gzip -9 > "${TMP_DUMP}"

echo "==> Dump created: $(du -h "${TMP_DUMP}" | cut -f1)"

if command -v rsync >/dev/null 2>&1; then
  echo "==> Syncing storage/app/public and public/assets to ${SSH_TARGET}:${DEPLOY_PATH}..."
  rsync -az --delete-after -e "ssh -p ${SSH_PORT}" \
    "${APP_DIR}/storage/app/public/" \
    "${SSH_TARGET}:${DEPLOY_PATH}/storage/app/public/"
  rsync -az -e "ssh -p ${SSH_PORT}" \
    "${APP_DIR}/public/assets/" \
    "${SSH_TARGET}:${DEPLOY_PATH}/public/assets/"
fi

echo "==> Uploading compressed DB snapshot over SCP..."
scp -P "${SSH_PORT}" "${TMP_DUMP}" "${SSH_TARGET}:${REMOTE_DUMP}"

echo "==> Importing snapshot and running remote deploy script on ${SSH_TARGET}..."
ssh -p "${SSH_PORT}" "${SSH_TARGET}" "set -Eeuo pipefail; cd '${DEPLOY_PATH}'; trap 'rm -f \"${REMOTE_DUMP}\"' EXIT; bash deploy/deploy.sh --no-git --php='${REMOTE_PHP}' --with-sql='${REMOTE_DUMP}'"

echo ""
echo "Private local DB + assets sync to ${SSH_TARGET} completed cleanly!"
