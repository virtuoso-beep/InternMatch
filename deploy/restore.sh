#!/bin/bash
set -euo pipefail
# Isolated recovery drill only; never overwrites live data.
exec python3 "$(dirname "$0")/recovery.py" verify "${1:?Supply a backup identifier}"
