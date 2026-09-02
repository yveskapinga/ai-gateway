# AIGW-003 — Architecture

**Statut :** normatif.

```text
HTTP  →  ControlPlane (controllers minces)
      →  Application (validation + RoutineExecutor + orchestration)
      →  Domain (types, invariants)
      →  SQL routines aigw_*  (routes, catalogue, audit)
      →  Infrastructure adapters (Ollama HTTP, Gemini HTTP)
```

## Couches

| Couche | Peut | Ne peut pas |
|---|---|---|
| Domain | Types, erreurs métier, règles family/failover | Symfony, PDO, Google, Ollama, noms d’apps |
| Application | Appeler routines nommées, orchestrer adapters via ports | SQL inline, SDK vendor |
| Infrastructure | PDO, HttpClient, lire `getenv(secret_env_name)` | Décider la route (c’est SQL) |
| ControlPlane | HTTP, auth clé, listener d’exceptions | SQL, appels modèle |

## Ports

- `RoutineExecutor::call(string $routine, array $params): mixed` — whitelist de noms `aigw_*`
- `GenerativeModelClient` — generate (implémenté par adapters)
- `EmbeddingModelClient` — embed (implémenté par adapters)

## SQL (NOW)

Tables : `gateway_run` (P1), `gateway_application`, `gateway_provider`, `gateway_model` (P2), `gateway_route` (P3).

Routines : `aigw_health_ping`, `aigw_run_append`, `aigw_application_*`, `aigw_provider_list`, `aigw_model_*`, `aigw_route_resolve`.
