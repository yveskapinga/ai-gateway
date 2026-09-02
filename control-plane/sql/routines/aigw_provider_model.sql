CREATE OR REPLACE FUNCTION aigw_provider_create(
    p_code TEXT,
    p_kind TEXT,
    p_endpoint TEXT,
    p_secret_env_name TEXT
)
RETURNS JSONB
LANGUAGE plpgsql
AS $$
DECLARE
    r gateway_provider%ROWTYPE;
BEGIN
    IF p_code IS NULL OR length(trim(p_code)) = 0 THEN
        RAISE EXCEPTION 'AIGW.VALIDATION.FAILED: code requis' USING ERRCODE = '22023';
    END IF;
    INSERT INTO gateway_provider (code, kind, endpoint, secret_env_name)
    VALUES (trim(p_code), p_kind, p_endpoint, NULLIF(trim(COALESCE(p_secret_env_name, '')), ''))
    RETURNING * INTO r;
    RETURN jsonb_build_object(
        'id', r.id,
        'code', r.code,
        'kind', r.kind,
        'endpoint', r.endpoint,
        'secret_env_name', r.secret_env_name
    );
END;
$$;

CREATE OR REPLACE FUNCTION aigw_provider_list()
RETURNS JSONB
LANGUAGE sql
STABLE
AS $$
    SELECT COALESCE(jsonb_agg(jsonb_build_object(
        'id', id,
        'code', code,
        'kind', kind,
        'endpoint', endpoint,
        'secret_env_name', secret_env_name
    ) ORDER BY code), '[]'::jsonb)
    FROM gateway_provider;
$$;

CREATE OR REPLACE FUNCTION aigw_model_create(
    p_provider_code TEXT,
    p_code TEXT,
    p_capability TEXT,
    p_family TEXT,
    p_dimension INTEGER,
    p_relative_cost NUMERIC
)
RETURNS JSONB
LANGUAGE plpgsql
AS $$
DECLARE
    pid UUID;
    r gateway_model%ROWTYPE;
BEGIN
    SELECT id INTO pid FROM gateway_provider WHERE code = p_provider_code;
    IF pid IS NULL THEN
        RAISE EXCEPTION 'AIGW.COMMON.NOT_FOUND: provider' USING ERRCODE = 'P0002';
    END IF;
    INSERT INTO gateway_model (provider_id, code, capability, family, dimension, relative_cost)
    VALUES (pid, p_code, p_capability, NULLIF(p_family, ''), p_dimension, COALESCE(p_relative_cost, 1))
    RETURNING * INTO r;
    RETURN jsonb_build_object(
        'id', r.id,
        'provider_code', p_provider_code,
        'code', r.code,
        'capability', r.capability,
        'family', r.family,
        'dimension', r.dimension,
        'relative_cost', r.relative_cost
    );
END;
$$;

CREATE OR REPLACE FUNCTION aigw_model_get(
    p_provider_code TEXT,
    p_code TEXT,
    p_capability TEXT
)
RETURNS JSONB
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
    rec RECORD;
BEGIN
    SELECT m.id, p.code AS provider_code, m.code, m.capability, m.family, m.dimension, m.relative_cost
      INTO rec
      FROM gateway_model m
      JOIN gateway_provider p ON p.id = m.provider_id
     WHERE p.code = p_provider_code AND m.code = p_code AND m.capability = p_capability;
    IF NOT FOUND THEN
        RAISE EXCEPTION 'AIGW.COMMON.NOT_FOUND: model' USING ERRCODE = 'P0002';
    END IF;
    RETURN jsonb_build_object(
        'id', rec.id,
        'provider_code', rec.provider_code,
        'code', rec.code,
        'capability', rec.capability,
        'family', rec.family,
        'dimension', rec.dimension,
        'relative_cost', rec.relative_cost
    );
END;
$$;
