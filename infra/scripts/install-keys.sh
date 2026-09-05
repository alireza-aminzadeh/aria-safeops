#!/bin/sh
set -eu
install -m 600 -o deploy -g deploy /tmp/safeops_github_plain /home/deploy/.ssh/id_ed25519_github
install -m 644 -o deploy -g deploy /tmp/safeops_github_plain.pub /home/deploy/.ssh/id_ed25519_github.pub
{
  cat /tmp/safeops_ci_plain.pub
  grep -v 'ci-safeops' /home/deploy/.ssh/authorized_keys || true
} | sed 's/\r$//' | awk 'NF' | sort -u > /tmp/authorized_keys.new
install -m 600 -o deploy -g deploy /tmp/authorized_keys.new /home/deploy/.ssh/authorized_keys
echo "keys installed"
sudo -u deploy -H ssh -i /home/deploy/.ssh/id_ed25519_github -o IdentitiesOnly=yes -o StrictHostKeyChecking=accept-new -T git@github.com || true
