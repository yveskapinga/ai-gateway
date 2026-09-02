-- P1 — append-only. Contrat : pas de paramètre prompt (AIGW-002 INV-010).
CREATE OR REPLACE FUNCTION aigw_run_append(
    p_application_code TEXT,
    p_task TEXT,
    p_complexity TEXT,
    p_route_id UUID,
    p_provider_code TEXT,
    p_model_code TEXT,
    p_status TEXT,
    p_latency_ms INTEGER,
    p_error_code TEXT,
    p_correlation_id TEXT
)
RETURNS JSONB
LANGUAGE plpgsql
AS $$
DECLARE
    r gateway_run%ROWTYPE;
BEGIN
    IF p_correlation_id IS NULL OR length(trim(p_correlation_id)) = 0 THEN
        RAISE EXCEPTION 'AIGW.VALIDATION.FAILED: correlation_id requis'
            USING ERRCODE = '22023';
    END IF;

    INSERT INTO gateway_run (
        application_code, task, complexity, route_id,
        provider_code, model_code, status, latency_ms, error_code, correlation_id
    ) VALUES (
        p_application_code, p_task, p_complexity, p_route_id,
        p_provider_code, p_model_code, p_status, p_latency_ms, p_error_code, p_correlation_id
    )
    RETURNING * INTO r;

    RETURN jsonb_build_object(
        'id', r.id,
        'application_code', r.application_code,
        'task', r.task,
        'complexity', r.complexity,
        'route_id', r.route_id,
        'provider_code', r.provider_code,
        'model_code', r.model_code,
        'status', r.status,
        'latency_ms', r.latency_ms,
        'error_code', r.error_code,
        'correlation_id', r.correlation_id,
        'recorded_at', r.recorded_at
    );
END;
$$;
