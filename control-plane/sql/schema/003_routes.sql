-- =============================================================================
-- P3 — Routes (task + complexity → primary + fallback). Pas d’appel modèle.
--
-- Pourquoi : AIGW-002 INV-004. Un UPDATE SQL change la politique sans PHP.
--
-- Debug : SELECT * FROM gateway_route;
-- =============================================================================

CREATE TABLE IF NOT EXISTS gateway_route (
    id                 UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    task               TEXT NOT NULL,
    complexity         TEXT NOT NULL CHECK (complexity IN ('low', 'medium', 'high')),
    primary_model_id   UUID NOT NULL REFERENCES gateway_model(id),
    fallback_model_id  UUID REFERENCES gateway_model(id),
    UNIQUE (task, complexity)
);
