# Report 01 — Product status

**Language:** English first, then فارسی.  
**Product:** Aria SafeOps (`hse.aria-ai.ir`)  
**Kind:** Public demo — enter from [https://aria-ai.ir](https://aria-ai.ir)  
**As of:** 13 September 2026 (22 Shahrivar 1405)  
**Evidence:** `docs/10-roadmap.md`, PHPUnit suite, production health endpoint, SPA routes

## English

### Executive summary

SafeOps has left the “BPMS core only” stage. Phase 0 infrastructure and Phase 1 Control of Work are done and deployed. Most Phase 2 PSM and operations modules are in production, including emergency/environment (originally sketched as Phase 3). What is **not** done is the competitive AI layer (real RAG + PermitGuard on the permit form), spatial SIMOPS, qualified signatures, and multi-customer tenancy.

Production health (expected): `GET https://hse.aria-ai.ir/api/health` → `{ "status": "ok", "service": "aria-safeops" }`.

### Phase checklist

| Phase | Intent | Status |
|---|---|---|
| 0 — Infrastructure | Docs, Docker on VPS, `deploy` user, UFW, GitHub secrets, DNS `hse.aria-ai.ir` | Done |
| 1 — BPMS MVP | PTW + MOC machines, incidents, contractors, JWT, SPA, AI *placeholder* | Done |
| 2 — PSM + live AI slot | HAZOP/LOPA/Bowtie, API 754, Vision simulator, shift, Mercure, on-prem knowledge pack, PetroOps holds, emergency/effluent | Done (AI is keyword pack, not LLM) |
| 3 — Maturity | Agentic permit draft, full multi-tenant contractor model, SSO | Open |

### Definition of Done — Phase 1 (met)

| Criterion | Result |
|---|---|
| Permit can travel `draft` → `closed` with audit trail | Yes (`PermitWorkflowTest`) |
| MOC cannot skip PSSR | Yes (`MocWorkflowTest`) |
| PHPUnit green in CI | Yes — see report 07 |
| AI buttons do not crash when inference is off | Yes — factory returns unavailable |
| Production deploy on the HSE VPS | Yes |

### Delivery numbers (documentation inventory)

| Kind | Count |
|---|---|
| SPA routes (authenticated) | Dashboard, permits, MOC, PSM, PSM audit, shift, incidents, emergency, vision, contractors, AI, equipment-by-tag |
| PHPUnit test files | 13 |
| Domain contexts under `src/Domain/` | Permit, MOC, Incident, Contractor, PSM, Emergency, Shift, Vision, Integration, Shared |
| Compose production services | nginx, app, postgres, redis, mercure |

### Honest remaining work

| Item | Blocking for |
|---|---|
| HTTP AI gateway + HF models on-prem | Differentiation vs GITA Permit |
| Plot-plan SIMOPS | Control of Work completeness |
| Multi-point LOTO certificates | Isolation integrity |
| Contractor `tenant_id` + onboarding | Second customer |
| SSO / MFA | Enterprise identity policy |

---

## فارسی

این مخزن یک **دموی عمومی** است. برای مشاهده و کار کردن با دموها به [https://aria-ai.ir](https://aria-ai.ir) مراجعه کنید.

فاز ۰ و ۱ تمام شده و روی `hse.aria-ai.ir` مستقر است. بیشتر فاز ۲ (از جمله اضطراری/پساب که در نقشهٔ اولیه فاز ۳ بود) هم در Production است. آنچه باز است لایهٔ هوشمندی رقابتی (RAG واقعی + PermitGuard روی فرم مجوز)، SIMOPS فضایی، امضای واجد شرایط، و چندمستأجری کامل پیمانکار است.

سلامت Production: `GET /api/health` باید `aria-safeops` را با `status=ok` برگرداند.
