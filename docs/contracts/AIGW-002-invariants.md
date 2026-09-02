# AIGW-002 — Invariants

**Statut :** normatif. Non négociables.

| Id | Invariant |
|---|---|
| INV-001 | Une seule autorité d’inférence. Interdit de recoller Ollama/Gemini dans une app. |
| INV-002 | Le moteur n’embarque pas le métier (prompts livre, crédits, règles ISO, journal). |
| INV-003 | `embed` exige une `family`. Mélanger Gemini-768 / MiniLM-384 / Ollama = erreur métier. |
| INV-004 | Providers et routes = données SQL, pas `if ($app === …)` dans le PHP. |
| INV-005 | Failover bidirectionnel : primary timeout / 429 / 5xx / JSON invalide → fallback de **la même** route. |
| INV-006 | Fail-secure : les deux voies down → `{ error.code }` + `correlationId`, HTTP 503, pas de texte inventé. |
| INV-007 | Clés et Ollama loopback : jamais frontend, jamais nginx public, jamais en clair en base. |
| INV-008 | Control plane ≠ inference. `gateway_run` est un audit, pas la vérité du modèle. |
| INV-009 | Secret en SQL = **nom d’environnement**, pas la valeur. |
| INV-010 | Prompt complet absent de `gateway_run` par défaut (P7). |
| INV-011 | Domain sans Google SDK, sans client Ollama, sans Symfony, sans SQL inline. |
| INV-012 | `control-plane/src` sans noms d’apps consommatrices. |

Codes d’erreur stables (préfixe `AIGW.`) :

- `AIGW.AUTH.MISSING_KEY` / `AIGW.AUTH.INVALID_KEY`
- `AIGW.VALIDATION.FAILED`
- `AIGW.ROUTE.UNKNOWN`
- `AIGW.EMBED.UNKNOWN_FAMILY`
- `AIGW.INFERENCE.UNAVAILABLE`
- `AIGW.COMMON.INTERNAL`
