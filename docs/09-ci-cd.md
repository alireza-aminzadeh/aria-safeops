# ۹) CI/CD — Aria SafeOps

## ۹.۱ نمای کلی پایپ‌لاین
فایل: [`.github/workflows/ci-cd.yml`](../.github/workflows/ci-cd.yml)

تغییر فقط روی فایل‌های Markdown / `docs/` پایپ‌لاین را اجرا نمی‌کند تا استقرار زنده کنسل نشود.

```
push/PR → main
   │
   ├─▶ Job "test"            (PHPUnit + php -l + frontend build)
   │
   ├─▶ Job "build-and-push"  (فقط روی main، بعد از پاس شدن test)
   │       → Build ایمیج Docker → Push به ghcr.io
   │
   └─▶ Job "deploy"          (فقط روی main، بعد از build)
           → SSH به 91.107.130.11 → docker compose pull && up -d
```

## ۹.۲ پیش‌نیازها روی GitHub
| مرحله | توضیح |
|---|---|
| ۱. ساخت ریپو | `gh repo create alireza-aminzadeh/aria-safeops --private --source=. --remote=origin` |
| ۲. GHCR | خودکار از `secrets.GITHUB_TOKEN` با `permissions: packages: write` |
| ۳. کاربر `deploy` روی سرور | `infra/scripts/provision-host.sh` |
| ۴. کلید SSH اختصاصی CI | `ssh-keygen -t ed25519 -f aria_safeops_ci -C "ci-safeops"` (جدا از کلید PetroOps و کلید شخصی) |
| ۵. کلید GitHub روی سرور | deploy key فقط-خواندنی برای `git@github.com:alireza-aminzadeh/aria-safeops.git` |
| ۶. Environment `production` | Settings → Environments → `production` |

## ۹.۳ GitHub Secrets لازم
| نام Secret | مقدار |
|---|---|
| `SAFEOPS_SSH_HOST` | `91.107.130.11` |
| `SAFEOPS_SSH_USER` | `deploy` |
| `SAFEOPS_SSH_KEY` | محتوای کامل private key اختصاصی CI |

```bash
gh secret set SAFEOPS_SSH_HOST --body "91.107.130.11" -R alireza-aminzadeh/aria-safeops
gh secret set SAFEOPS_SSH_USER --body "deploy" -R alireza-aminzadeh/aria-safeops
gh secret set SAFEOPS_SSH_KEY  < aria_safeops_ci -R alireza-aminzadeh/aria-safeops
```

## ۹.۴ استراتژی برنچ
```
main                 ← همیشه قابل‌استقرار؛ فقط از طریق PR
 └─ feature/permit-workflow
 └─ feature/moc-workflow
 └─ fix/gas-test-validation
```
Conventional Commits (`feat:`, `fix:`, `docs:`, `chore:`) + Branch Protection Rule برای اجباری‌کردن سبز بودن Job «test» قبل از Merge.

## ۹.۵ اجرای اولین Deploy (دستی، قبل از اتکا به CI/CD خودکار)
```bash
ssh deploy@91.107.130.11
# یا از ریشه، اسکریپت‌های infra/scripts/clone-repo.sh و generate-env.sh
cd /opt/aria-safeops
docker compose -f docker-compose.yml up -d
```

## ۹.۶ Rollback
```bash
cd /opt/aria-safeops
git log --oneline -5
echo "IMAGE_TAG=<sha-پایدار>" > .env.deploy
# یا IMAGE_TAG را در .env عوض کنید
docker compose -f docker-compose.yml up -d
```

## ۹.۷ تفاوت با پایپ‌لاین PetroOps
| مورد | SafeOps | PetroOps |
|---|---|---|
| زبان تست | PHPUnit | Jest |
| Setup Action | `shivammathur/setup-php` | `actions/setup-node` + Corepack |
| Package Manager | Composer + npm (فرانت) | pnpm |
| مسیر Deploy | `/opt/aria-safeops` | `/opt/aria-petroops` |
| Secrets Prefix | `SAFEOPS_*` | `PETROOPS_*` |

هر دو پایپ‌لاین کاملاً مستقل‌اند — Deploy یکی هرگز روی دیگری اثر نمی‌گذارد.

## ۹.۸ وضعیت فعلی
ریپو: [github.com/alireza-aminzadeh/aria-safeops](https://github.com/alireza-aminzadeh/aria-safeops)
Production: `https://hse.aria-ai.ir` روی `91.107.130.11` — تست، build به GHCR، و deploy با SSH کاربر `deploy`.
