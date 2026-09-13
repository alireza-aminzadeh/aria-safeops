# Report 05 — Gap analysis

**Language:** English first, then فارسی.  
**Method:** strategy intent vs code in this repository (September 2026). RAG/LLM is called out explicitly and is **not** counted as done.

## English

### Summary

The **workflow core is shippable**. The **intelligence and enterprise-integration layer** described in the Aria oil-and-gas strategy is not. That is the same conclusion as the internal product audit: buyers get CoW + PSM registers; they do not yet get cited RAG, spatial SIMOPS, or CMMS connectors.

### Gaps by theme

| Theme | Today | Gap | Priority if the goal is sales |
|---|---|---|---|
| Knowledge assistant | Keyword pack, optional HTTP port | FastAPI + Qdrant + embeddings + abstention; PetroSafe RAG in process | **P1** |
| PTW intelligence | Manual JSA field; regex risk | PermitGuard on free text; draft from JSA history | **P1** |
| SIMOPS | String tag/area | MapLibre/Konva plot plan, permit-type distance rules | **P2** |
| LOTO | Boolean confirmed | Isolation certificate, lock points, dual-verify, QR on lock | **P2** |
| Vision | Simulator + camera registry | GPU PPE model, alert linked to active permit in that area | P3 |
| Incidents | RCA/CAPA + KPI | Statutory filing, lesson-learned RAG, link to PetroOps anomaly | P3 |
| Contractors | Cert + training | Mandatory training matrix gated on issue; tenant isolation | P2 |
| Signature | Name + hash | Qualified e-sign / PKI | P2 |
| PWA | Offline shell + queue | Full offline gas-test/permit + conflict sync | P3 |
| Notifications | Optional email / log | SMS, push | P3 |
| Identity | Local JWT | Keycloak / AD / MFA | **P2** (on-prem plants) |
| Documents | None | Engineering/MSDS vault, Persian OCR | P3 |
| Observability | `pg_dump`, docker stats | Prometheus/Grafana, OTel, off-box backup | P3 |
| Air-gap | Images from GHCR | Offline OCI + model weights, no Hub at site | P2 for refinery install |

### What we will not pretend

- Vision events in production are **simulator-grade** unless a real ingest URL is configured.
- Knowledge answers are **keyword retrieval**, not semantic RAG.
- Equipment holds work only if PetroOps outbox is enabled (`SAFEOPS_ENABLED` on the sister system).
- Seed passwords in local docs are **demo** credentials.

---

## فارسی

هستهٔ گردش‌کار قابل استقرار است؛ لایهٔ هوشمندی و یکپارچگی سازمانی سند استراتژی هنوز نیست. اولویت فروش: وصل RAG/PermitGuard، سپس SIMOPS روی نقشه و LOTO چندنقطه‌ای، سپس SSO و بستهٔ air-gap. Vision شبیه‌ساز است مگر ingest واقعی ست شود. Hold تجهیز فقط با فعال بودن outbox پترواپس کار می‌کند.
