# Agent instructions — AI Gateway

Règle de travail : [`docs/governance/MASTER-INSTRUCTION.md`](docs/governance/MASTER-INSTRUCTION.md).  
Feuille de route unique : [`docs/governance/IMPLEMENTATION-ROADMAP.md`](docs/governance/IMPLEMENTATION-ROADMAP.md).

Les contrats `docs/contracts/AIGW-xxx` sont la source de vérité. Ne pas inventer une architecture parallèle.

## Avant toute modification de code

Lire les contrats disponibles. En cas de contradiction : fichier `CONTRACT_CONFLICT`, attendre.

## NOW / NEXT / LATER

| NOW | NEXT | LATER |
|---|---|---|
| P8 SDK PEP | — | P9+ consommateurs, un gate par app |

## Boucle de phases

```text
implémenter UNIQUEMENT la phase N
→ tests @group Pn + check-sql-first
→ docs/reports/PHASE-PN-GATE.md = PASS
→ enchaîner IMMÉDIATEMENT N+1
```

P8 PASS = SDK livré. Ne pas commencer P9 (apps) sans demande.

## Interdit

- Second moteur d’inférence dans une app « en attendant »
- SQL inline dans les contrôleurs / Domain
- Clé Gemini / Ollama en Git, en frontend, ou en base
- Noms d’apps consommatrices (`ssk-book`, `elimu`, `normind`, `coach`) dans `control-plane/src`
- Mélanger des familles d’embeddings
- Inventer du texte si les deux voies d’une route sont down
