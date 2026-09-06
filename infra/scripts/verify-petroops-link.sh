#!/bin/sh
# اسموک‌تست لینک PetroOps↔SafeOps روی Production. هیچ secret در این فایل
# hardcode نمی‌شود — همه از متغیرهای محیطی خوانده می‌شوند:
#   ARIA_LOGIN_USERNAME, ARIA_LOGIN_PASSWORD  (حساب HSE برای تست ایجاد مجوز)
# استفاده: ARIA_LOGIN_PASSWORD=*** ./verify-petroops-link.sh <hex-key> [equipment-tag]
set -eu
KEY=${1:-}
if [ -z "$KEY" ]; then
  echo "usage: $0 <hex-key> [equipment-tag]" >&2
  exit 1
fi
LOGIN_USERNAME=${ARIA_LOGIN_USERNAME:-}
LOGIN_PASSWORD=${ARIA_LOGIN_PASSWORD:-}
if [ -z "$LOGIN_USERNAME" ] || [ -z "$LOGIN_PASSWORD" ]; then
  echo "ARIA_LOGIN_USERNAME و ARIA_LOGIN_PASSWORD باید به‌عنوان متغیر محیطی ست شوند (هرگز در فایل commit نمی‌شوند)." >&2
  exit 1
fi
TAG=${2:-ARIA-LINK-TEST}
BASE=${ARIA_BASE_URL:-https://hse.aria-ai.ir}
NOW=$(date -u +%Y-%m-%dT%H:%M:%SZ)

json_field() {
  sed -n "s/.*\"$1\":\"\\([^\"]*\\)\".*/\\1/p" | head -1
}

echo "ingest open hold"
code=$(curl -sS -o /tmp/hold-open.json -w '%{http_code}' -m 20 \
  -X POST "$BASE/api/integrations/petroops/anomalies" \
  -H "Content-Type: application/json" \
  -H "X-Aria-Api-Key: $KEY" \
  -d "{\"equipmentTag\":\"$TAG\",\"eventId\":\"link-test-open-$NOW\",\"score\":0.91,\"detectedAt\":\"$NOW\",\"summary\":\"smoke link test\",\"status\":\"open\"}")
echo "POST anomalies $code $(cat /tmp/hold-open.json)"
test "$code" = "200"

echo "get hold"
curl -sS -m 15 -H "X-Aria-Api-Key: $KEY" "$BASE/api/integrations/petroops/holds/$TAG"
echo

echo "login"
TOKEN=$(curl -sS -m 15 -X POST "$BASE/api/login" \
  -H 'Content-Type: application/json' \
  -d "{\"username\":\"$LOGIN_USERNAME\",\"password\":\"$LOGIN_PASSWORD\"}" | json_field token)
test -n "$TOKEN"

TYPE_ID=$(curl -sS -m 15 -H "Authorization: Bearer $TOKEN" "$BASE/api/permit_types" | json_field id)
test -n "$TYPE_ID"
TYPE="/api/permit_types/$TYPE_ID"

echo "create permit on held tag (expect 422)"
code=$(curl -sS -o /tmp/permit-blocked.json -w '%{http_code}' -m 20 \
  -X POST "$BASE/api/permits" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"permitType\":\"$TYPE\",\"equipmentTag\":\"$TAG\",\"locationPlotRef\":\"Unit-A\",\"workDescription\":\"link test blocked\"}")
echo "POST permits blocked $code $(cat /tmp/permit-blocked.json)"
test "$code" = "422"

echo "release hold"
code=$(curl -sS -o /tmp/hold-close.json -w '%{http_code}' -m 20 \
  -X POST "$BASE/api/integrations/petroops/anomalies" \
  -H "Content-Type: application/json" \
  -H "X-Aria-Api-Key: $KEY" \
  -d "{\"equipmentTag\":\"$TAG\",\"eventId\":\"link-test-close-$NOW\",\"score\":0.1,\"detectedAt\":\"$NOW\",\"summary\":\"smoke link release\",\"status\":\"closed\"}")
echo "POST anomalies close $code $(cat /tmp/hold-close.json)"
test "$code" = "200"

echo "create permit on released tag (expect 201)"
code=$(curl -sS -o /tmp/permit-ok.json -w '%{http_code}' -m 20 \
  -X POST "$BASE/api/permits" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"permitType\":\"$TYPE\",\"equipmentTag\":\"$TAG\",\"locationPlotRef\":\"Unit-A\",\"workDescription\":\"link test released\"}")
echo "POST permits ok $code"
test "$code" = "201"
echo "verify-petroops-link OK"
