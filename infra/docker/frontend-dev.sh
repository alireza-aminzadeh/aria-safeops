#!/bin/sh
set -eu
cd /workspace/frontend
if [ -f package-lock.json ]; then
  npm ci
else
  npm install
fi
exec npm run dev -- --host 0.0.0.0 --port 5173
