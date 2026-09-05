#!/bin/sh
set -eu
for path in / /login /api/health /api/docs; do
  code=$(curl -sS -o /tmp/body -w '%{http_code}' -m 15 "https://hse.aria-ai.ir$path")
  echo "$path $code"
done
