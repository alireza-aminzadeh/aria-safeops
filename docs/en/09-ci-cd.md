# 9) CI/CD — Aria SafeOps

English technical manual. Persian original: [`../09-ci-cd.md`](../09-ci-cd.md).  
Workflow: `.github/workflows/ci-cd.yml`.

## 9.1 Triggers

`push` and `pull_request` to `main`, plus `workflow_dispatch`. Concurrency group `safeops-${{ github.ref }}` cancels in-progress runs.

## 9.2 Jobs

| Job | Runs when | Does |
|---|---|---|
| `test` | all | PHP 8.4, Composer (no scripts until env exists), `php -l`, PHPUnit vs Timescale, Node 22 frontend build |
| `build-and-push` | `main` after test | Docker Buildx → `ghcr.io/alireza-aminzadeh/aria-safeops-app:<sha>` and `:latest` |
| `deploy` | `main` after push | `appleboy/ssh-action` to production |

Permissions: `contents: read`, `packages: write`.

## 9.3 Secrets / environment

GitHub Environment `production`:

| Secret | Purpose |
|---|---|
| `SAFEOPS_SSH_HOST` | VPS |
| `SAFEOPS_SSH_USER` | `deploy` (not root) |
| `SAFEOPS_SSH_KEY` | Deploy key |

The VPS uses a **separate** GitHub SSH key (`id_ed25519_github`) to `git fetch`. GHCR login in the deploy script uses `GITHUB_TOKEN`.

## 9.4 Deploy script behaviour (summary)

- `cd /opt/aria-safeops` and hard-reset to `origin/main`
- Fail if `.env` is missing (secrets stay on the box)
- Set `IMAGE_TAG` to the git SHA
- Ensure Mercure env keys exist (generate JWT secret if empty)
- `docker compose pull && up -d` and restart nginx
- `docker image prune`

Do not put plant data or production passwords in Actions logs.
