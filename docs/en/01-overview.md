# 1) Product overview — Aria SafeOps (ImenKar)

English technical manual. Persian original: [`../01-overview.md`](../01-overview.md).

## 1.1 Purpose

Aria SafeOps is the **process-safety, electronic permit-to-work (Control of Work), and HSE knowledge** system for oil, gas, and petrochemical operating units.

This repository is a **public demo**. Visit **[https://aria-ai.ir](https://aria-ai.ir)** to view the demos and work with them.

Data collection, process analysis, and implementation of this project were carried out in **2024, 2025, and 2026**.

| | |
|---|---|
| Kind | Public demo |
| Demo entry | https://aria-ai.ir |
| Demo URL | https://hse.aria-ai.ir |
| GitHub | https://github.com/alireza-aminzadeh/aria-safeops |
| Role in the family | Sister of [Aria PetroOps](https://github.com/alireza-aminzadeh/aria-petroops) |
| Project period | 2024, 2025, and 2026 — data collection, process analysis, and implementation |

## 1.2 Why Symfony (not NestJS)

PetroOps is NestJS because it streams OT telemetry. SafeOps is **approval-centric**:

- Symfony Workflow is a first-class declarative state machine for PTW and MOC.
- No OPC-UA/MQTT requirement; PHP’s weak OT ecosystem does not matter here.
- Headless: Symfony exposes API Platform JSON only; the UI is React 19 + TypeScript, same family as PetroOps.

## 1.3 Target users

| Role | Primary job in this product |
|---|---|
| Permit issuer | Raise hot/cold/confined/height/excavation/electrical permits |
| Performing authority / contractor | Execute the live permit, log gas tests, close out |
| HSE manager | Approve/reject, incidents, LTIFR/TRIR, PSM audit |
| Operations supervisor | MOC, PSSR, shift handover |
| Contractor qualification | Training matrix, certificate expiry |

## 1.4 Modules vs original plan

| # | Module | Phase originally | In production now |
|---|---|---|---|
| 1 | Control of Work / PTW | 1 | Yes (LOTO/SIMOPS still partial vs industrial CoW) |
| 2 | PSM (MOC, HAZOP/LOPA/Bowtie, API 754, audit) | 2 | Yes |
| 3 | Incidents / observations | 1 (simple) | Yes + CAPA + rates |
| 4 | HSE Vision | 2 | Camera registry + simulator |
| 5 | Knowledge assistant | Reserved | Keyword pack; HTTP RAG port unused |
| 6 | Shift / logbook | 2 | Yes |
| 7 | Contractors | 1 simple / 2 full | Simple + expiry; no tenant column |
| 8 | Emergency and environment | 3 | **Brought forward into Phase 2** |

## 1.5 Current phase scope

In scope and deployed:

- PTW/MOC engines with guards, gas test, LOTO flag, SIMOPS string match
- Equipment hold from PetroOps on create and on activate/resume/implementation
- Incidents, PSM register, PSM audit, shift, emergency/effluent, Vision simulator, Mercure
- Auth, RBAC, Voters, hash-chain audit, login rate limit
- Three-level AI factory (off / on-prem keywords / HTTP)

Out of scope by design on this VPS:

- Real GPU Vision
- Vector RAG / LLM inference
- Writing to any DCS/PLC (SafeOps never would)

## 1.6 Link to PetroOps

- Same `equipment_tag` string as PetroOps ISA-95 tags.
- Open anomaly → hold → cannot issue or activate a permit / cannot move MOC into implementation on that tag.

## 1.7 Login

Shared bootstrap username with PetroOps: `alireza`. Local seed password is `alireza`. Production password is **not** documented in git; set `SEED_ALIREZA_PASSWORD` on the server and rotate it.
