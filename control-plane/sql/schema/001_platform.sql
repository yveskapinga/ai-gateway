-- =============================================================================
-- P1 — Audit d’exécution (pas le catalogue, pas les routes).
--
-- Pourquoi : AIGW-002 INV-008 / INV-010. gateway_run est un journal, pas la
-- vérité du modèle. Le texte d’entrée n’est PAS une colonne (fail-secure P7 dès P1).
--
-- Garantit : append-only (pas de UPDATE/DELETE métier ici).
--
-- Ne fait pas : catalogue providers (P2) ; routes (P3) ; inférence (P4).
--
-- Debug : SELECT * FROM gateway_run ORDER BY recorded_at DESC LIMIT 20;
-- =============================================================================

CREATE TABLE IF NOT EXISTS gateway_run (
    id                 UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    application_code   TEXT,
    task               TEXT,
    complexity         TEXT,
    route_id           UUID,
    provider_code      TEXT,
    model_code         TEXT,
    status             TEXT NOT NULL,
    latency_ms         INTEGER,
    error_code         TEXT,
    correlation_id     TEXT NOT NULL,
    recorded_at        TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS gateway_run_correlation_idx
    ON gateway_run (correlation_id);

CREATE INDEX IF NOT EXISTS gateway_run_recorded_idx
    ON gateway_run (recorded_at DESC);
