# AIGW-004 — Stack

**Statut :** normatif. NOW seulement.

| Élément | Choix | Interdit NOW |
|---|---|---|
| Langage hôte | PHP 8.3 | Python FastAPI comme cœur |
| Framework | Symfony 7 (Kernel présent) ; FrontController mince comme authz tant que les routes sont peu nombreuses | Voters Symfony comme politique |
| Base | PostgreSQL 16 | SQLite production, ORM d’agrégats |
| Inférence locale | Ollama `127.0.0.1:11434` | Bind public, nginx `/ollama` |
| Inférence cloud | Gemini generate + embed | Clé dans Git / frontend / table |
| Tests | PHPUnit 11, `@group Pn` | Clé Gemini dans CI |
| Secrets | `.env` gitignoré ; SQL stocke le **nom** d’env | Valeur de clé en SQL |

Auth service-to-service : header `X-API-Key` (pas un JWT utilisateur). Health peut rester public.
