# Aria SafeOps («ایمن‌کار») — hse.aria-ai.ir

سامانهٔ ایمنی فرآیند، مجوز کار الکترونیک (PTW)، مدیریت تغییر (MOC/PSM) و دانش سازمانی HSE.

> سند مرجع: [`../docs/oil-gas-petrochemical-strategy.md`](../docs/oil-gas-petrochemical-strategy.md)

## وضعیت فعلی

فاز ۱ هستهٔ BPMS کامل شده است: گردش‌کار PTW/MOC با Guard، گاز‌تست و LOTO، حوادث+CAPA، داشبورد، QR/PWA، و جای رزرو AI.

| مورد | وضعیت |
|---|---|
| بک‌اند | Symfony 7.4 LTS + API Platform + Workflow + JWT |
| فرانت‌اند | React 19 + Vite + Tailwind v4 (RTL) + داشبورد |
| RAG/LLM | Stub — `/api/ai/knowledge-query` همیشه `503` |
| Production | `https://hse.aria-ai.ir` روی `91.107.130.11` (CI/CD به GHCR) |

## اجرای محلی

SafeOps روی پورت `5174` است تا با PetroOps روی `5173` همزمان اجرا شود. PHP و Vite روی میزبان هستند؛ Postgres/Redis در Docker.

```powershell
Copy-Item .env.example .env
docker compose up -d postgres redis
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console app:seed --no-interaction
php -S 127.0.0.1:8000 -t public public/index.php
# ترمینال دیگر:
cd frontend
npm run dev
```

- UI: [http://localhost:5174](http://localhost:5174)
- API: [http://127.0.0.1:8000/api/health](http://127.0.0.1:8000/api/health)

JWT با کلیدهای `config/jwt/*.pem` (gitignored) امضا می‌شود. اگر `lexik:jwt:generate-keypair` روی Windows خطا داد، کلید RSA بدون passphrase بسازید و `JWT_PASSPHRASE=` را در `.env.local` بگذارید.

## ورود

هر دو سامانه (SafeOps و PetroOps) یک کاربر راه‌انداز مشترک با **نام کاربری `alireza`** دارند.

| محیط | نام کاربری | رمز عبور |
|---|---|---|
| لوکال (`php bin/console app:seed` روی میزبان) | `alireza` | `alireza` |
| Production (`ARIA_RUNTIME=production`) | `alireza` | `Aria7x!Alireza#Ops2026` |

رمز Production در هر دو سامانه یکسان است. برای بازنویسی، متغیر `SEED_ALIREZA_PASSWORD` را در `.env` سرور ست کنید.

کاربران نمونهٔ دیگر (همچنان معتبر):

| نام کاربری / ایمیل | رمز | نقش |
|---|---|---|
| `admin` / `admin@hse.aria-ai.ir` | `ChangeMe!Admin1` | ROLE_ADMIN |
| `hse` / `hse@hse.aria-ai.ir` | `ChangeMe!Hse1` | ROLE_HSE_MANAGER |
| `issuer` / `issuer@hse.aria-ai.ir` | `ChangeMe!Issuer1` | ROLE_PERMIT_ISSUER |
