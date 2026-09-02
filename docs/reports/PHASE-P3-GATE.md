# PHASE P3 GATE

**Date:** 2026-09-02  
**Status:** PASS

| Check | Result |
|---|---|
| `generate/low` | primary ollama `llama3.2:3b`, fallback gemini flash-lite **depuis les données** |
| Upsert SQL/routine | change la route sans constante modèle dans `RouteService.php` |
| Tâche inconnue | `AIGW.ROUTE.UNKNOWN` |
| Seed `003_routes.sql` | low → ollama puis gemini |
| sql-first | OK |

**Commandes:**

```text
bash scripts/check-sql-first-boundaries.sh
cd control-plane && php vendor/bin/phpunit --group P3
cd control-plane && php vendor/bin/phpunit --group P0 --group P1 --group P2 --group P3
# OK (26 tests, 89 assertions)
```

**Suite :** enchaîner P4 (`POST /v1/generate` + adapter Ollama). Pas Gemini, pas embed.
