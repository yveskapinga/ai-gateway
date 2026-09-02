# PHASE P7 GATE

**Date:** 2026-09-02  
**Status:** PASS

| Check | Result |
|---|---|
| Noms d’apps dans `src/` | aucun (`ssk-book` / `elimu` / `normind` / `coach`) |
| Secrets | pas de clé type AIza/sk- dans src/sql ; `.env` gitignoré |
| `gateway_run` | toujours sans colonne prompt |
| Compose | Postgres seulement, pas d’Ollama publié |
| Health Ollama down | HTTP 200 `degraded`, `checks.ollama=down`, DB `ok` |
| Clé API vide | 401 fail-secure |
| sql-first | OK |

**Commandes:**

```text
bash scripts/check-sql-first-boundaries.sh
cd control-plane && php vendor/bin/phpunit --group P7
cd control-plane && php vendor/bin/phpunit --group P0 --group P1 --group P2 --group P3 --group P4 --group P5 --group P6 --group P7
# OK (43 tests, 160 assertions)
```

**Suite :** STOP vague moteur. P7 PASS = fin de construction. Ne pas commencer P8 (SDK PEP) ni brancher ssk-book / elimu-pass / normind / coach-app.
