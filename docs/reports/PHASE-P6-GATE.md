# PHASE P6 GATE

**Date:** 2026-09-02  
**Status:** PASS

| Check | Result |
|---|---|
| POST /v1/embed `family=gemini-768` | 200, dimension 768, 2 vecteurs |
| Famille inconnue `minilm-384` | 422 `AIGW.EMBED.UNKNOWN_FAMILY` |
| `family` absente | 422 validation |
| GenerateService | n’appelle pas embed |
| sql-first | OK |

**Commandes:**

```text
bash scripts/check-sql-first-boundaries.sh
cd control-plane && php vendor/bin/phpunit --group P6
# cumul P0–P6 : OK (36 tests, 143 assertions)
```

**Suite :** enchaîner P7 (durcissement). Pas de SDK, pas d’apps.
