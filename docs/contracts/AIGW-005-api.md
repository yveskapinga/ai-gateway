# AIGW-005 — Contrat API

**Statut :** normatif.

Prefix : `/v1`.

## Endpoints NOW

| Méthode | Chemin | Auth | Phase |
|---|---|---|---|
| `GET` | `/v1/health` | non | P1 |
| `POST` | `/v1/generate` | `X-API-Key` | P4 |
| `POST` | `/v1/embed` | `X-API-Key` | P6 |

## Erreurs (toutes)

```json
{
  "error": {
    "code": "AIGW.AUTH.MISSING_KEY",
    "message": "Clé d’API requise.",
    "details": null,
    "correlationId": "…"
  }
}
```

Header de réponse `X-Correlation-Id` toujours posé. Accepte `X-Correlation-Id` ou `X-Request-Id` en entrée.

## GET /v1/health — 200

```json
{
  "status": "ok",
  "code": "AIGW.PLATFORM.HEALTH_OK",
  "checks": { "database": "ok" },
  "correlationId": "…"
}
```

`status` : `ok` | `degraded` | `unavailable`. P7 : distinguer config OK vs Ollama down **sans** faire échouer health process si Ollama est down (degraded).

## POST /v1/generate

Entrée : `application`, `task`, `complexity` (`low`\|`medium`\|`high`), `prompt`, `jsonSchema?`.

Sortie 200 : `text` (ou objet JSON demandé), `provider`, `model`, `correlationId`.

Sans clé : 401 `AIGW.AUTH.MISSING_KEY`. Les deux voies down : 503 `AIGW.INFERENCE.UNAVAILABLE`.

## POST /v1/embed

Entrée : `application`, `family` (**obligatoire**), `texts[]`.

Sortie 200 : `vectors[]`, `family`, `dimension`, `provider`, `model`, `correlationId`.

Famille inconnue : 422 `AIGW.EMBED.UNKNOWN_FAMILY`.
