-- =============================================================================
-- P2 — Catalogue (applications, providers, modèles). Pas encore de routes.
--
-- Pourquoi : AIGW-002 INV-004 / INV-009. La politique d’inférence est des
-- données. secret_env_name = nom d’environnement, jamais la clé.
--
-- Ne fait pas : aigw_route_resolve (P3) ; HTTP generate (P4).
--
-- Debug : SELECT code, secret_env_name FROM gateway_provider;
-- =============================================================================

CREATE TABLE IF NOT EXISTS gateway_application (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    code        TEXT NOT NULL UNIQUE,
    status      TEXT NOT NULL DEFAULT 'active'
                CHECK (status IN ('active', 'suspended')),
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS gateway_provider (
    id               UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    code             TEXT NOT NULL UNIQUE,
    kind             TEXT NOT NULL,
    endpoint         TEXT NOT NULL,
    secret_env_name  TEXT,
    created_at       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT gateway_provider_secret_is_env_name CHECK (
        secret_env_name IS NULL
        OR secret_env_name ~ '^[A-Z][A-Z0-9_]{2,63}$'
    )
);

CREATE TABLE IF NOT EXISTS gateway_model (
    id             UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    provider_id    UUID NOT NULL REFERENCES gateway_provider(id),
    code           TEXT NOT NULL,
    capability     TEXT NOT NULL CHECK (capability IN ('generate', 'embed')),
    family         TEXT,
    dimension      INTEGER,
    relative_cost  NUMERIC NOT NULL DEFAULT 1,
    created_at     TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (provider_id, code, capability)
);
