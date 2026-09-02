# Feuille de route d’implémentation — unique

**Ne pas dupliquer** ce fichier ni inventer un troisième plan. Les gates `docs/reports/PHASE-P*` sont la preuve.

Règle d’itération (agent autonome jusqu’à P7) :

```text
phase = P0
tant que phase ≤ P7 :
  implémenter UNIQUEMENT N
  → phpunit @group Pn + groupes déjà PASS
  → bash scripts/check-sql-first-boundaries.sh
  → docs/reports/PHASE-PN-GATE.md
  FAIL → STOP
  PASS → enchaîner IMMÉDIATEMENT N+1
P7 PASS → STOP vague moteur (pas de P8)
```

Une phase = **un** palier. Interdit : deux phases en parallèle.

## Correspondance

| Preuve | Contrat | Statut |
|---|---|---|
| `PHASE-P0` | AIGW-001..005 + ADR SQL-first | **PASS** |
| `PHASE-P1` | hôte HTTP + audit | **PASS** |
| `PHASE-P2` | catalogue | **PASS** |
| `PHASE-P3` | routes | **PASS** |
| `PHASE-P4` | generate local | **PASS** |
| `PHASE-P5` | failover | **PASS** |
| `PHASE-P6` | embed | **PASS** |
| `PHASE-P7` | hardening | **PASS** — fin de vague moteur |
| P8 | SDK PEP | **PASS** — `sdk/php`, AIGW-006 |
| P9+ | apps | LATER — un gate par app |

## NOW / NEXT / LATER

| NOW | NEXT | LATER |
|---|---|---|
| P8 SDK | P9+ un gate par app | — |

## Commandes de gate

```text
bash scripts/check-sql-first-boundaries.sh
php vendor/bin/phpunit --group Pn
php vendor/bin/phpunit --group P0 --group P1   # cumul (jamais de virgules : PHPUnit 11)
```

CI : fake HTTP pour P4–P5. Pas de clé Gemini dans Git. Smoke Ollama réel = note host, pas critère CI.
