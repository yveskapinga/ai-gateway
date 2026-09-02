#!/usr/bin/env bash
# Vérifie les frontières SQL-first (AIGW ADR-001).
# Contrôleurs et Domain : pas de SQL inline. Services Application : pas de SELECT/INSERT.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SRC="$ROOT/control-plane/src"
fail=0

if [[ ! -d "$SRC" ]]; then
  echo "OK: pas encore de src/ (P0)"
  exit 0
fi

# Controllers must not contain SQL keywords used as statements.
if [[ -d "$SRC/ControlPlane" ]]; then
  if grep -RInE --include='*.php' '\b(SELECT|INSERT|UPDATE|DELETE|CREATE TABLE)\b' "$SRC/ControlPlane" 2>/dev/null; then
    echo "FAIL: SQL inline dans ControlPlane/"
    fail=1
  fi
fi

if [[ -d "$SRC/Domain" ]]; then
  if grep -RInE --include='*.php' '\b(SELECT|INSERT|UPDATE|DELETE|CREATE TABLE)\b' "$SRC/Domain" 2>/dev/null; then
    echo "FAIL: SQL inline dans Domain/"
    fail=1
  fi
  if grep -RIn --include='*.php' -E 'use (PDO|Doctrine\\|Symfony\\|Google\\|Gemini)' "$SRC/Domain" 2>/dev/null; then
    echo "FAIL: Domain importe PDO/Doctrine/Symfony/Google"
    fail=1
  fi
fi

if [[ -d "$SRC/Application" ]]; then
  if grep -RInE --include='*.php' '\b(SELECT|INSERT INTO|UPDATE [a-z_]+ SET|DELETE FROM)\b' "$SRC/Application" 2>/dev/null; then
    echo "FAIL: SQL inline dans Application/"
    fail=1
  fi
fi

if [[ "$fail" -ne 0 ]]; then
  exit 1
fi

echo "OK: frontières SQL-first respectées"
exit 0
