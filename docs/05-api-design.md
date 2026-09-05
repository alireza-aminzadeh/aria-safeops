# ۵) طراحی API — Aria SafeOps

## ۵.۱ اصول کلی
- **API Platform** روی Symfony 7.4 — تولید خودکار REST از Doctrine Entity (با `#[ApiResource]` attribute)، OpenAPI/Swagger خودکار.
- فرمت خطا: **RFC 7807 (`application/problem+json`)** — پیش‌فرض API Platform، نیازی به پیاده‌سازی دستی نیست.
- Base URL: `https://hse.aria-ai.ir/api`
- مستندات زندهٔ API: `https://hse.aria-ai.ir/api/docs` (Swagger UI) و `/api/docs.jsonopenapi` برای codegen فرانت‌اند.
- احراز هویت: `Authorization: Bearer <JWT>` (LexikJWTAuthenticationBundle).
- ورود: `POST /api/login` با `{ "username": "alireza", "password": "..." }` (ایمیل هم به‌عنوان شناسه پذیرفته می‌شود).
  - لوکال: `alireza` / `alireza`
  - Production: `alireza` / `Aria7x!Alireza#Ops2026`
- Pagination: `?page=1&itemsPerPage=30` (پیش‌فرض API Platform، قابل تنظیم).
- فیلتر: `SearchFilter`, `OrderFilter`, `DateFilter` روی فیلدهای مشخص‌شده در `#[ApiFilter]`.

## ۵.۲ Endpoint های خودکار (نمونه — از Doctrine Entity تولید می‌شود)

| متد | مسیر | توضیح |
|---|---|---|
| `POST` | `/api/login` | ورود با `username` (یا ایمیل) و `password`؛ پاسخ JWT |
| `GET` | `/api/permits` | فهرست مجوزها (فیلتر بر status, permit_type, equipment_tag) |
| `POST` | `/api/permits` | ایجاد مجوز جدید (status اولیه: `draft`) |
| `GET` | `/api/permits/{id}` | جزئیات یک مجوز |
| `PATCH` | `/api/permits/{id}` | ویرایش فیلدهای مجاز (فقط در status `draft`) |
| `GET` | `/api/moc-requests` | فهرست درخواست‌های MOC |
| `POST` | `/api/moc-requests` | ایجاد MOC جدید |
| `GET` | `/api/incidents` | فهرست حوادث |
| `POST` | `/api/incidents` | ثبت حادثه/near-miss |
| `GET` | `/api/contractors` | فهرست پیمانکاران |

## ۵.۳ Endpoint های سفارشی (Custom State Processor — گذار Workflow)

چون تغییر state machine یک عملیات دامنه‌ای است (نه یک PATCH سادهٔ فیلد)، از **Custom API Platform Processor** استفاده می‌شود:

| متد | مسیر | Body | توضیح |
|---|---|---|---|
| `POST` | `/api/permits/{id}/transitions` | `{"transition": "approve"}` | اجرای گذار روی Workflow؛ اگر Guard بلوکه کند، `422 Unprocessable Entity` با پیام دلیل برمی‌گرداند |
| `GET` | `/api/permits/{id}/available-transitions` | — | فهرست گذارهای مجاز فعلی برای کاربر جاری (برای نمایش دکمه‌های صحیح در UI) |
| `POST` | `/api/permits/{id}/gas-test-readings` | `{"gasType": "LEL", "value": 0}` | ثبت قرائت گاز‌تست |
| `POST` | `/api/moc-requests/{id}/transitions` | `{"transition": "approve"}` | مشابه بالا برای MOC |
| `POST` | `/api/incidents/{id}/capa-actions` | `{...}` | افزودن اقدام اصلاحی |

نمونهٔ پیاده‌سازی Processor:

```php
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/permits/{id}/transitions',
            processor: PermitTransitionProcessor::class,
        ),
    ]
)]
final class PermitTransitionInput
{
    public string $transition;
}

final class PermitTransitionProcessor implements ProcessorInterface
{
    public function __construct(
        private WorkflowInterface $permitToWorkStateMachine,
        private PermitRepository $permits,
    ) {}

    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $permit = $this->permits->find($uriVariables['id']);

        if (!$this->permitToWorkStateMachine->can($permit, $data->transition)) {
            throw new UnprocessableEntityHttpException('این گذار در وضعیت فعلی مجاز نیست.');
        }

        $this->permitToWorkStateMachine->apply($permit, $data->transition);
        $this->permits->save($permit);

        return $permit;
    }
}
```

## ۵.۴ Endpoint های AI Gateway (Placeholder — بخش ۶)

| متد | مسیر | وضعیت فعلی |
|---|---|---|
| `POST` | `/api/ai/knowledge-query` | همیشه `503 Service Unavailable` با پیام «سرویس دستیار هوشمند هنوز فعال نشده است» — پیاده‌سازی واقعی در فاز بعد |

## ۵.۵ نسخه‌بندی API
فاز ۱: بدون versioning صریح (فقط `/api/*`). اگر breaking change لازم شد، از هدر `Accept: application/vnd.aria.safeops.v2+json` طبق پشتیبانی توکار API Platform استفاده می‌شود، نه تغییر مسیر.

## ۵.۶ Rate Limiting
`symfony/rate-limiter` روی مسیرهای حساس (`/api/login`, `/api/ai/*`) — پیش‌گیری از brute-force و سوءاستفاده، طبق چک‌لیست امنیتی ([`07-security.md`](07-security.md)).
