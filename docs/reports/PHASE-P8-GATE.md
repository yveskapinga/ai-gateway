# PHASE P8 GATE

**Date:** 2026-09-02  
**Status:** PASS

| Check | Result |
|---|---|
| `GatewayClient::generate` | PEP asks `/v1/generate` ; `provider=ollama` réel |
| `embed` + `family` | dimension 768 depuis le gateway |
| Famille inconnue | `GatewayClientException` `AIGW.EMBED.UNKNOWN_FAMILY` (pas un vecteur inventé) |
| 503 inférence | `AIGW.INFERENCE.UNAVAILABLE` + `correlationId` |
| Timeout transport | `TransportException` `AIGW.PEP.TIMEOUT`, pas de `text` |
| SDK | pas de `*Engine`, pas de `GEMINI_API_KEY`, pas de `:11434`, pas de routine de route |
| Contrat | `docs/contracts/AIGW-006-sdk.md` |
| sql-first | OK |

**Commandes:**

```text
bash scripts/check-sql-first-boundaries.sh
cd control-plane && php vendor/bin/phpunit --group P8
cd control-plane && php vendor/bin/phpunit --group P0 --group P1 --group P2 --group P3 --group P4 --group P5 --group P6 --group P7 --group P8
# OK (50 tests, 228 assertions)
```

**Suite :** STOP. P9+ (elimu-pass, ssk-book, …) = autre demande, un gate par app.
