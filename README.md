# Aria SafeOps («ایمن‌کار») — hse.aria-ai.ir

سامانهٔ ایمنی فرآیند، مجوز کار الکترونیک (PTW)، مدیریت تغییر (MOC/PSM) و دانش سازمانی HSE.

> سند مرجع: [`../docs/oil-gas-petrochemical-strategy.md`](../docs/oil-gas-petrochemical-strategy.md)

## وضعیت فعلی

فاز ۱ هستهٔ BPMS کامل شده و بخشی از فاز ۲ نیز اضافه شده است: گردش‌کار PTW/MOC با Guard (شامل بلاک‌شدن به‌خاطر نگه‌داشت PetroOps، هم در ایجاد و هم در فعال‌سازی/از سرگیری/ورود به implementation)، گاز‌تست و LOTO، حوادث+CAPA، ماژول اضطراری (Emergency/Drill)، داشبورد، QR/PWA، و AI Gateway سه‌سطحی (جزئیات: [`docs/06-ai-gateway-placeholder.md`](docs/06-ai-gateway-placeholder.md)).

| مورد | وضعیت |
|---|---|
| بک‌اند | Symfony 7.4 LTS + API Platform + Workflow + JWT |
| فرانت‌اند | React 19 + Vite + Tailwind v4 (RTL) + داشبورد |
| AI Gateway | سه‌سطحی: خاموش (پیش‌فرض `.env` محلی) / بستهٔ دانش محلی واقعی (کلیدواژه‌ای، بدون LLM — پیش‌فرض `.env.example`) / HTTP خارجی (RAG/LLM واقعی — هنوز در تولید وصل نشده) |
| Production | فعال: [https://hse.aria-ai.ir](https://hse.aria-ai.ir) — CI/CD به GHCR |

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
