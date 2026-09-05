#!/bin/sh
set -eu
echo "=== key files ==="
ls -l /home/deploy/.ssh
echo "=== pub local vs github ==="
cat /home/deploy/.ssh/id_ed25519_github.pub
echo "=== key head ==="
head -n 2 /home/deploy/.ssh/id_ed25519_github
echo "=== ssh test ==="
sudo -u deploy -H ssh -i /home/deploy/.ssh/id_ed25519_github -o IdentitiesOnly=yes -o StrictHostKeyChecking=accept-new -T git@github.com || true
