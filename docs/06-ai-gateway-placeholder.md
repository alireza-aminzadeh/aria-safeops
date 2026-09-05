# ۶) جای‌گذاری RAG/LLM بدون پیاده‌سازی (AI Gateway Placeholder)

## ۶.۱ تصمیم صریح
طبق دستور مستقیم پروژه: **بخش RAG/LLM در این فاز پیاده‌سازی نمی‌شود**، اما جای آن در معماری، دیتابیس و UI به‌طور کامل رزرو می‌شود تا وصل کردن سرویس واقعی در آینده فقط یک «سوییچ کانفیگ» باشد، نه ری‌فکتور.

## ۶.۲ رابط (Interface) — قرارداد پایدار

```php
namespace App\Infrastructure\AiGateway;

interface AiGatewayInterface
{
    public function isEnabled(): bool;

    /** پرسش از دستیار دانش HSE (RAG روی IPS/API/ASME/NFPA + دستورالعمل داخلی) */
    public function askKnowledgeBase(string $query, array $context = []): AiAnswer;

    /** طبقه‌بندی ریسک متن آزاد (مثلاً پیش‌نویس هوشمند مجوز از تاریخچهٔ JSA) */
    public function classifyRiskText(string $text, array $context = []): AiRiskClassification;
}
```

```php
namespace App\Infrastructure\AiGateway;

final class AiAnswer
{
    private function __construct(
        public readonly bool $available,
        public readonly ?string $text,
        public readonly array $citations,
        public readonly ?string $unavailableReason,
    ) {}

    public static function unavailable(string $reason): self
    {
        return new self(available: false, text: null, citations: [], unavailableReason: $reason);
    }

    public static function fromResponse(string $text, array $citations): self
    {
        return new self(available: true, text: $text, citations: $citations, unavailableReason: null);
    }
}
```

## ۶.۳ پیاده‌سازی فعلی — Stub غیرفعال

```php
namespace App\Infrastructure\AiGateway;

/**
 * پیاده‌سازی پیش‌فرض تا زمان راه‌اندازی سرویس AI Gateway واقعی.
 * فعال‌سازی سرویس واقعی فقط با تغییر AI_GATEWAY_ENABLED=true در .env
 * و تعریف HttpAiGatewayAdapter به‌جای این کلاس در services.yaml.
 */
final class NullAiGatewayAdapter implements AiGatewayInterface
{
    public function isEnabled(): bool
    {
        return false;
    }

    public function askKnowledgeBase(string $query, array $context = []): AiAnswer
    {
        return AiAnswer::unavailable('سرویس دستیار هوشمند HSE هنوز فعال نشده است.');
    }

    public function classifyRiskText(string $text, array $context = []): AiRiskClassification
    {
        return AiRiskClassification::unavailable();
    }
}
```

Wiring در `config/services.yaml`:
```yaml
services:
    App\Infrastructure\AiGateway\AiGatewayInterface:
        # فاز فعلی: همیشه Stub. فاز بعد: alias به HttpAiGatewayAdapter بر اساس AI_GATEWAY_ENABLED
        class: App\Infrastructure\AiGateway\NullAiGatewayAdapter
```

## ۶.۴ نقاط اتصال UI (غیرفعال ولی موجود)
| مکان | رفتار فعلی |
|---|---|
| دکمهٔ «پرسش از دستیار دانش HSE» در فرم مجوز/MOC | نمایش داده می‌شود ولی با tooltip «به‌زودی» غیرفعال است؛ کلیک آن endpoint را صدا می‌زند و پاسخ `503` را به پیام کاربرپسند تبدیل می‌کند |
| پیشنهاد خودکار محتوای JSA هنگام ایجاد مجوز | فیلد `jsaReference` در فرم وجود دارد اما به‌صورت دستی پر می‌شود؛ دکمهٔ «پیشنهاد هوشمند» غیرفعال |

## ۶.۵ کانفیگ (از الان در `.env.example` موجود)
```
AI_GATEWAY_ENABLED=false
AI_GATEWAY_URL=
AI_GATEWAY_API_KEY=
AI_GATEWAY_TIMEOUT_MS=8000
```

## ۶.۶ جدول دیتابیس از الان ساخته می‌شود
`ai_query_log` (جزئیات کامل در [`04-database-schema.md`](04-database-schema.md)) — خالی و بلااستفاده در فاز ۱، فقط برای پیشگیری از Migration دردسرساز روی داده Production در فاز بعد.

## ۶.۷ معماری اتصال واقعی در آینده (فقط طراحی — بدون اجرا)
سرویس AI Gateway واقعی (Qdrant + embedding + LLM محلی) **روی این سرور اجرا نخواهد شد** — طبق تحلیل منابع، این سرور (2 vCPU / ~3.7GB) گنجایش inference مدل ندارد. طراحی هدف:

```
┌─────────────────┐  HTTP (internal)   ┌───────────────────────────┐
│ Symfony (این     │ ──────────────────▶│ AI Gateway مرکزی           │
│ سرور 91.107.130.11)                   │ (سرور/زیرساخت سوم جدا،     │
│                  │◀────────────────── │ مشترک با PetroOps هم)     │
└─────────────────┘                    │ FastAPI + Qdrant + LLM     │
                                        └───────────────────────────┘
```

`HttpAiGatewayAdapter` (فاز بعد) فقط یک HTTP client ساده به `AI_GATEWAY_URL` خواهد بود — کد فعلی تغییری نمی‌خواهد جز این کلاس جدید و alias در `services.yaml`.

## ۶.۸ چرا این‌طور طراحی شد (اصول SOLID)
- **Dependency Inversion**: دامنه (`Domain/Permit`) فقط `AiGatewayInterface` را می‌شناسد، نه جزئیات HTTP/Qdrant/LLM.
- **Open/Closed**: افزودن `HttpAiGatewayAdapter` در آینده نیازی به تغییر کد مصرف‌کننده (Controller/Processor) ندارد.
- **تست‌پذیری**: در تست‌های PHPUnit به‌راحتی `NullAiGatewayAdapter` یا یک Mock جای‌گزین می‌شود.
