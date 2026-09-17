# Aria SafeOps (ImenKar)

Electronic permit-to-work, process safety management, and HSE operations for oil, gas, and petrochemical sites.

**Project period:** Data collection, process analysis, and implementation of this project were carried out in **2024, 2025, and 2026**.

**Language:** [English](#english) · [فارسی](#persian)

**Live:** [https://hse.aria-ai.ir](https://hse.aria-ai.ir) · **API docs:** [https://hse.aria-ai.ir/api/docs](https://hse.aria-ai.ir/api/docs) · **Health:** [https://hse.aria-ai.ir/api/health](https://hse.aria-ai.ir/api/health)

[![CI/CD](https://github.com/alireza-aminzadeh/aria-safeops/actions/workflows/ci-cd.yml/badge.svg)](https://github.com/alireza-aminzadeh/aria-safeops/actions/workflows/ci-cd.yml)
[![Production](https://img.shields.io/badge/production-hse.aria--ai.ir-1f6feb)](https://hse.aria-ai.ir)

Sister product: [Aria PetroOps](https://github.com/alireza-aminzadeh/aria-petroops) (`petro.aria-ai.ir`) — asset, process, and energy intelligence. The two systems share an `equipment_tag` contract so an open process anomaly can hold a permit or MOC.

---

<a id="english"></a>

# Aria SafeOps — English

Aria SafeOps is the Control of Work / HSE product of [Aria AI](https://aria-ai.ir). It is a **headless modular monolith**: Symfony 7.4 LTS + API Platform serves JSON; a React 19 SPA (RTL, Tailwind v4) is the operator UI. Production runs on a dedicated VPS behind Nginx, with GitHub Actions building images to GHCR and deploying over SSH.

Data collection, analysis of plant processes, and delivery of this system took place across **2024, 2025, and 2026**.

This repository is the system of record for the product. Persian engineering notes remain in [`docs/`](docs/). English technical manuals live in [`docs/en/`](docs/en/). Status tables, KPI catalogues, gap analysis, and Hugging Face alignment are in [`docs/reports/`](docs/reports/).

## 1. Snapshot (13 September 2026)

| Item | Value |
|---|---|
| Product name | Aria SafeOps («ایمن‌کار») |
| Project period | 2024, 2025, and 2026 (data collection, process analysis, implementation) |
| Public URL | https://hse.aria-ai.ir |
| GitHub | https://github.com/alireza-aminzadeh/aria-safeops |
| Current delivery | Phase 1 BPMS complete + most of Phase 2 (PSM, Vision simulator, shift, emergency/environment, Mercure, on-prem knowledge pack) |
| Backend | PHP 8.4 · Symfony 7.4 LTS · API Platform 4 · Lexik JWT · Symfony Workflow |
| Frontend | React 19 · TypeScript · Vite · Tailwind CSS v4 (RTL-first) |
| Data | PostgreSQL 16 + TimescaleDB 2.17 · Redis 7.4 |
| Realtime | Mercure hub (live events in the SPA) |
| AuthZ | JWT + RBAC roles + Symfony Voters (ABAC per tenant / permit / MOC) |
| AI | Three-level gateway: off / on-prem keyword knowledge pack / HTTP RAG (not wired in production yet) |
| CI/CD | GitHub Actions → GHCR (`ghcr.io/alireza-aminzadeh/aria-safeops-app`) → SSH deploy to `/opt/aria-safeops` |
| License | Proprietary |

## 2. What the product does

SafeOps is built for **permit issuers, HSE managers, operations supervisors, and contractor qualification staff** on a process plant. It is not a CMMS and it is not a DCS. It is Control of Work plus the PSM paperwork that must sit next to live operations.

| Module | What operators get today | Honesty note |
|---|---|---|
| **Control of Work / e-PTW** | Hot / cold / confined space / height / excavation / electrical permits; gas tests; LOTO flag; SIMOPS string match on tag/area; QR; equipment-hold from PetroOps | Plot-plan spatial SIMOPS and multi-point lock certificates are not built |
| **MOC / PSSR** | Temporary / permanent / emergency change; PSSR cannot be skipped; same equipment-hold guard | Not linked to a live ISA-95 asset register beyond the tag string |
| **PSM register** | HAZOP / LOPA / Bowtie items; API 754 Tier 1–3 + leading indicators | Register is operational; not a full PHA tool |
| **PSM audit** | 14 OSHA 1910.119 elements, scored findings, complete/close | Checklist scoring — not a third-party audit firm workflow |
| **Incidents + CAPA** | Near-miss / incident, RCA fields, CAPA, recordable flag, LTIFR/TRIR | No automatic filing to the Iranian MoP HSE system |
| **Emergency & environment** | ERP plans with review cycle, drills, effluent readings vs limit policy | Built in Phase 2 (ahead of the original Phase 3 plan) |
| **Shift / logbook** | Structured handover + toolbox talk | Not a full control-room logbook replacement |
| **Contractors** | Company record, certifications, training, expiry warnings | `Contractor` is not tenant-scoped yet |
| **HSE Vision** | Camera/RTSP registry + PPE / restricted-area **simulator** | No GPU detector on a live camera stream |
| **Knowledge assistant** | Keyword retrieval over a small HSE pack (API 754, NFPA-aligned hot work, H2S, …) | Not vector RAG / LLM. Hugging Face *PetroSafe RAG* and *PermitGuard* are published separately and not called from this API |
| **PWA shell** | Service worker + IndexedDB queue + offline banner | Not a full offline gas-test form with conflict sync |

## 3. In-app reports and KPIs

These are **product screens**, not slide decks. Endpoints are under `/api`.

| Screen | Route | Report / KPI | Source |
|---|---|---|---|
| Operations dashboard | `/` | Open/active permit counts by status, recent permits, API 754 T1/T2, open high HAZOP, open Vision events, LTIFR, TRIR, lost-time injuries (TTM), expiring contractor certs | `GET /api/dashboard`, `GET /api/kpis/api754`, `GET/POST /api/safety-period-metrics` |
| Permit list / detail | `/permits`, `/permits/:id` | Status, type, tag, gas tests, available transitions, signatures, hold reason | Workflow + Voters |
| MOC list / detail | `/moc`, `/moc/:id` | Change type, PSSR lock, transitions | `moc_workflow` |
| PSM register | `/psm` | HAZOP/LOPA/Bowtie tables + Tier 1/2/3 cards | `GET /api/kpis/api754` |
| PSM audit | `/psm-audit` | 14-element score, findings, rating | `PsmAuditScoreCalculator` |
| Incidents | `/incidents` | Recordable vs near-miss, CAPA status | Incident state machine |
| Emergency | `/emergency` | ERP review, drill log, effluent compliance | `EffluentCompliancePolicy` |
| Vision | `/vision` | Camera list, simulated detections | Simulator unless a Vision ingest URL is set |
| Contractors | `/contractors` | Certificate expiry | Certification entities |
| Knowledge | `/ai` | Answer + citations or honest “unavailable” | `AiGatewayFactory` |
| Equipment status | `/equipment/:tag` | Holds from PetroOps for that tag | `EquipmentHold` |

LTIFR / TRIR stay `null` until an HSE manager posts **hours worked** for a period (`/api/safety-period-metrics`). That is intentional: the denominator is never invented.

Detailed KPI definitions: [`docs/reports/04-in-app-kpis-and-reports.md`](docs/reports/04-in-app-kpis-and-reports.md).

## 4. Architecture

```
React 19 SPA (Vite, RTL)
        │ HTTPS / JSON + Mercure
        ▼
Nginx 1.27 (80/443 only) ── static SPA + PHP-FPM
        │
        ▼
Symfony 7.4 + API Platform
  Domain: Permit · MOC · Incident · PSM · Emergency · Shift · Vision · Contractor
  Infra: JWT · Redis · Mercure · AiGateway · PetroOps holds
        │
        ├─ PostgreSQL 16 + TimescaleDB
        └─ Redis 7.4 (cache, rate limit)

Optional later (not on this 2 vCPU VPS):
  FastAPI + Qdrant + LLM  ← AI_GATEWAY_URL
```

| Layer | Responsibility |
|---|---|
| API Platform resources / custom processors | HTTP ↔ domain; RFC 7807 errors |
| Domain entities + policies | Gas-test policy, isolation/LOTO flag, SIMOPS checker, equipment-hold |
| Symfony Workflow | Declarative PTW and MOC state machines (`config/packages/workflow.yaml`) |
| Voters | ABAC: who may approve / activate / close **this** record in **this** tenant |
| Hash-chain audit log | `SHA-256(prev \|\| tenant \|\| entity \|\| action \|\| actor \|\| payload \|\| time)` |

## 5. Permit and MOC state machines

**Permit-to-work:** `draft → submitted → hse_review → approved → active → closed` (also `suspended`, `rejected`, `cancelled`). Activation, resume, and entry to implementation are blocked when PetroOps has an open hold on the same `equipment_tag`.

**MOC:** `proposed → risk_assessment → approval → implementation → pssr → closed`. PSSR cannot be skipped. Reject from approval.

Full YAML and mermaid: [`docs/en/03-bpms-workflow.md`](docs/en/03-bpms-workflow.md).

## 6. Tech stack

| Concern | Choice | Why |
|---|---|---|
| Backend | Symfony 7.4 LTS + API Platform | Workflow-heavy CoW, not live OT ingest |
| Frontend | React 19 + Vite + Tailwind v4 | Same SPA family as PetroOps |
| Workflow | Symfony Workflow Component | Built-in, auditable, Graphviz dump |
| Database | PostgreSQL 16 + TimescaleDB | Shared ops stack; hypertables ready if gas-test series grow |
| Cache / limiter | Redis 7.4 with `requirepass` | Login throttle, cache |
| Realtime | Mercure | Live “event happened” banner without a Node gateway |
| Containers | Nginx, app (non-root `appuser`), Postgres, Redis, Mercure | Pinned tags, memory limits, no DB ports on `0.0.0.0` |
| Host | Ubuntu on Hetzner VPS, 2 vCPU / ~3.7 GiB | Reverse proxy only on 80/443 |

## 7. Hugging Face alignment (demos, not this runtime)

These Spaces validate the *future* AI layer. They are **not** called by production SafeOps today.

| Hub project | Role for SafeOps | Space |
|---|---|---|
| PetroSafe RAG | Bilingual HSE RAG with citation + abstention | [petrosafe-rag-fa](https://huggingface.co/spaces/alirezaaminzadeh/petrosafe-rag-fa) |
| PermitGuard | PTW risk / SIMOPS text classifier | [permitguard-ptw-risk-classifier](https://huggingface.co/spaces/alirezaaminzadeh/permitguard-ptw-risk-classifier) |

Wiring them requires a dedicated adapter and a timeout that survives Space cold-start — see [`docs/reports/05-huggingface-alignment.md`](docs/reports/05-huggingface-alignment.md). Plant data must **not** be sent to a public Space.

## 8. Local development

On this Windows host, npm/Alpine DNS from inside Docker is unreliable, so **PHP and Vite run on the host**; Postgres/Redis stay in Docker. Port 80 is often taken by local Apache, so the UI uses **5174**.

```powershell
Copy-Item .env.example .env
docker compose up -d postgres redis
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console app:seed --no-interaction
php -S 127.0.0.1:8000 -t public public/index.php
# other terminal:
cd frontend
npm run dev
```

| Surface | URL |
|---|---|
| UI | http://localhost:5174 |
| API health | http://127.0.0.1:8000/api/health |
| OpenAPI | http://127.0.0.1:8000/api/docs |

JWT is signed with `config/jwt/*.pem` (gitignored). If `lexik:jwt:generate-keypair` fails on Windows, generate a passphrase-less RSA pair and set `JWT_PASSPHRASE=` in `.env.local`.

Tests:

```powershell
php bin/phpunit --testdox
```

## 9. Seed users (local / demo only)

Never commit production passwords. Production bootstrap uses `SEED_ALIREZA_PASSWORD` on the server.

| Username / email | Local password | Role |
|---|---|---|
| `alireza` | `alireza` | Operator bootstrap (shared name with PetroOps) |
| `admin` / `admin@hse.aria-ai.ir` | `ChangeMe!Admin1` | `ROLE_ADMIN` |
| `hse` / `hse@hse.aria-ai.ir` | `ChangeMe!Hse1` | `ROLE_HSE_MANAGER` |
| `issuer` / `issuer@hse.aria-ai.ir` | `ChangeMe!Issuer1` | `ROLE_PERMIT_ISSUER` |

Change these before any customer-facing environment.

## 10. Documentation map

| Path | Language | Contents |
|---|---|---|
| [`README.md`](README.md) | EN then FA | This file |
| [`docs/DOCUMENTATION.md`](docs/DOCUMENTATION.md) | EN then FA | Index of every manual and report |
| [`docs/01-overview.md`](docs/01-overview.md) … [`docs/10-roadmap.md`](docs/10-roadmap.md) | FA | Original engineering manuals |
| [`docs/en/`](docs/en/) | EN | English manuals (overview through roadmap) |
| [`docs/reports/`](docs/reports/) | EN then FA | Status, feature matrix, KPIs, gaps, HF, CI, sister-system |
| [`CONTRIBUTING.md`](CONTRIBUTING.md) | EN then FA | How to work on this repo |
| [`SECURITY.md`](SECURITY.md) | EN then FA | How to report issues; what is in / out of scope |

## 11. CI/CD

Push to `main`: lint PHP → PHPUnit (Timescale service) → frontend `npm ci && npm run build` → Docker build/push to GHCR → SSH deploy, `docker compose pull && up -d`, Nginx reload.

Required GitHub environment secrets: `SAFEOPS_SSH_HOST`, `SAFEOPS_SSH_USER`, `SAFEOPS_SSH_KEY`. Images: `ghcr.io/alireza-aminzadeh/aria-safeops-app:<sha>`.

## 12. What is still open (Phase 3)

| Gap | Why it matters |
|---|---|
| Real RAG/LLM + PermitGuard in the permit form | Competitive differentiation vs GITA-style e-PTW |
| Spatial SIMOPS + multi-point LOTO certificates | Control of Work completeness |
| Qualified electronic signature / PKI | Name + content hash is not a qualified e-sign |
| Tenant-scoped contractors + multi-customer onboarding | SaaS / multi-site |
| SSO (Keycloak), MFA, SMS/push | Enterprise identity |
| Full offline field PWA | Gas tests in the unit without radio |

Honest gap tables: [`docs/reports/04-gap-analysis.md`](docs/reports/04-gap-analysis.md).

---

<a id="persian"></a>

# آریا سیف‌آپس («ایمن‌کار») — فارسی

سامانهٔ **مجوز کار الکترونیک، مدیریت ایمنی فرآیند (PSM) و عملیات HSE** برای واحدهای نفت، گاز و پتروشیمی. محصول Control of Work مجموعهٔ [آریا اِی‌آی](https://aria-ai.ir).

**دورهٔ پروژه:** گردآوری اطلاعات، تحلیل و آنالیز فرآیندها، و اجرای این سامانه در سال‌های **۲۰۲۴، ۲۰۲۵ و ۲۰۲۶** انجام شده است.

**زنده:** [https://hse.aria-ai.ir](https://hse.aria-ai.ir) · **مستندات API:** [https://hse.aria-ai.ir/api/docs](https://hse.aria-ai.ir/api/docs)

سامانهٔ خواهر: [Aria PetroOps](https://github.com/alireza-aminzadeh/aria-petroops) (`petro.aria-ai.ir`). قرارداد مشترک `equipment_tag` اجازه می‌دهد آنومالی بازِ فرآیندی، صدور یا فعال‌سازی مجوز/MOC را نگه دارد.

## ۱. وضعیت یک‌نگاه (۲۲ شهریور ۱۴۰۵)

| مورد | مقدار |
|---|---|
| نام محصول | Aria SafeOps («ایمن‌کار») |
| دورهٔ پروژه | سال‌های ۲۰۲۴، ۲۰۲۵ و ۲۰۲۶ (گردآوری اطلاعات، تحلیل فرآیندها، اجرا) |
| آدرس عمومی | https://hse.aria-ai.ir |
| تحویل فعلی | فاز ۱ هستهٔ BPMS کامل + بیشتر فاز ۲ (PSM، Vision شبیه‌ساز، شیفت، اضطراری/محیط‌زیست، Mercure، بستهٔ دانش on-prem) |
| بک‌اند | PHP 8.4 · Symfony 7.4 LTS · API Platform 4 · JWT · Symfony Workflow |
| فرانت‌اند | React 19 · TypeScript · Vite · Tailwind v4 (RTL) |
| داده | PostgreSQL 16 + TimescaleDB · Redis 7.4 |
| هوشمندی | درگاه سه‌سطحی: خاموش / بستهٔ دانش کلیدواژه‌ای / HTTP RAG (هنوز به Production وصل نشده) |
| CI/CD | GitHub Actions → GHCR → استقرار SSH |

## ۲. ماژول‌ها

| ماژول | آنچه امروز در UI هست | محدودیت صادقانه |
|---|---|---|
| مجوز کار الکترونیک | گرم/سرد/فضای بسته/ارتفاع/حفاری/برق، گاز‌تست، پرچم LOTO، تعارض رشته‌ای SIMOPS، QR، hold از PetroOps | SIMOPS روی نقشه و گواهی قفل چندنقطه‌ای نیست |
| MOC / PSSR | موقت/دائم/اضطراری؛ دور زدن PSSR ممکن نیست | رجیستر دارایی فراتر از رشتهٔ تگ نیست |
| رجیستر PSM | HAZOP / LOPA / Bowtie + شاخص API 754 | ابزار کامل PHA نیست |
| ممیزی PSM | ۱۴ عنصر OSHA 1910.119 با امتیاز | گردش‌کار مؤسسهٔ ممیزی ثالث نیست |
| حوادث + CAPA | ثبت، RCA، CAPA، LTIFR/TRIR | ارسال خودکار به نظام HSE وزارت نفت نیست |
| اضطراری و محیط‌زیست | طرح ERP، مانور، پایش پساب | جلوتر از برنامهٔ اولیه در فاز ۲ آمده |
| شیفت / لاگ‌بوک | تحویل شیفت ساخت‌یافته + Toolbox Talk | جایگزین کامل لاگ اتاق کنترل نیست |
| پیمانکار | گواهی، آموزش، هشدار انقضا | هنوز بدون `tenant` روی پیمانکار |
| HSE Vision | رجیستر دوربین + شبیه‌ساز PPE | استنتاج GPU روی RTSP زنده نیست |
| دستیار دانش | بازیابی کلیدواژه‌ای روی چند سند HSE | RAG برداری / LLM نیست |
| پوستهٔ PWA | Service worker + صف IndexedDB | فرم کامل آفلاین گاز‌تست نیست |

## ۳. گزارش‌ها و KPI داخل سامانه

| صفحه | مسیر | گزارش |
|---|---|---|
| داشبورد عملیات | `/` | شمار مجوزها، API 754، LTIFR/TRIR، گواهی در حال انقضا |
| مجوز کار | `/permits` | وضعیت، گاز‌تست، گذارها، دلیل hold |
| MOC | `/moc` | نوع تغییر، قفل PSSR |
| PSM | `/psm` | جداول HAZOP/LOPA/Bowtie و Tier 1–3 |
| ممیزی PSM | `/psm-audit` | امتیاز ۱۴ عنصر |
| حوادث | `/incidents` | ثبت‌شدنی در برابر near-miss |
| اضطراری | `/emergency` | مانور و انطباق پساب |
| Vision | `/vision` | رویدادهای شبیه‌سازی‌شده |
| دستیار | `/ai` | پاسخ + استناد یا «در دسترس نیست» |

LTIFR و TRIR تا وقتی **ساعت‌کار دوره** ثبت نشود محاسبه نمی‌شوند (مخرج ساختگی نیست).

جزئیات: [`docs/reports/04-in-app-kpis-and-reports.md`](docs/reports/04-in-app-kpis-and-reports.md).

## ۴. اجرای محلی

```powershell
Copy-Item .env.example .env
docker compose up -d postgres redis
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console app:seed --no-interaction
php -S 127.0.0.1:8000 -t public public/index.php
cd frontend
npm run dev
```

- UI: http://localhost:5174
- API: http://127.0.0.1:8000/api/health

رمزهای Production را در این README نمی‌گذاریم. روی سرور `SEED_ALIREZA_PASSWORD` را ست کنید. کاربران بذر محلی در بخش انگلیسی همین فایل آمده‌اند.

## ۵. نقشهٔ مستندات

| مسیر | زبان |
|---|---|
| [`docs/`](docs/) (۰۱ تا ۱۰) | فارسی — اسناد مهندسی اصلی |
| [`docs/en/`](docs/en/) | انگلیسی — همان سرفصل‌ها |
| [`docs/reports/`](docs/reports/) | انگلیسی سپس فارسی — وضعیت، ماتریس قابلیت، KPI، شکاف‌ها، Hugging Face، CI، پیوند PetroOps |

نسخهٔ فارسیِ همین صفحه همان ادامهٔ فایل است؛ نسخهٔ انگلیسی را از [ابتدای فایل](#english) بخوانید.
