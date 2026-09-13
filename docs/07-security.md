# ۷) امنیت — Aria SafeOps

## ۷.۱ اسنپ‌شات وضعیت فعلی سرور (بررسی‌شده در ۱۴۰۵/۰۶/۱۴ از طریق SSH read-only)

| مورد | وضعیت فعلی | اقدام لازم |
|---|---|---|
| IP / Hostname | `91.107.130.11` / `hse` | — |
| OS | Ubuntu 26.04.1 LTS، کرنل `7.0.0-30-generic` | به‌روز نگه‌داشتن با `unattended-upgrades` |
| فایروال (UFW) | نصب شده ولی **غیرفعال** | ⚠️ فعال‌سازی با قوانین محدود (بخش ۷.۲) |
| پورت‌های باز فعلی | فقط `22` (روی `0.0.0.0`) — چیز دیگری اکسپوز نیست | بعد از نصب Docker، فقط `80`/`443` هم اضافه شود |
| Docker | **نصب نشده** | نصب Docker Engine + Compose plugin ([`08-infrastructure-deployment.md`](08-infrastructure-deployment.md)) |
| کاربر Deploy اختصاصی | وجود ندارد (فقط `root` از طریق کلید SSH شخصی) | ⚠️ ساخت کاربر `deploy` غیر-root با دسترسی گروه `docker` (بخش ۷.۳) |
| Swap | `0B` | توصیه: swap با حجم کم (۱ گیگ) به‌عنوان ضامن ایمنی OOM |

## ۷.۲ قوانین فایروال پیشنهادی (UFW)
```bash
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp     # SSH — ایده‌آل: محدود به IP دفتر/VPN اگر ثابت است
ufw allow 80/tcp     # HTTP (فقط برای ریدایرکت به HTTPS و ACME challenge)
ufw allow 443/tcp    # HTTPS
ufw enable
```
> طبق قاعدهٔ Zero Trust پروژه: **هیچ پورت دیگری** (نه ۵۴۳۲ پستگرس، نه ۶۳۷۹ ردیس) هرگز باز نمی‌شود؛ ترافیک بین سرویس‌ها همیشه از طریق شبکهٔ داخلی Docker (`aria_safeops_net`) عبور می‌کند — همان‌طور که در `docker-compose.yml` تعریف شده (بدون `ports:` برای Postgres/Redis در فایل Production).

## ۷.۳ کاربر Deploy اختصاصی (پیشنهاد قبل از فعال‌سازی CI/CD)
به‌جای استفاده از `root` برای SSH خودکار GitHub Actions:
```bash
adduser --disabled-password --gecos "" deploy
usermod -aG docker deploy
mkdir -p /home/deploy/.ssh && chmod 700 /home/deploy/.ssh
# کلید SSH اختصاصی این پروژه (نه کلید شخصی id_ed25519) را در authorized_keys قرار دهید
chmod 600 /home/deploy/.ssh/authorized_keys
chown -R deploy:deploy /home/deploy/.ssh
```
سپس در GitHub Secrets: `SAFEOPS_SSH_USER=deploy` (نه `root`).

## ۷.۴ چک‌لیست امنیتی سرویس‌ها (اعمال‌شده در `docker-compose.yml`)
| مورد | وضعیت |
|---|---|
| Redis با `--requirepass` | ✅ در `docker-compose.yml` |
| Postgres/Redis بدون expose به `0.0.0.0` | ✅ فقط در `docker-compose.override.yml` (dev) به `127.0.0.1` bind می‌شود |
| کاربر non-root در ایمیج نهایی | ✅ `USER appuser` (uid 1000) در `Dockerfile` |
| Tag نسخه‌دار (نه `:latest`) | ✅ `nginx:1.27-alpine`, `redis:7.4-alpine`, `timescale/timescaledb:2.17.2-pg16` |
| `restart: unless-stopped` | ✅ همهٔ سرویس‌ها |
| سقف حافظه هر سرویس | ✅ `deploy.resources.limits/reservations` |
| Secrets فقط از `.env` (gitignored) | ✅ `.env.example` بدون مقدار واقعی commit شده |

## ۷.۵ احراز هویت و مجوزها
- **JWT** (LexikJWTAuthenticationBundle) با `JWT_TTL=28800` (۸ ساعت) + Refresh Token جدا با انقضای کوتاه‌تر برای دسترسی حساس.
- ورود با **نام کاربری** (ستون `users.username`) یا ایمیل.
- **کاربر راه‌انداز مشترک با PetroOps:** نام کاربری `alireza`. رمز لوکال `alireza`. رمز Production فقط از طریق `SEED_ALIREZA_PASSWORD` روی سرور (در git ذخیره نشود).
- **RBAC پایه**: `ROLE_PERMIT_ISSUER`, `ROLE_HSE_MANAGER`, `ROLE_CONTRACTOR`, `ROLE_ADMIN` در `security.yaml`.
- **ABAC دقیق**: Symfony Voter برای تصمیم‌های ریزدانه (مثلاً «مدیر HSE فقط می‌تواند مجوز‌های سایت خودش را تأیید کند» — تطبیق `tenant_id`).
- **Rate Limiting** روی `/api/login` (۵ تلاش در ۱۵ دقیقه) برای پیشگیری از brute-force.

## ۷.۶ OWASP Top 10 — اقدامات مشخص
| ریسک | اقدام در Symfony |
|---|---|
| SQL Injection | Doctrine ORM + Parameter Binding (هیچ کوئری خام با concatenation) |
| XSS | Twig auto-escaping (اگر بخش‌های Twig استفاده شود)؛ در API فقط JSON برمی‌گردد، فرانت React خودش escape می‌کند |
| CSRF | چون API کاملاً Bearer-token-based است (نه Cookie-session)، CSRF classic منتفی است؛ Symfony CSRF Protection فقط اگر فرم Twig اضافه شود لازم می‌شود |
| Broken Access Control | Voter + Guard روی هر Workflow transition (بخش ۳) |
| Sensitive Data Exposure | HTTPS اجباری (TLS 1.2/1.3)، رمز عبور با Argon2id، `.env` هرگز commit نمی‌شود |
| Security Misconfiguration | `APP_ENV=prod` (خطاهای دیباگ غیرفعال)، هدرهای امنیتی در Nginx (`X-Frame-Options`, `HSTS`, ...) |

## ۷.۷ پشتیبان‌گیری (Backup)
- `pg_dump` روزانه به یک volume جدا + rsync/rclone به یک مقصد خارج از سرور (توصیه: یک Storage Box هتزنر یا حداقل سرور دیگر) — **جزئیات اجرا در فاز عملیاتی، خارج از scope مستندسازی فعلی**.
- Retention پیشنهادی: ۷ نسخهٔ روزانه + ۴ نسخهٔ هفتگی.

## ۷.۸ مانیتورینگ سبک (متناسب با منابع محدود سرور)
- `docker stats` برای بررسی دستی مصرف لحظه‌ای.
- در فاز بعد (وقتی منابع اجازه داد): `node-exporter` + Prometheus/Grafana خارج از این سرور (روی سرور مانیتورینگ مرکزی، نه روی هر سرور اپلیکیشن، تا بار اضافه نکند).
