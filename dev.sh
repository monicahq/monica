#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT_DIR"

APP_PORT="${APP_PORT:-8000}"
VITE_PORT="${VITE_PORT:-5173}"
SAIL_CMD="${SAIL_CMD:-}"

if [[ -z "$SAIL_CMD" ]]; then
  if [[ -x "$ROOT_DIR/vendor/bin/sail" ]]; then
    SAIL_CMD="$ROOT_DIR/vendor/bin/sail"
  elif [[ -x "$ROOT_DIR/sail" ]]; then
    SAIL_CMD="$ROOT_DIR/sail"
  else
    SAIL_CMD="docker compose"
  fi
fi

export APP_PORT
export VITE_PORT

cleanup() {
  local exit_code=$?

  if [[ -n "${VITE_PID:-}" ]] && kill -0 "$VITE_PID" >/dev/null 2>&1; then
    kill "$VITE_PID" >/dev/null 2>&1 || true
    wait "$VITE_PID" >/dev/null 2>&1 || true
  fi

  if [[ "${1:-}" == "stop-stack" ]]; then
    if [[ "$SAIL_CMD" == "docker compose" ]]; then
      docker compose down >/dev/null 2>&1 || true
    else
      "$SAIL_CMD" down >/dev/null 2>&1 || true
    fi
  fi

  return "$exit_code"
}

trap 'cleanup "stop-stack"' EXIT INT TERM

compose_up() {
  if [[ "$SAIL_CMD" == "docker compose" ]]; then
    docker compose up -d --remove-orphans
  else
    "$SAIL_CMD" up -d --remove-orphans
  fi
}

compose_down() {
  if [[ "$SAIL_CMD" == "docker compose" ]]; then
    docker compose down
  else
    "$SAIL_CMD" down
  fi
}

compose_logs() {
  if [[ "$SAIL_CMD" == "docker compose" ]]; then
    docker compose logs -f
  else
    "$SAIL_CMD" logs -f
  fi
}

ensure_yarn() {
  if ! command -v yarn >/dev/null 2>&1; then
    echo "yarn is required but was not found in PATH." >&2
    exit 1
  fi
}

reset_generated_i18n_files() {
  # The Vite i18n plugin regenerates these files on startup.
  # If they were previously created by another user/container, they can block writes.
  find lang -maxdepth 1 -type f -name 'php_*.json' -delete 2>/dev/null || true
}

reset_vite_cache() {
  # Vite's optimized dependency cache can also be created with mismatched ownership
  # when the dev server or containers are run as root.
  rm -rf .vite-cache 2>/dev/null || true
}

migrate_database() {
  echo "Running database migrations..."

  if [[ "$SAIL_CMD" == "docker compose" ]]; then
    docker compose exec -T laravel.test php artisan migrate --force
  else
    "$SAIL_CMD" artisan migrate --force
  fi
}

kill_port() {
  local port="$1"

  if command -v lsof >/dev/null 2>&1; then
    lsof -ti tcp:"$port" | xargs -r kill -9 >/dev/null 2>&1 || true
  fi
}

start_vite() {
  ensure_yarn
  reset_generated_i18n_files
  reset_vite_cache
  kill_port "$VITE_PORT"

  if [[ ! -d node_modules ]]; then
    echo "node_modules not found, installing frontend dependencies..."
    yarn install
  fi

  yarn dev --host 0.0.0.0 --port "$VITE_PORT" --strictPort
}

wait_for_laravel() {
  local url="http://127.0.0.1:${APP_PORT}/login"
  echo "Waiting for Laravel at ${url}"

  for _ in {1..60}; do
    if curl -fsS "$url" >/dev/null 2>&1; then
      echo "Laravel is up on http://127.0.0.1:${APP_PORT}"
      return 0
    fi
    sleep 2
  done

  echo "Laravel did not become ready on port ${APP_PORT}." >&2
  return 1
}

show_help() {
  cat <<'EOF'
Usage: ./dev.sh [up|down|logs|shell|test]

Commands:
  up      Start the Docker stack on port 8000 and run Vite on 5173
  down    Stop the Docker stack
  logs    Follow Docker logs
  shell   Open a shell in the Laravel app container
  test    Run the Laravel test suite inside Sail
EOF
}

case "${1:-up}" in
  up)
    compose_up
    wait_for_laravel
    migrate_database
    start_vite
    ;;
  down)
    trap - EXIT INT TERM
    compose_down
    ;;
  logs)
    trap - EXIT INT TERM
    compose_logs
    ;;
  shell)
    trap - EXIT INT TERM
    if [[ "$SAIL_CMD" == "docker compose" ]]; then
      docker compose exec laravel.test bash
    else
      "$SAIL_CMD" shell
    fi
    ;;
  test)
    trap - EXIT INT TERM
    compose_up
    if [[ "$SAIL_CMD" == "docker compose" ]]; then
      docker compose exec -T laravel.test php artisan test
    else
      "$SAIL_CMD" test
    fi
    ;;
  -h|--help|help)
    trap - EXIT INT TERM
    show_help
    ;;
  *)
    trap - EXIT INT TERM
    show_help >&2
    exit 1
    ;;
esac
