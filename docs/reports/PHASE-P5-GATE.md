# PHASE P5 GATE

**Date:** 2026-09-02  
**Status:** PASS

| Check | Result |
|---|---|
| Primary Ollama down | fallback Gemini, `provider=gemini`, `gateway_run.status=failover` |
| Les deux down | HTTP 503, `AIGW.INFERENCE.UNAVAILABLE`, `correlationId` |
| Gemini 429 | bascule Ollama (route SQL swapped) |
| Fake HTTP | pas de clé Gemini dans Git |
| sql-first | OK |
| Smoke Ollama réel | N/A — fake only |

**Commandes:**

```text
bash scripts/check-sql-first-boundaries.sh
cd control-plane && php vendor/bin/phpunit --group P5
cd control-plane && php vendor/bin/phpunit --group P0 --group P1 --group P2 --group P3 --group P4 --group P5
# OK (32 tests, 126 assertions)
```

**Suite :** enchaîner P6 (`POST /v1/embed` + `family` obligatoire).
