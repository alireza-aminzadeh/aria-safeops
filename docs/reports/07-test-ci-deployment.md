# Report 07 — Tests, CI, and deployment

**Language:** English first, then فارسی.  
**Pipeline file:** `.github/workflows/ci-cd.yml`

## English

### PHPUnit inventory (13 files)

| Test class | Protects |
|---|---|
| `PermitWorkflowTest` | PTW places/transitions |
| `PermitActivationPolicyTest` | Activation rules (gas test / isolation / hold) |
| `PermitVoterTest` | ABAC on permits |
| `MocWorkflowTest` | MOC + PSSR lock |
| `MocVoterTest` | MOC authorization |
| `IncidentStatusMachineTest` | Incident statuses |
| `SafetyKpiCalculatorTest` | LTIFR / TRIR math |
| `PsmAuditScoreCalculatorTest` | Audit scoring |
| `EffluentCompliancePolicyTest` | Effluent limits |
| `EquipmentHoldCheckerTest` | PetroOps hold blocks PTW/MOC |
| `ElectronicSignatureHasherTest` | Signature hash |
| `AuditLoggerHashChainTest` | Tamper-evident chain |
| `Phase2ServicesTest` | Phase 2 service wiring smoke |

CI command: `php bin/phpunit --testdox` against `timescale/timescaledb:2.17.2-pg16`. Frontend: Node 22, `npm ci`, `npm run build`.

### Pipeline

```
push/PR to main
  test (PHP 8.4 + Timescale + Node 22)
    → build-and-push (main only) → ghcr.io/alireza-aminzadeh/aria-safeops-app
      → deploy (environment: production) SSH to VPS
         git reset --hard origin/main
         IMAGE_TAG=<sha>
         docker compose pull && up -d
         nginx restart
```

Secrets: `SAFEOPS_SSH_HOST`, `SAFEOPS_SSH_USER`, `SAFEOPS_SSH_KEY`. GHCR login uses `GITHUB_TOKEN`.

### Production compose (data plane not published)

| Service | Image family | Host ports |
|---|---|---|
| nginx | `nginx:1.27-alpine` | 80, 443 |
| app | GHCR app image | none (proxy only) |
| postgres | `timescale/timescaledb:2.17.2-pg16` | none |
| redis | `redis:7.4-alpine` | none |
| mercure | Mercure hub | internal; public path `/.well-known/mercure` via nginx |

Host sizing assumed in compose comments: 2 vCPU / ~3.7 GiB. Memory limits are set per service.

### Backup / observe (current)

| Practice | Status |
|---|---|
| `pg_dump` daily | Documented; off-box copy is an ops task |
| Prometheus / Grafana | Not on this VPS |
| Sentry | Not deployed |

---

## فارسی

سیزده تست PHPUnit گردش‌کار، Voter، KPI، hold تجهیز و زنجیرهٔ ممیزی را قفل می‌کنند. Actions روی `main` تست، بیلد GHCR و استقرار SSH را اجرا می‌کند. در Production فقط ۸۰/۴۴۳ روی میزبان باز است. Postgres و Redis پورت عمومی ندارند. پایش هنوز `docker stats` / `pg_dump` است نه استک Observability کامل.
