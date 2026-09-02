CREATE OR REPLACE FUNCTION aigw_route_upsert(
    p_task TEXT,
    p_complexity TEXT,
    p_primary_provider TEXT,
    p_primary_model TEXT,
    p_primary_capability TEXT,
    p_fallback_provider TEXT,
    p_fallback_model TEXT,
    p_fallback_capability TEXT
)
RETURNS JSONB
LANGUAGE plpgsql
AS $$
DECLARE
    primary_id UUID;
    fallback_id UUID;
    r gateway_route%ROWTYPE;
BEGIN
    SELECT m.id INTO primary_id
      FROM gateway_model m
      JOIN gateway_provider p ON p.id = m.provider_id
     WHERE p.code = p_primary_provider AND m.code = p_primary_model AND m.capability = p_primary_capability;
    IF primary_id IS NULL THEN
        RAISE EXCEPTION 'AIGW.COMMON.NOT_FOUND: primary model' USING ERRCODE = 'P0002';
    END IF;
    fallback_id := NULL;
    IF p_fallback_provider IS NOT NULL AND p_fallback_model IS NOT NULL THEN
        SELECT m.id INTO fallback_id
          FROM gateway_model m
          JOIN gateway_provider p ON p.id = m.provider_id
         WHERE p.code = p_fallback_provider AND m.code = p_fallback_model
           AND m.capability = COALESCE(p_fallback_capability, 'generate');
    END IF;

    INSERT INTO gateway_route (task, complexity, primary_model_id, fallback_model_id)
    VALUES (p_task, p_complexity, primary_id, fallback_id)
    ON CONFLICT (task, complexity) DO UPDATE
        SET primary_model_id = EXCLUDED.primary_model_id,
            fallback_model_id = EXCLUDED.fallback_model_id
    RETURNING * INTO r;

    RETURN jsonb_build_object(
        'id', r.id,
        'task', r.task,
        'complexity', r.complexity
    );
END;
$$;

CREATE OR REPLACE FUNCTION aigw_route_resolve(p_task TEXT, p_complexity TEXT)
RETURNS JSONB
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
    rec RECORD;
BEGIN
    SELECT
        r.id AS route_id,
        r.task,
        r.complexity,
        pp.code AS primary_provider_code,
        pp.kind AS primary_kind,
        pp.endpoint AS primary_endpoint,
        pp.secret_env_name AS primary_secret_env_name,
        pm.code AS primary_model_code,
        pm.capability AS primary_capability,
        pm.family AS primary_family,
        pm.dimension AS primary_dimension,
        fp.code AS fallback_provider_code,
        fp.kind AS fallback_kind,
        fp.endpoint AS fallback_endpoint,
        fp.secret_env_name AS fallback_secret_env_name,
        fm.code AS fallback_model_code,
        fm.capability AS fallback_capability,
        fm.family AS fallback_family,
        fm.dimension AS fallback_dimension
      INTO rec
      FROM gateway_route r
      JOIN gateway_model pm ON pm.id = r.primary_model_id
      JOIN gateway_provider pp ON pp.id = pm.provider_id
      LEFT JOIN gateway_model fm ON fm.id = r.fallback_model_id
      LEFT JOIN gateway_provider fp ON fp.id = fm.provider_id
     WHERE r.task = p_task AND r.complexity = p_complexity;

    IF NOT FOUND THEN
        RAISE EXCEPTION 'AIGW.ROUTE.UNKNOWN: % / %', p_task, p_complexity USING ERRCODE = 'P0002';
    END IF;

    RETURN jsonb_build_object(
        'route_id', rec.route_id,
        'task', rec.task,
        'complexity', rec.complexity,
        'primary', jsonb_build_object(
            'provider_code', rec.primary_provider_code,
            'kind', rec.primary_kind,
            'endpoint', rec.primary_endpoint,
            'secret_env_name', rec.primary_secret_env_name,
            'code', rec.primary_model_code,
            'capability', rec.primary_capability,
            'family', rec.primary_family,
            'dimension', rec.primary_dimension
        ),
        'fallback', CASE WHEN rec.fallback_model_code IS NULL THEN NULL ELSE jsonb_build_object(
            'provider_code', rec.fallback_provider_code,
            'kind', rec.fallback_kind,
            'endpoint', rec.fallback_endpoint,
            'secret_env_name', rec.fallback_secret_env_name,
            'code', rec.fallback_model_code,
            'capability', rec.fallback_capability,
            'family', rec.fallback_family,
            'dimension', rec.fallback_dimension
        ) END
    );
END;
$$;
