# 7) Security — Aria SafeOps

English technical manual. Persian original: [`../07-security.md`](../07-security.md).  
Policy for reporters: [`../../SECURITY.md`](../../SECURITY.md).

> The Persian doc still contains a **historical SSH snapshot** from the first infra survey (UFW off, Docker not installed). That snapshot is **not** the current production state. Treat compose + CI as the live hardening baseline; re-verify the VPS before an audit.

## 7.1 Network (Zero Trust)

| Rule | Production compose |
|---|---|
| Published ports | 80 and 443 on nginx only |
| Postgres / Redis | Docker network only |
| Redis | `--requirepass` from env |
| OT protocols | Not used by SafeOps |

UFW should allow 22 (ideally from office/VPN), 80, 443; deny the rest.

## 7.2 Identity and access

| Control | Implementation |
|---|---|
| Password hash | Symfony hasher (Argon2id/bcrypt) |
| Session | Bearer JWT ~8 h; not cookie CSRF |
| Roles | `ROLE_ADMIN`, `ROLE_HSE_MANAGER`, `ROLE_PERMIT_ISSUER`, `ROLE_CONTRACTOR`, … |
| ABAC | Voters: tenant match + transition |
| Login abuse | Rate limiter on `/api/login` |
| Bootstrap user | `alireza`; production secret via `SEED_ALIREZA_PASSWORD` |

## 7.3 OWASP mapping (selected)

| Risk | Mitigation |
|---|---|
| SQLi | Doctrine parameterized queries |
| XSS | JSON API; React escaping |
| CSRF | Bearer tokens, no cookie session |
| Broken access control | Voters + workflow guards |
| Secrets | `.env` gitignored; JWT pem gitignored |
| Misconfiguration | `APP_ENV=prod`, Nginx security headers |

## 7.4 Container checklist

| Item | Status in compose/Dockerfile |
|---|---|
| Non-root `appuser` | Yes |
| Pinned image tags | Yes |
| `restart: unless-stopped` | Yes |
| Memory limits | Yes |
| `:latest` for third-party images | Forbidden |

## 7.5 Backup

Daily `pg_dump` plus copy **off** the VPS (Hetzner Storage Box or the sister host). Retention suggested in the Persian doc: 7 daily + 4 weekly. Off-box copy is an operations task, not a GitHub Action in this repo.

## 7.6 Known product-level security gaps

Contractor not tenant-scoped; no MFA/SSO; electronic signature is not qualified PKI; optional mailer otherwise logs only. See gap report 05.
