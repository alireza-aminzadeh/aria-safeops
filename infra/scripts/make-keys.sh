#!/bin/sh
set -eu
ssh-keygen -t ed25519 -f /tmp/safeops_github_plain -N '' -C safeops-github-deploy -q
ssh-keygen -t ed25519 -f /tmp/safeops_ci_plain -N '' -C ci-safeops -q
echo "=== github pub ==="
cat /tmp/safeops_github_plain.pub
echo "=== ci pub ==="
cat /tmp/safeops_ci_plain.pub
echo "=== github priv head ==="
head -n 2 /tmp/safeops_github_plain
