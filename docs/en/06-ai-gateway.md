# 6) AI gateway — Aria SafeOps

English technical manual. Persian original: [`../06-ai-gateway-placeholder.md`](../06-ai-gateway-placeholder.md).

## 6.1 Decision

Heavy RAG/LLM (embeddings + Qdrant + generator) is **not** hosted on the HSE VPS. The product still has a stable `AiGatewayInterface` so enabling a real service is a configuration switch.

The running code is **not** a single stub anymore. `AiGatewayFactory` selects one of three adapters.

## 6.2 Port

```php
interface AiGatewayInterface
{
    public function isEnabled(): bool;
    public function askKnowledgeBase(string $query, array $context = []): AiAnswer;
    public function classifyRiskText(string $text, array $context = []): AiRiskClassification;
}
```

`AiAnswer` is either `available` with `text` + `citations`, or `unavailable` with a reason. The UI must show that reason. Never invent a clause number.

## 6.3 Three levels

| Level | When | Behaviour |
|---|---|---|
| Off | Local `.env` default / explicit disable | `NullAiGatewayAdapter` |
| On-prem pack | `.env.example` production-style default | Keyword retrieval over a small HSE pack + regex risk class |
| HTTP | `AI_GATEWAY_URL` set | `HttpAiGatewayAdapter` → `POST /v1/knowledge-query`, `POST /v1/classify-risk` |

The on-prem pack is **real code** and **not** RAG: no embeddings, no generative model, no semantic search. Project status reports must not tick “RAG/LLM done” because of this pack.

## 6.4 Hugging Face

PetroSafe RAG and PermitGuard Spaces implement the *product idea* of those two methods. They are not drop-in replacements for the HTTP contract. See [`../reports/06-huggingface-alignment.md`](../reports/06-huggingface-alignment.md).

## 6.5 Logging

Calls are stored in `ai_query_log` for later audit (question hash / response metadata — keep plant-identifying text out of shared logs when the HTTP backend is external).
