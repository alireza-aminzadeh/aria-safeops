# Report 08 — Sister-system integration (PetroOps)

**Language:** English first, then فارسی.  
**Peer repo:** https://github.com/alireza-aminzadeh/aria-petroops

## English

### Contract

| Direction | Payload idea | SafeOps effect |
|---|---|---|
| PetroOps → SafeOps | Open / updated / closed anomaly on `equipment_tag` | Upsert `EquipmentHold` |
| SafeOps create permit/MOC | `TenantAwarePersistProcessor` | Reject if hold is open on that tag |
| SafeOps activate / resume / enter implementation | Workflow subscribers | Same checker so a hold that opens *after* issue still blocks work |

Receiver (SafeOps): `POST /api/integrations/petroops/anomalies` (see `PetroopsIntegrationController`).

PetroOps sender: outbox `IntegrationDelivery` + `SAFEOPS_ENABLED` (default **false** until explicitly turned on).

### What is not integrated yet

| Link in the strategy | Status |
|---|---|
| MOC ↔ ISA-95 asset register (ids, not just tag string) | Missing |
| Incident ↔ anomaly event | Missing |
| Permit volume → TAR schedule | Missing |
| Shared SSO (`id.aria-ai.ir`) | Missing |
| Shared UI kit | Missing (two SPAs) |

### Operational caution

Enabling the outbox without a reachable SafeOps URL will queue deliveries. Enabling holds in a demo tenant will **block** permit activation on that tag — that is the feature, not a bug.

---

## فارسی

پترواپس آنومالی باز را روی `equipment_tag` می‌فرستد؛ SafeOps hold می‌سازد و صدور/فعال‌سازی/ازسرگیری مجوز و ورود MOC به اجرا را بند می‌کند. Outbox سمت پترواپس پیش‌فرض خاموش است. هنوز پیوند شناسهٔ دارایی، حادثه↔آنومالی، حجم مجوز→TAR و SSO مشترک ساخته نشده است.
