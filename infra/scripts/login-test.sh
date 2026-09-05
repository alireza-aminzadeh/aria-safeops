#!/bin/sh
set -eu
curl -fsS -m 15 -X POST https://hse.aria-ai.ir/api/login \
  -H 'Content-Type: application/json' \
  -d '{"username":"alireza","password":"Aria7x!Alireza#Ops2026"}'
echo
