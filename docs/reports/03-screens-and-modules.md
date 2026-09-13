# Report 03 — Screens and modules

**Language:** English first, then فارسی.  
**Source:** `frontend/src/App.tsx`, `src/Domain/*`

## English

### SPA information architecture

All routes except `/login` require a JWT.

| Nav label (FA in UI) | Path | Primary API families |
|---|---|---|
| Login | `/login` | `POST /api/login` |
| Dashboard | `/` | `/api/dashboard`, `/api/kpis/api754`, `/api/safety-period-metrics` |
| Permits | `/permits`, `/permits/:id` | `/api/permits`, transitions, gas tests |
| MOC | `/moc`, `/moc/:id` | `/api/moc-requests` |
| PSM / HAZOP | `/psm` | HAZOP/LOPA/Bowtie + KPI cards |
| PSM audit | `/psm-audit` | `/api/psm-audits` |
| Shift | `/shift` | Handover, logbook, toolbox talk |
| Incidents | `/incidents`, `/incidents/:id` | Incidents + CAPA |
| Emergency & environment | `/emergency` | ERP, drills, effluent |
| Vision | `/vision` | Cameras + events |
| Contractors | `/contractors` | Companies, certs, training |
| Knowledge | `/ai` | `/api/ai/*` |
| Equipment (deep link) | `/equipment/:tag` | Holds for that tag |

UI language is **Persian (RTL)**. English manuals in this repo do not change the running SPA.

### Domain map (PHP)

| Namespace | Entities / policies (selected) |
|---|---|
| `Domain\Permit` | `Permit`, `PermitType`, `GasTestReading`, `GasTestPolicy`, `IsolationPolicy`, `PermitConflictChecker`, `PermitVoter` |
| `Domain\Moc` | `MocRequest`, `MocVoter`, workflow subscriber |
| `Domain\Psm` | `HazopRegisterItem`, `LopaScenario`, `BowtieBarrier`, `PsmAudit`, `PsmAuditFinding`, `PsmElements`, calculators |
| `Domain\Incident` | `Incident`, `CapaAction`, `SafetyPeriodMetric`, `SafetyKpiCalculator` |
| `Domain\Emergency` | `ErpPlan`, `EmergencyDrill`, `EffluentReading`, `EffluentCompliancePolicy` |
| `Domain\Shift` | `ShiftHandover`, `LogbookEntry`, `ToolboxTalk` |
| `Domain\Vision` | `VisionCamera`, `VisionEvent` |
| `Domain\Contractor` | `Contractor`, `ContractorCertification`, `ContractorTrainingRecord` |
| `Domain\Integration` | `EquipmentHold`, `EquipmentHoldChecker` |
| `Domain\Shared` | `Tenant`, `User`, `AuditLogEntry`, `ElectronicSignature`, `AiQueryLog` |

### Permit types seeded

| Code | English | Typical extra controls |
|---|---|---|
| `hot_work` | Hot work | Gas test + isolation |
| `cold_work` | Cold work | | 
| `confined_space` | Confined space | Gas test |
| `working_at_height` | Working at height | |
| `excavation` | Excavation | |
| `electrical` | Electrical | Isolation |

---

## فارسی

همهٔ مسیرها جز ورود به JWT نیاز دارند. برچسب ناوبری در UI فارسی است. دامنهٔ PHP مطابق جدول انگلیسی است. شش نوع مجوز بذر می‌شوند. صفحهٔ تجهیز از روی تگ، holdهای PetroOps را نشان می‌دهد.
