# PHASE P1 GATE

**Date:** 2026-09-02  
**Status:** PASS

| Check | Result |
|---|---|
| `GET /v1/health` | 200, `AIGW.PLATFORM.HEALTH_OK`, `correlationId` |
| Health sans clé | 200 (public) |
| Sans `X-API-Key` sur POST | 401 `{ error.code = AIGW.AUTH.MISSING_KEY, correlationId }` |
| Listener interne | 500 `AIGW.COMMON.INTERNAL`, pas de secret dans le body |
| `gateway_run` / `aigw_run_append` | append sans colonne prompt |
| sql-first | OK |

**Commandes:**

```text
bash scripts/check-sql-first-boundaries.sh
# OK: frontières SQL-first respectées

cd control-plane && php vendor/bin/phpunit --group P1
cd control-plane && php vendor/bin/phpunit --group P0 --group P1
# OK (16 tests, 54 assertions)
```

**Suite :** enchaîner P2 (catalogue application / provider / model). Pas de routage complexité, pas d’inférence.
