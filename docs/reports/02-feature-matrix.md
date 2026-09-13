# Report 02 — Feature matrix

**Language:** English first, then فارسی.  
**Compared against:** workspace strategy document and `docs/10-roadmap.md`  
**As of:** 13 September 2026

## English

Status key: **Built** = in UI + API on production · **Partial** = real code but incomplete vs industrial CoW · **Demo-only** = Hugging Face only · **Missing** = not in this repo.

| Capability | Status | Where in code | Notes |
|---|---|---|---|
| e-PTW types (6) | Built | `PermitType`, Permits SPA | hot, cold, confined, height, excavation, electrical |
| Workflow guards | Built | `PermitWorkflowSubscriber`, Voters | Role + tenant + policy |
| Gas test | Built | `GasTestPolicy`, `GasTestReading` | O2 / LEL / H2S / CO |
| LOTO | Partial | `IsolationPolicy` | Confirmation flag, not lock-point certificates |
| SIMOPS | Partial | `PermitConflictChecker` | Tag/area string match, not plot plan |
| QR on permit | Built | Permit detail | |
| Equipment hold from PetroOps | Built | `EquipmentHold`, `EquipmentHoldChecker` | Create + activate + resume + MOC implementation |
| MOC + PSSR lock | Built | `moc_workflow` | |
| HAZOP / LOPA / Bowtie | Built | PSM entities + `/psm` | |
| API 754 KPI | Built | `Api754KpiCalculator` | T1/T2/T3 + leading |
| PSM 14-element audit | Built | `PsmElements`, `/psm-audit` | OSHA 1910.119 list |
| Incidents + CAPA | Built | Incident machine | Recordable flag for TRIR |
| LTIFR / TRIR | Built | `SafetyKpiCalculator` | Needs hours-worked periods |
| Shift handover + TBT | Built | Shift domain | |
| Emergency ERP / drill | Built | Emergency domain | |
| Effluent compliance | Built | `EffluentCompliancePolicy` | |
| Contractor cert expiry | Partial | Contractor domain | Shared across tenants |
| HSE Vision | Partial | Vision domain | Simulator; optional ingest URL |
| Knowledge assistant | Partial | `OnPremAiGatewayAdapter` | Keywords, not embeddings |
| Risk classify on permit | Partial | `classifyRiskText` regex | PermitGuard Space not called |
| Smart JSA draft | Missing | UI button disabled / Phase 3 | |
| Mercure live banner | Built | `useMercure` | |
| PWA offline queue | Partial | `sw.js`, `offlineQueue.ts` | |
| Email notification | Partial | Mailer optional, else log | |
| Qualified e-sign | Partial | Name + content hash | Not PKI |
| SSO / LDAP / MFA | Missing | Local JWT only | |
| i18n English UI | Missing | FA UI; calendar switch is PetroOps | |
| MinIO document vault | Missing | | |
| RAG FastAPI + Qdrant | Missing | Port ready via `AI_GATEWAY_URL` | |

### Competitive reading

A buyer comparing SafeOps to a GITA-style e-PTW will see a **credible BPMS** with API 754 and a PetroOps hold. They will **not** yet see a cited RAG answer on a live permit or a spatial SIMOPS map. That is the gap this matrix is meant to make explicit.

---

## فارسی

کلید: **ساخته‌شده** / **ناقص** / **فقط دموی HF** / **نیست**.

موتور مجوز کار، MOC با قفل PSSR، شاخص API 754، ممیزی ۱۴عنصری، حوادث، اضطراری، و hold تجهیز از PetroOps در Production هستند. LOTO هنوز پرچم تأیید است نه گواهی نقاط قفل. SIMOPS تطبیق رشته است نه نقشه. دستیار دانش کلیدواژه‌ای است نه RAG. امضا نام+هش است نه PKI. UI انگلیسی و SSO نیستند.
