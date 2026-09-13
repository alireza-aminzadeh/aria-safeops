# Report 06 — Hugging Face alignment

**Language:** English first, then فارسی.

SafeOps production does **not** call Hugging Face. The Hub projects are technical proofs for the same product family.

## English

### Mapping

| Hub project | Hub URL | SafeOps method | Fit |
|---|---|---|---|
| PetroSafe RAG | [Space](https://huggingface.co/spaces/alirezaaminzadeh/petrosafe-rag-fa) | `askKnowledgeBase` | Best fit: FA/EN question, citation, abstention |
| PermitGuard | [Space](https://huggingface.co/spaces/alirezaaminzadeh/permitguard-ptw-risk-classifier) | `classifyRiskText` | Good for free-text permit/JSA; not a plot-plan SIMOPS engine |

Related Hub artefacts: dataset `petrosafe-rag-corpus-fa`, retriever `petrosafe-rag-retriever`, PermitGuard dataset/model on the same account (`alirezaaminzadeh`).

### Why `AI_GATEWAY_URL=https://huggingface.co/spaces/...` is not enough

| SafeOps HTTP adapter expects | Gradio Space actually returns |
|---|---|
| `POST /v1/knowledge-query`, `POST /v1/classify-risk` | Gradio functions (`ask`, `run_risk`) without those paths |
| JSON `{ text, citations }` | HTML / Plotly / DataFrame |
| ~8 s timeout | Cold start on `cpu-basic` often longer |

A dedicated `HuggingFaceSpaceAdapter` (Gradio client, 30–60 s timeout, `HF_TOKEN`) is a **demo** path only. Plant PTW text must not leave the VPS. The documented product path is: download weights, serve on-prem or on a third FastAPI host, keep `HttpAiGatewayAdapter` pointed at that private `/v1`.

### Honesty in the UI

The knowledge page must keep showing **unavailable** when the HTTP gateway is unset. That is preferred over a silent Hub call that times out mid-permit.

---

## فارسی

Production این سامانه Hugging Face را صدا نمی‌زند. PetroSafe RAG بهترین تطابق با `askKnowledgeBase` است و PermitGuard با `classifyRiskText`. گذاشتن آدرس Space روی `AI_GATEWAY_URL` کافی نیست: قرارداد JSON فرق دارد و Space بعد از خواب ممکن است بیشتر از ۸ ثانیه بیدار شود. برای پالایشگاه باید وزن مدل locally سرو شود. برای دموی فروش، آداپتور جدا با تایم‌اوت بالا قابل دفاع است به شرط افشای این‌که استنتاج روی Hub است.
