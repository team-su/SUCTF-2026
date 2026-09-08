#!/bin/sh
set -eu

: "${ZHIPU_API_KEY:?ZHIPU_API_KEY is required}"

PORT="${PORT:-8080}"

cd /app
exec python -m uvicorn easyLLM:app --host 0.0.0.0 --port "${PORT}" --workers 1
