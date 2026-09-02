# AIGW-001 — Contrat maître

**Statut :** normatif. Cumulatif avec 002–005.

## Objet

`ai-gateway` est **l’unique autorité d’inférence** (generate / embed) du workspace. Les applications consommatrices n’appellent pas Ollama ni Gemini directement.

## Portée NOW (P0–P7)

- Control plane PHP 8.3 / Symfony 7 + PostgreSQL 16
- API `GET /v1/health`, `POST /v1/generate`, `POST /v1/embed`
- Routes et catalogue en SQL
- Adapters Ollama (loopback) et Gemini

## Hors portée NOW

- SDK PEP (P8)
- Branchement ssk-book / elimu-pass / normind / coach-app (P9+)
- RAG, historique de conversation, quotas métier, fine-tune
- FastAPI interne, second moteur

## Priorité

Voir [`MASTER-INSTRUCTION.md`](../governance/MASTER-INSTRUCTION.md).

## Non-buts

- Ceci n’est pas un RAG.
- Ceci n’est pas un PDP d’autorisation (authz reste l’autorité d’accès).
- Ceci n’est pas un client frontend.
