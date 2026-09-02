# ADR-001 — SQL-first Control Plane et contrat API unique

- **Status:** Accepted
- **Date:** 2026-09-02
- **Parents:** AIGW-002, AIGW-003, AIGW-004, AIGW-005 ; calque authz ADR-011

## Context

Les routes (quel modèle pour quelle complexité) et l’audit doivent être inspectables en SQL. Le Domain PHP valide le contrat d’entrée et orchestre failover ; il n’est pas la vérité des routes.

## Decision

1. **SQL-first pour la persistance :** tables, vues, fonctions dans `control-plane/sql/`. Les services PHP appellent des **routines nommées** (`aigw_*`) via `RoutineExecutor`. Pas d’ORM d’agrégats. Pas de SQL inline dans les controllers ni le Domain.
2. **Domain PHP pour les invariants** : types, `family` obligatoire, fail-secure, pas de texte inventé. Le Domain n’importe ni Doctrine, ni Symfony, ni client Google/Ollama.
3. **Adapters** : HTTP Ollama et Gemini seulement dans `Infrastructure`.
4. **Contrat HTTP unique** : erreurs `{ error: { code, message, details, correlationId } }`. Listener d’exceptions unique.
5. **Commentaires pédagogiques** obligatoires (contrat, invariant, non-buts).

## Consequences

- PDO/DBAL = transporteur d’appels de routines, pas un modèle objet.
- Un `UPDATE` sur `gateway_route` change la politique **sans** redéployer du PHP.
- Les agents n’avancent de phase que si le gate de tests est vert.
