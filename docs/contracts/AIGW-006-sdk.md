# AIGW-006 — SDK PEP (multi-application)

**Statut :** normatif. Cumulatif avec 001–005. Phase **P8**.

## Objet

Les applications consommatrices **demandent** generate/embed via un client mince. Elles n’appellent pas Ollama ni Gemini.

```text
PEP asks → gateway (SQL route + adapters) → PEP returns
```

## Garantit

- `generate` / `embed` / `health` seulement
- Auth : `X-API-Key` (clé **gateway**, pas Gemini)
- Timeout / hôte down / JSON invalide → exception de **transport**, jamais un texte inventé
- 4xx/503 du contrat AIGW-005 → exception avec `error.code` + `correlationId`, jamais un lorem
- Pas de `*Engine` dans `sdk/`
- Pas de `aigw_route_resolve` dans le SDK (la route reste SQL côté moteur)

## Interdit

- Clé Gemini / URL Ollama dans le SDK
- Second moteur (FastAPI, client Google, hash embeddings)
- Noms d’apps hardcodés comme politique
- SDK frontend avec secret

## Hors P8

- SDK TypeScript/Go
- Branchement ssk-book / elimu-pass / normind / coach (P9+)
