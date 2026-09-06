# ۶) جای‌گذاری RAG/LLM بدون پیاده‌سازی (AI Gateway Placeholder)

## ۶.۱ تصمیم صریح
طبق دستور مستقیم پروژه: **بخش RAG/LLM واقعی (embedding + vector DB + مدل مولد) در این فاز پیاده‌سازی نمی‌شود**، اما جای آن در معماری، دیتابیس و UI به‌طور کامل رزرو شده و پشت `AiGatewayInterface` قرار دارد تا وصل کردن سرویس واقعی در آینده فقط یک «سوییچ کانفیگ» باشد، نه ری‌فکتور.

> **به‌روزرسانی (پیاده‌سازی واقعی کد):** برخلاف نسخهٔ اولیهٔ این سند، پیاده‌سازی فعلی دیگر فقط یک Stub تک‌حالته نیست. یک **`AiGatewayFactory`** سه سطح را مدیریت می‌کند (پایین را ببینید) و سطح دوم آن — «بستهٔ دانش محلی» (`OnPremAiGatewayAdapter` + `HseKnowledgePack`) — کاملاً **واقعی و فعال** است: بازیابی متن با تطبیق کلیدواژه روی چند سند مرجع HSE (API 754، NFPA، ...) و طبقه‌بندی ریسک با regex روی کلمات کلیدی (گاز/H2S/کار گرم/فضای بسته/ارتفاع/حفاری/برق). این هنوز RAG/LLM واقعی **نیست** (نه embedding، نه مدل مولد، نه جستجوی معنایی) و طبق مصوبهٔ پروژه در گزارش پیشرفت به‌عنوان RAG/LLM شمرده نمی‌شود، اما دیگر «۵۰۳ همیشگی» هم نیست.

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

## ۶.۳ پیاده‌سازی فعلی — Factory سه‌سطحی

`AiGatewayFactory::create()` بسته به کانفیگ، یکی از سه پیاده‌سازی `AiGatewayInterface` را برمی‌گرداند:

```php
namespace App\Infrastructure\AiGateway;

final class AiGatewayFactory
{
    public function __construct(
        private readonly NullAiGatewayAdapter $null,
        private readonly OnPremAiGatewayAdapter $onPrem,
        private readonly HttpAiGatewayAdapter $http,
    ) {}

    public function create(): AiGatewayInterface
    {
        if (getenv('AI_GATEWAY_ENABLED') !== 'true') {
            return $this->null;       // ۱) کاملاً خاموش
        }
        if ($this->http->isEnabled()) {
            return $this->http;       // ۳) RAG/LLM واقعی خارجی (فاز بعد، هنوز پیاده نشده در تولید)
        }

        return $this->onPrem;         // ۲) بستهٔ دانش محلی — پیش‌فرض وقتی فعال است
    }
}
```

| سطح | کلاس | فعال وقتی | رفتار |
|---|---|---|---|
| ۱. خاموش | `NullAiGatewayAdapter` | `AI_GATEWAY_ENABLED=false` (مقدار فعلی `.env` محلی) | همیشه `unavailable`/۵۰۳ با پیام «هنوز فعال نشده» |
| ۲. بستهٔ دانش محلی (**واقعی، بدون LLM**) | `OnPremAiGatewayAdapter` + `HseKnowledgePack` | `AI_GATEWAY_ENABLED=true` (پیش‌فرض `.env.example`) و بدون `AI_GATEWAY_URL` | تطبیق کلیدواژه روی چند سند HSE مرجع (`HseKnowledgePack::retrieve`) + طبقه‌بندی ریسک با regex کلیدواژه‌ای |
| ۳. RAG/LLM خارجی واقعی | `HttpAiGatewayAdapter` | `AI_GATEWAY_URL` تنظیم و در دسترس باشد | HTTP client ساده به سرویس AI Gateway مرکزی (Qdrant + embedding + LLM) — **هنوز در تولید فعال نشده** |

Wiring در `config/services.yaml` (بدون نیاز به تغییر دستی هنگام سوییچ محیط):
```yaml
services:
    App\Infrastructure\AiGateway\AiGatewayInterface:
        factory: ['@App\Infrastructure\AiGateway\AiGatewayFactory', 'create']
```

## ۶.۴ نقاط اتصال UI
| مکان | رفتار فعلی |
|---|---|
| دکمهٔ «پرسش از دستیار دانش HSE» در فرم مجوز/MOC و صفحهٔ AI | وقتی سطح ۱ (خاموش) است: تلاش برای پرسش پیام «هنوز فعال نشده» برمی‌گرداند. وقتی سطح ۲ یا ۳ فعال است: پاسخ واقعی (متن + منبع/citation) از بستهٔ دانش محلی یا سرویس خارجی نمایش داده می‌شود؛ فرانت‌اند (`AiPage.tsx`) هر دو حالت را هندل می‌کند، نه فقط ۵۰۳ |
| پیشنهاد خودکار محتوای JSA هنگام ایجاد مجوز | فیلد `jsaReference` در فرم وجود دارد اما به‌صورت دستی پر می‌شود؛ دکمهٔ «پیشنهاد هوشمند» غیرفعال (این بخش هنوز به `classifyRiskText` وصل نشده) |

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
