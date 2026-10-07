#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
TARGET_SCRIPT="$REPO_ROOT/deploy-frontend.sh"

if [[ ! -f "$TARGET_SCRIPT" ]]; then
  echo "Error: deploy-frontend.sh not found at repo root ($TARGET_SCRIPT)" >&2
  exit 1
fi

if [[ ! -x "$TARGET_SCRIPT" ]]; then
  chmod +x "$TARGET_SCRIPT"
fi

exec "$TARGET_SCRIPT" "$@"
