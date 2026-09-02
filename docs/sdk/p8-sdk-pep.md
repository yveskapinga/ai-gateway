# P8 — SDK PEP

**Gate:** [reports/PHASE-P8-GATE.md](../reports/PHASE-P8-GATE.md)

Le SDK PHP (`sdk/php`) est un **PEP d’inférence** : il pose la question au control plane, il n’est pas une autorité.

- Transport down ≠ complétion
- `AIGW.INFERENCE.UNAVAILABLE` (HTTP 503) ≠ timeout réseau, mais **aucun** des deux n’invente de texte
- `family` obligatoire pour embed, inchangé (AIGW-005)
