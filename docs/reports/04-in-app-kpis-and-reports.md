# Report 04 — In-app KPIs and reports

**Language:** English first, then فارسی.  
**Source:** `DashboardPage.tsx`, `Api754Controller.php`, `SafetyKpiCalculator.php`, `PsmAuditScoreCalculator.php`, `PsmPage.tsx`

These are the **operational reports inside the application**. They are not Excel exports. Formulas below match the PHP calculators.

## English

### 4.1 Operations dashboard (`/`)

| Card | Meaning | Null / empty behaviour |
|---|---|---|
| Permit counts by status | Sum of workflow markings for the tenant | Zero is a real zero |
| API 754 Tier 1 | Count of serious process-safety events in window | Integer |
| API 754 Tier 2 | Lesser LOPC-class events | Integer |
| Open high HAZOP | Leading indicator: high-risk HAZOP items still open | Integer |
| Open Vision events | Open PPE / restricted-area simulator events | Integer |
| LTIFR (12 months) | Lost-time injury frequency | `—` until hours worked exist |
| TRIR (12 months) | Total recordable incident rate | `—` until hours worked exist |
| Lost-time injuries TTM | Count | Integer |
| Expiring / expired certs | Contractor certificates | Integer |

HSE can **post hours worked** for a period. That row becomes the denominator for LTIFR/TRIR. The UI will not fabricate hours.

### 4.2 Safety rate formulas

Implemented in `App\Domain\Incident\SafetyKpiCalculator`:

| KPI | Formula used in code | Standard framing |
|---|---|---|
| LTIFR | `lostTimeInjuries / hoursWorked * 1_000_000` | Lost-time injuries per million hours |
| TRIR | `recordableCount / hoursWorked * 1_000_000` | Recordable incidents per million hours |

Recordable is a flag on the incident (`recordable`), not an inferred OSHA decision engine.

### 4.3 API 754 process-safety indicators

`GET /api/kpis/api754` returns trailing tiers and leading counts (open high HAZOP, open Vision, expired certifications). Tiers are counted from PSM/incident data in the tenant — they are **operational tallies**, not a certified API 754 submission pack.

### 4.4 PSM audit score (`/psm-audit`)

| Item | Behaviour |
|---|---|
| Element catalogue | 14 OSHA 29 CFR 1910.119 elements (`PsmElements`) |
| Findings | Per-element status, notes, score contribution |
| Complete | Locks scoring via `PsmAuditScoreCalculator` |
| Rating | Derived from completed findings (see calculator) |

OSHA elements (EN / FA):

| Code | English | فارسی |
|---|---|---|
| employee_participation | Employee Participation | مشارکت کارکنان |
| process_safety_information | Process Safety Information | اطلاعات ایمنی فرآیند |
| process_hazard_analysis | Process Hazard Analysis | تحلیل خطر فرآیند |
| operating_procedures | Operating Procedures | دستورالعمل‌های عملیاتی |
| training | Training | آموزش |
| contractors | Contractors | پیمانکاران |
| pre_startup_safety_review | Pre-Startup Safety Review | بازبینی ایمنی پیش از راه‌اندازی |
| mechanical_integrity | Mechanical Integrity | یکپارچگی مکانیکی |
| hot_work_permit | Hot Work Permit | مجوز کار گرم |
| management_of_change | Management of Change | مدیریت تغییر |
| incident_investigation | Incident Investigation | تحقیق حادثه |
| emergency_planning | Emergency Planning and Response | برنامه‌ریزی و واکنش اضطراری |
| compliance_audits | Compliance Audits | ممیزی انطباق |
| trade_secrets | Trade Secrets | اسرار تجاری |

### 4.5 Other operational tables (not named “report” in the nav)

| Screen | Table the operator sees |
|---|---|
| Permits | Filterable list: type, tag, status, validity |
| Gas tests | O2, LEL, H2S, CO readings on the permit |
| HAZOP register | Node, deviation, consequence, safeguards, status |
| Incidents | Severity, recordable, CAPA list |
| Effluent | Reading vs limit, compliance policy result |
| Vision | Camera, event type, open/closed |
| Audit log | Hash-chain entries (console verify command in docs) |

### 4.6 What is not an in-app report yet

| Missing report | Why |
|---|---|
| Ministry of Petroleum HSE statutory pack | No connector |
| Permit volume → TAR plan (PetroOps) | Integration not built |
| Board PDF / scheduled email KPI pack | No report scheduler |
| English UI of the same dashboards | SPA is FA-only |

---

## فارسی

داشبورد عملیات شمار مجوز، شاخص‌های API 754، رویداد Vision باز، گواهی در حال انقضا، و LTIFR/TRIR را نشان می‌دهد. LTIFR و TRIR تا ثبت ساعت‌کار دوره `null` می‌مانند. فرمول: تعداد × ۱٬۰۰۰٬۰۰۰ ÷ ساعت‌کار.

ممیزی PSM روی ۱۴ عنصر OSHA امتیاز می‌دهد. رجیستر HAZOP، گاز‌تست، پساب و Vision جدول عملیاتی دارند ولی خروجی PDF سازمانی و اتصال به نظام HSE وزارت نفت هنوز نیست.
