# PHASE P4 GATE

**Date:** 2026-09-02  
**Status:** PASS

| Check | Result |
|---|---|
| POST /v1/generate (fake HTTP) | 200, `provider=ollama`, `model=llama3.2:3b` |
| Timeout simulé | 503 `AIGW.INFERENCE.UNAVAILABLE`, pas de champ `text` inventé |
| Domain | pas de client Ollama / `:11434` |
| Audit | `gateway_run` sans prompt |
| sql-first | OK |
| Smoke Ollama réel | N/A — fake only |

**Commandes:**

```text
bash scripts/check-sql-first-boundaries.sh
cd control-plane && php vendor/bin/phpunit --group P4
cd control-plane && php vendor/bin/phpunit --group P0 --group P1 --group P2 --group P3 --group P4
# OK (29 tests, 110 assertions)
```

**Suite :** enchaîner P5 (adapter Gemini + failover de la même route SQL).
