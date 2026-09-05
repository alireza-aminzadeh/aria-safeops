#!/bin/sh
set -eu
sed -i 's/\r$//' /home/deploy/.ssh/authorized_keys /home/deploy/.ssh/config || true
chmod 600 /home/deploy/.ssh/authorized_keys
chown -R deploy:deploy /home/deploy/.ssh

mkdir -p /opt/aria-safeops
chown deploy:deploy /opt/aria-safeops

if [ ! -d /opt/aria-safeops/.git ]; then
  sudo -u deploy -H env GIT_SSH_COMMAND='ssh -i /home/deploy/.ssh/id_ed25519_github -o IdentitiesOnly=yes' \
    git clone git@github.com:alireza-aminzadeh/aria-safeops.git /opt/aria-safeops
fi

install -m 600 -o deploy -g deploy /root/aria-safeops.env /opt/aria-safeops/.env
mkdir -p /opt/aria-safeops/infra/certbot/www \
  /opt/aria-safeops/infra/certbot/conf \
  /opt/aria-safeops/infra/certbot/work \
  /opt/aria-safeops/infra/certbot/logs
chown -R deploy:deploy /opt/aria-safeops/infra/certbot
sed -i 's/\r$//' /opt/aria-safeops/infra/scripts/*.sh || true
chmod +x /opt/aria-safeops/infra/scripts/*.sh /opt/aria-safeops/infra/docker/*.sh || true
BACKUP_CRON='15 2 * * * /opt/aria-safeops/infra/scripts/backup-postgres.sh'
(crontab -l 2>/dev/null | grep -v 'backup-postgres.sh' || true; echo "$BACKUP_CRON") | crontab -
echo "clone ready"
ls -la /opt/aria-safeops | sed -n '1,25p'
