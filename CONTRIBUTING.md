# Contributing — Aria SafeOps

**Language:** [English](#english) · [فارسی](#persian)

<a id="english"></a>

## English

This is a **private proprietary** repository for Aria AI. External pull requests are not solicited. Internal changes should stay reviewable and must not weaken the security rules in `docker-compose.yml` or `SECURITY.md`.

### Layout

| Path | Role |
|---|---|
| `src/Domain/` | Bounded contexts (Permit, MOC, Incident, PSM, Emergency, …) |
| `src/Infrastructure/` | AI gateway, audit, notifications |
| `config/packages/workflow.yaml` | PTW and MOC state machines |
| `frontend/` | React 19 SPA |
| `migrations/` | Doctrine migrations |
| `tests/` | PHPUnit |
| `docs/` | Persian manuals; `docs/en/` English; `docs/reports/` bilingual reports |

### Rules of thumb

1. Domain policy (gas test, LOTO, SIMOPS, equipment hold) lives in PHP, not only in the React form.
2. Do not expose Postgres/Redis on `0.0.0.0`. Local debug binds stay `127.0.0.1` in override files.
3. Do not call public Hugging Face Spaces from the production request path.
4. Keep AI honest: if the gateway is off or HTTP is unset, the UI must say unavailable — never invent a citation.
5. Documentation is bilingual: update **English first**, then the Persian section / `docs/*.md`.

### Local checks before push

```powershell
php bin/phpunit --testdox
cd frontend
npm run build
```

CI on `main` runs PHP lint, PHPUnit against Timescale, frontend build, image push, and deploy.

---

<a id="persian"></a>

## فارسی

این ریپو خصوصی و proprietary است. سیاست دامنه (گاز‌تست، LOTO، SIMOPS، hold تجهیز) باید در PHP باشد نه فقط در فرم React. Postgres/Redis را به `0.0.0.0` باز نکنید. Space عمومی Hugging Face را در مسیر Production صدا نزنید. اگر درگاه AI خاموش است UI باید «در دسترس نیست» بگوید. مستندات را **اول انگلیسی، بعد فارسی** به‌روز کنید.
