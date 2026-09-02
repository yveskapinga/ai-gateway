# Instruction maîtresse — AI Gateway

Ce document est la **règle de travail** des agents et développeurs. Il ne remplace pas AIGW-001..N ; il dit comment les appliquer.

Priorité :

```text
SECURITY > CORRECTNESS > CONSISTENCY > EXPLAINABILITY
> GOVERNANCE > MAINTAINABILITY > PERFORMANCE > IMPLEMENTATION SPEED
```

## Règles opérationnelles

1. Les fichiers `AIGW-xxx` sont des contrats normatifs, pas de l’inspiration.
2. Lire **tous** les contrats disponibles avant de modifier le code.
3. Les contrats sont **cumulatifs**. AIGW-N ne replace pas AIGW-(N-1).
4. Contradiction réelle → fichier `CONTRACT_CONFLICT`, puis attendre validation. Ne pas corriger la sémantique en silence.
5. Distinguer **FOUNDATION / PHASE_N / TARGET** (NOW / NEXT / LATER). Ne pas implémenter toute la cible.
6. Une seule autorité d’inférence. Interdit de recoller Ollama/Gemini dans une app consommatrice.
7. Control plane (config, audit SQL) ≠ inference (HTTP vers un modèle). Une projection d’audit n’est pas la vérité du modèle.
8. Ports + adapters. Le Domain n’importe ni Google, ni client Ollama, ni Symfony, ni PDO.
9. Fail-secure : timeout / 429 / 5xx / JSON invalide sur **les deux** voies d’une route → erreur métier, jamais de texte inventé.
10. Familles d’embeddings incompatibles : `embed` exige `family`. Mélanger Gemini-768 / MiniLM-384 / Ollama est une erreur métier.
11. Providers et routes sont des **données SQL**, pas des `if ($app === …)`.
12. Pas de second moteur pour le même concept (pas de FastAPI interne « temporaire »).
13. Avant une phase : [`IMPLEMENTATION-ROADMAP.md`](IMPLEMENTATION-ROADMAP.md) (unique). Pas de second plan parallèle.
14. Après gate PASS de la phase N, enchaîner N+1 **sans** nouvelle confirmation. Gate FAIL → STOP.

Détail : plan de construction 2026-09-02. Calque authz `MASTER-INSTRUCTION`.
