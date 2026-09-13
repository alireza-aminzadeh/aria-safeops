# Security policy — Aria SafeOps

**Language:** English first, then فارسی.

## English

### Reporting

This is a private operational product. If you find a vulnerability in Aria SafeOps, email the maintainers through the Aria AI contact channel on [aria-ai.ir](https://aria-ai.ir) and **do not** open a public GitHub issue that includes exploits, tokens, or plant data.

### What this repo must never contain

| Class | Rule |
|---|---|
| Production passwords | Only `SEED_ALIREZA_PASSWORD` on the server. Not in git. |
| JWT private keys | `config/jwt/*.pem` is gitignored |
| `.env` | gitignored; commit `.env.example` with empty/placeholder values |
| Plant / PTW / incident data | Not in this repository |
| Hugging Face tokens | Not committed; Space inference is not used in production |

### Security controls already in the product

| Control | Implementation |
|---|---|
| Transport | HTTPS only on `hse.aria-ai.ir` (80 → 443) |
| Database / Redis | Internal Docker network; Redis `--requirepass`; no `0.0.0.0` bind for data stores in production compose |
| App user | Container `USER appuser` (uid 1000) |
| AuthN | JWT (Lexik), Argon2id/bcrypt hashes, login rate limit |
| AuthZ | RBAC roles + Voters (ABAC) + `tenant_id` |
| Workflow integrity | Guards on PTW/MOC including PetroOps equipment holds |
| Audit | Hash-chain `audit_log`; Symfony Workflow audit trail |
| Images | Pinned tags (`nginx:1.27-alpine`, `redis:7.4-alpine`, `timescale/timescaledb:2.17.2-pg16`) |
| Memory | `deploy.resources.limits` on every compose service |

### Out of scope today

SSO/OIDC, MFA, qualified PKI signatures, fail-closed RLS on every table including contractors, and sending operational text to public Hugging Face Spaces.

Details: [`docs/en/07-security.md`](docs/en/07-security.md) and [`docs/07-security.md`](docs/07-security.md).

---

## فارسی

آسیب‌پذیری را از طریق کانال تماس [aria-ai.ir](https://aria-ai.ir) گزارش کنید؛ در Issue عمومی گیت‌هاب Exploit، توکن یا دادهٔ واحد عملیاتی نگذارید.

رمز Production، کلید JWT، فایل `.env` و توکن Hugging Face نباید وارد git شوند. رمز راه‌انداز Production فقط با `SEED_ALIREZA_PASSWORD` روی سرور ست می‌شود.

کنترل‌های فعلی: HTTPS، شبکهٔ داخلی Docker برای Postgres/Redis، Redis با رمز، کاربر غیر-root در کانتینر، JWT + Voter، hold تجهیزات PetroOps، زنجیرهٔ هش ممیزی، تگ نسخه‌دار ایمیج‌ها.

جزئیات: [`docs/07-security.md`](docs/07-security.md).
