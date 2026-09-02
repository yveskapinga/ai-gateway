# PHASE P2 GATE

**Date:** 2026-09-02  
**Status:** PASS

| Check | Result |
|---|---|
| `aigw_application_create/get` | APP_DEMO via routines nommées |
| `aigw_provider_create` | `secret_env_name=GEMINI_API_KEY` |
| Clé refusée | valeur type AIza… → `AIGW.VALIDATION.FAILED` |
| Seed SQL | nom d’env, pas de clé |
| sql-first | OK |

**Commandes:**

```text
bash scripts/check-sql-first-boundaries.sh
cd control-plane && php vendor/bin/phpunit --group P2
cd control-plane && php vendor/bin/phpunit --group P0 --group P1 --group P2
# OK (22 tests, 71 assertions)
```

**Suite :** enchaîner P3 (`gateway_route` + `aigw_route_resolve`). Pas d’appel modèle.
