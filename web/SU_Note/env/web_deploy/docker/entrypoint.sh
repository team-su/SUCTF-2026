#!/bin/sh
set -eu

GENERATED_USER_SUFFIX="$(LC_ALL=C tr -dc 'a-z0-9' </dev/urandom | head -c 8 || true)"
if [ -z "${GENERATED_USER_SUFFIX}" ]; then
  GENERATED_USER_SUFFIX="$(date +%s | sha256sum | cut -c1-8)"
fi
ADMIN_USER="admin_${GENERATED_USER_SUFFIX}"

GENERATED_PASSWORD="$(LC_ALL=C tr -dc 'A-Za-z0-9' </dev/urandom | head -c 18 || true)"
if [ -z "${GENERATED_PASSWORD}" ]; then
  GENERATED_PASSWORD="$(date +%s | sha256sum | cut -c1-18)"
fi

export SU_EZNOTE_ADMIN_USER="${ADMIN_USER}"
export SU_EZNOTE_ADMIN_PASSWORD="${GENERATED_PASSWORD}"
export SU_EZNOTE_FLAG_PATH="${SU_EZNOTE_FLAG_PATH:-/opt/su-eznote/flag}"

CONFIG_FILE="${SU_EZNOTE_CONFIG_PATH:-/opt/su-eznote/.config}"
CONFIG_MAX_CONCURRENCY=""
if [ -r "${CONFIG_FILE}" ]; then
  CONFIG_MAX_CONCURRENCY="$(awk -F= '
    {
      key=$1
      gsub(/^[[:space:]]+|[[:space:]]+$/, "", key)
      lower=tolower(key)
      if (lower == "max_concurrency") {
        v=$2
        gsub(/^[[:space:]]+|[[:space:]]+$/, "", v)
        print v
        exit
      }
    }
  ' "${CONFIG_FILE}")"
fi

if [ -z "${CONFIG_MAX_CONCURRENCY}" ]; then
  echo "[SU-ezNote] Missing max_concurrency in config: ${CONFIG_FILE}" >&2
  exit 1
fi

if ! printf '%s' "${CONFIG_MAX_CONCURRENCY}" | grep -Eq '^[0-9]+$' || [ "${CONFIG_MAX_CONCURRENCY}" -lt 1 ]; then
  echo "[SU-ezNote] Invalid max_concurrency in config: ${CONFIG_FILE}" >&2
  exit 1
fi

export BOT_MAX_CONCURRENCY="${CONFIG_MAX_CONCURRENCY}"

mkdir -p /run/su-eznote
chown root:www-data /run/su-eznote
chmod 750 /run/su-eznote

printf '%s' "${SU_EZNOTE_ADMIN_USER}" > /run/su-eznote/admin_user
chown root:www-data /run/su-eznote/admin_user
chmod 640 /run/su-eznote/admin_user

printf '%s' "${SU_EZNOTE_ADMIN_PASSWORD}" > /run/su-eznote/admin_password
chown root:www-data /run/su-eznote/admin_password
chmod 640 /run/su-eznote/admin_password

echo "[SU-ezNote] Built-in admin user: ${SU_EZNOTE_ADMIN_USER}"
echo "[SU-ezNote] Built-in admin password: ${SU_EZNOTE_ADMIN_PASSWORD}"
echo "[SU-ezNote] Flag path: ${SU_EZNOTE_FLAG_PATH}"
echo "[SU-ezNote] Bot max concurrency: ${BOT_MAX_CONCURRENCY} (config: ${CONFIG_FILE})"

exec docker-php-entrypoint "$@"
