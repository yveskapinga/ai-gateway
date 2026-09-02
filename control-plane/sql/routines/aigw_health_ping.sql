-- P1 — sonde persistance. Pas une inférence.
CREATE OR REPLACE FUNCTION aigw_health_ping()
RETURNS TEXT
LANGUAGE sql
STABLE
AS $$
    SELECT 'ok'::TEXT;
$$;
