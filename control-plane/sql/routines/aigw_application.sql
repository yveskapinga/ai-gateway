CREATE OR REPLACE FUNCTION aigw_application_create(p_code TEXT, p_status TEXT DEFAULT 'active')
RETURNS JSONB
LANGUAGE plpgsql
AS $$
DECLARE
    r gateway_application%ROWTYPE;
BEGIN
    IF p_code IS NULL OR length(trim(p_code)) = 0 THEN
        RAISE EXCEPTION 'AIGW.VALIDATION.FAILED: code requis' USING ERRCODE = '22023';
    END IF;
    INSERT INTO gateway_application (code, status)
    VALUES (trim(p_code), COALESCE(p_status, 'active'))
    RETURNING * INTO r;
    RETURN jsonb_build_object(
        'id', r.id, 'code', r.code, 'status', r.status, 'created_at', r.created_at
    );
END;
$$;

CREATE OR REPLACE FUNCTION aigw_application_get(p_code TEXT)
RETURNS JSONB
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
    r gateway_application%ROWTYPE;
BEGIN
    SELECT * INTO r FROM gateway_application WHERE code = p_code;
    IF NOT FOUND THEN
        RAISE EXCEPTION 'AIGW.COMMON.NOT_FOUND: application' USING ERRCODE = 'P0002';
    END IF;
    RETURN jsonb_build_object(
        'id', r.id, 'code', r.code, 'status', r.status, 'created_at', r.created_at
    );
END;
$$;
