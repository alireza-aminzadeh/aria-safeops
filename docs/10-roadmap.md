# ۱۰) نقشهٔ راه — Aria SafeOps

## فاز ۰ — پیش‌نیاز زیرساخت (این مرحله، مستندسازی + آماده‌سازی سرور)
- [x] بررسی و مستندسازی معماری، DB schema، API design، BPMS، امنیت (همین مستندات)
- [x] بررسی SSH read-only سرور `91.107.130.11` (مشخصات واقعی گرفته شد)
- [ ] نصب Docker Engine + Compose plugin روی سرور (بخش [`08-infrastructure-deployment.md`](08-infrastructure-deployment.md))
- [ ] ساخت کاربر `deploy` غیر-root + فعال‌سازی UFW (بخش [`07-security.md`](07-security.md))
- [ ] ساخت ریپوی GitHub + اتصال Secrets برای CI/CD (بخش [`09-ci-cd.md`](09-ci-cd.md))
- [ ] رفع مشکل DNS زیردامنهٔ `hse.aria-ai.ir` (باید A record به `91.107.130.11` اشاره کند)

## فاز ۱ — MVP: هستهٔ BPMS (تمرکز فعلی)
- [x] اسکلت‌سازی Symfony 7.4 + API Platform (`composer create-project symfony/skeleton`)
- [x] Entity ها + Migration های `tenants`, `users`, `permit_types`, `permits`, `moc_requests`, `incidents`, `contractors`, `audit_log`, `ai_query_log`
- [x] پیاده‌سازی `permit_to_work` و `moc_workflow` (Symfony Workflow) با Guard/Voter کامل
- [x] CRUD کامل API Platform + Custom Transition Processor
- [x] JWT Auth + RBAC پایه
- [x] فرانت‌اند React 19 + TS + Vite: فرم صدور/تأیید مجوز، فرم MOC، ثبت حادثه، داشبورد وضعیت
- [x] AI Gateway Placeholder (Interface + Stub + جدول DB) — **بدون هیچ inference واقعی**
- [ ] استقرار اولیهٔ Production روی `91.107.130.11` + CI/CD کامل (پایپ‌لاین آماده است؛ نصب Docker/DNS/کاربر deploy روی سرور هنوز عملیات دستی است)

## فاز ۲ — تکمیل PSM و اتصال واقعی AI
- [ ] رجیستر HAZOP/LOPA/Bowtie کامل + KPI مطابق API 754
- [ ] HSE Vision (تشخیص PPE) — نیاز به مدل Vision و دوربین RTSP واقعی
- [ ] تحویل شیفت/Logbook + Toolbox Talk
- [ ] فعال‌سازی واقعی AI Gateway: تغییر `AI_GATEWAY_ENABLED=true` + پیاده‌سازی `HttpAiGatewayAdapter` بعد از راه‌اندازی سرویس RAG/LLM مستقل (سرور/زیرساخت سوم، مشترک با PetroOps)
- [ ] اتصال بین‌سامانه‌ای واقعی به PetroOps (بلوکه‌کردن مجوز روی تجهیز با آنومالی باز)
- [ ] Mercure برای اعلان real-time (فعلاً کامنت‌شده در `docker-compose.yml`)

## فاز ۳ — بلوغ
- [ ] واکنش اضطراری و محیط‌زیست (ERP، مانور، پایش پساب)
- [ ] کوپایلوت عاملی (Agentic) برای پیش‌نویس خودکار مجوز از تاریخچهٔ JSA
- [ ] چندمستأجری کامل (چند مشتری هم‌زمان) — RLS از فاز ۱ آماده شده، فقط فعال‌سازی onboarding چندگانه

## معیار موفقیت فاز ۱ (Definition of Done)
- یک مجوز کار می‌تواند از `draft` تا `closed` بدون خطا و با Audit Trail کامل عبور کند.
- یک MOC می‌تواند تا `pssr` و `closed` برود بدون این‌که بتوان PSSR را دور زد.
- تمام تست‌های PHPUnit/Pest سبز، پایپ‌لاین CI/CD کامل تا Deploy روی `91.107.130.11` کار می‌کند.
- دکمه‌های AI Gateway در UI دیده می‌شوند ولی صادقانه «به‌زودی» نشان می‌دهند، بدون کرش.
