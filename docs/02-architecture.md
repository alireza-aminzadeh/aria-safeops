# ۲) معماری — Aria SafeOps

## ۲.۱ الگوی کلی
**Modular Monolith** روی Symfony — هر دومین کسب‌وکاری (Bounded Context) یک Bundle/Namespace مجزا در `src/Domain/*` است. معماری **Headless API-first**: Symfony فقط API می‌دهد؛ فرانت‌اند React کاملاً مستقل مصرف‌کننده است.

```
┌─────────────────────┐        HTTPS/JSON         ┌──────────────────────────┐
│  React 19 + TS SPA   │ ─────────────────────────▶│  Nginx (reverse proxy)   │
│  (Vite build static) │◀───────────────────────── │  + سرو فایل استاتیک SPA  │
└─────────────────────┘                            └───────────┬──────────────┘
                                                                │ FastCGI
                                                     ┌──────────▼──────────────┐
                                                     │  Symfony 7.4 + API      │
                                                     │  Platform (PHP-FPM)     │
                                                     │  - Domain/Permit        │
                                                     │  - Domain/Moc           │
                                                     │  - Domain/Incident      │
                                                     │  - Domain/Contractor    │
                                                     │  - Infrastructure/      │
                                                     │      AiGateway (stub)   │
                                                     └──┬───────────┬──────────┘
                                                        │           │
                                          ┌─────────────▼───┐   ┌───▼──────┐
                                          │ PostgreSQL 16 +  │   │  Redis 7 │
                                          │ TimescaleDB      │   │ (cache + │
                                          │                  │   │ queue)   │
                                          └──────────────────┘   └──────────┘

                                          (آینده — سرویس مستقل، فاز ۲+)
                                          ┌──────────────────────────┐
                                          │ AI Gateway (FastAPI +    │
                                          │ Qdrant + LLM) — خارج از  │
                                          │ این سرور، هنوز نساخته    │
                                          └──────────────────────────┘
```

## ۲.۲ ساختار پوشه‌های اپلیکیشن (هدف فاز اسکلت‌سازی)

```
aria-safeops/
├── src/
│   ├── Domain/
│   │   ├── Permit/
│   │   │   ├── Entity/Permit.php, PermitType.php, GasTestReading.php
│   │   │   ├── Workflow/ (Guard listeners, transition subscribers)
│   │   │   ├── Voter/PermitVoter.php          ← ABAC
│   │   │   ├── Repository/
│   │   │   └── Api/ (API Platform State Providers/Processors سفارشی)
│   │   ├── Moc/
│   │   │   ├── Entity/MocRequest.php, HazopRegisterItem.php
│   │   │   └── Workflow/
│   │   ├── Incident/
│   │   │   └── Entity/Incident.php, CapaAction.php
│   │   ├── Contractor/
│   │   │   └── Entity/Contractor.php, Certification.php
│   │   └── Shared/
│   │       ├── Entity/Tenant.php, User.php, AuditLogEntry.php
│   │       └── ValueObject/
│   ├── Infrastructure/
│   │   ├── AiGateway/                          ← Placeholder (بخش ۶)
│   │   │   ├── AiGatewayInterface.php
│   │   │   ├── NullAiGatewayAdapter.php
│   │   │   └── HttpAiGatewayAdapter.php (فاز بعد — غیرفعال)
│   │   ├── Audit/ (Hash-chain writer)
│   │   └── Notification/
│   └── Kernel.php
├── config/
│   ├── packages/
│   │   ├── workflow.yaml                       ← تعریف State Machine ها (بخش ۳)
│   │   ├── api_platform.yaml
│   │   ├── security.yaml                       ← RBAC + JWT
│   │   └── lexik_jwt_authentication.yaml
│   └── jwt/ (کلیدهای private/public — gitignored)
├── migrations/                                  ← Doctrine Migrations
├── frontend/                                     ← React 19 + TS + Vite (مستقل)
│   ├── src/
│   │   ├── modules/permit/, moc/, incident/, contractor/
│   │   ├── shared/ (UI Kit shadcn + RTL + i18n fa/en)
│   │   └── lib/apiClient.ts (تولیدشده از OpenAPI)
│   └── vite.config.ts
├── tests/ (PHPUnit + Pest)
├── docker-compose.yml / .override.yml
└── docs/ (همین پوشه)
```

## ۲.۳ لایه‌بندی و مسئولیت‌ها (Separation of Concerns)
| لایه | مسئولیت | مثال |
|---|---|---|
| `Api/` (API Platform Resource + State Provider/Processor) | تبدیل درخواست HTTP ↔ Entity، اعتبارسنجی ورودی | `PermitResource`, `PermitTransitionProcessor` |
| `Domain/*/Entity` | مدل دامنه، قوانین کسب‌وکار پایه، Doctrine mapping | `Permit`, `MocRequest` |
| `Domain/*/Workflow` | Listener های Guard/Transition روی Symfony Workflow | `PermitWorkflowSubscriber` |
| `Domain/*/Voter` | تصمیم ABAC («آیا این کاربر می‌تواند این عملیات را انجام دهد؟») | `PermitVoter::canApprove()` |
| `Infrastructure/` | جزئیات فنی مستقل از دامنه (DB خام، HTTP client، فایل) | `HttpAiGatewayAdapter` |

## ۲.۴ چندمستأجری (Multi-tenancy)
- **Row-Level Security در PostgreSQL** روی `tenant_id` برای ایزولاسیون داده بین سایت‌های عملیاتی مختلف (اگر یک نصب SafeOps بین چند واحد/شرکت به اشتراک گذاشته شود).
- در فاز ۱ (یک مشتری Pilot) این به‌صورت یک `tenant_id` ثابت پیاده می‌شود؛ RLS از روز اول فعال است تا در فاز رشد نیازی به Migration بزرگ نباشد.

## ۲.۵ Audit Trail غیرقابل‌تغییر (الزام امنیتی پروژه)
هر تغییر روی موجودیت‌های حساس (Permit, MocRequest, Incident) یک ردیف در جدول `audit_log` می‌نویسد:

```
hash[n] = SHA-256( hash[n-1] || tenant_id || entity || entity_id || action || actor_id || payload_json || timestamp )
```

هر ردیف `prev_hash` را نگه می‌دارد؛ اگر ردیفی دستکاری شود، زنجیره از آن نقطه به بعد نامعتبر می‌شود. یک دستور کنسول (`app:audit:verify-chain`) برای بازرسی دوره‌ای زنجیره اضافه می‌شود.

## ۲.۶ i18n و تقویم جلالی
- پیام‌ها/برچسب‌ها: `translations/messages.fa.yaml` + `messages.en.yaml`.
- تاریخ‌ها در دیتابیس همیشه UTC/میلادی (`DateTimeImmutable`)؛ نمایش جلالی فقط در فرانت‌اند با `date-fns-jalali`.
- تمام فرم‌ها و جدول‌ها RTL-first (Tailwind `dir="rtl"` پیش‌فرض).
