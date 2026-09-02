-- Seed P2 — providers + modèles de référence. secret = nom d’env.
-- Les codes d’application métier (consommateurs) ne sont PAS seedés ici.

INSERT INTO gateway_provider (code, kind, endpoint, secret_env_name)
VALUES
    ('ollama', 'ollama', 'http://127.0.0.1:11434', NULL),
    ('gemini', 'gemini', 'https://generativelanguage.googleapis.com', 'GEMINI_API_KEY')
ON CONFLICT (code) DO NOTHING;

INSERT INTO gateway_model (provider_id, code, capability, family, dimension, relative_cost)
SELECT p.id, 'llama3.2:3b', 'generate', NULL, NULL, 1
  FROM gateway_provider p WHERE p.code = 'ollama'
ON CONFLICT (provider_id, code, capability) DO NOTHING;

INSERT INTO gateway_model (provider_id, code, capability, family, dimension, relative_cost)
SELECT p.id, 'gemini-flash-lite-latest', 'generate', NULL, NULL, 2
  FROM gateway_provider p WHERE p.code = 'gemini'
ON CONFLICT (provider_id, code, capability) DO NOTHING;

INSERT INTO gateway_model (provider_id, code, capability, family, dimension, relative_cost)
SELECT p.id, 'gemini-2.5-flash', 'generate', NULL, NULL, 3
  FROM gateway_provider p WHERE p.code = 'gemini'
ON CONFLICT (provider_id, code, capability) DO NOTHING;

INSERT INTO gateway_model (provider_id, code, capability, family, dimension, relative_cost)
SELECT p.id, 'gemini-embedding-001', 'embed', 'gemini-768', 768, 2
  FROM gateway_provider p WHERE p.code = 'gemini'
ON CONFLICT (provider_id, code, capability) DO NOTHING;

INSERT INTO gateway_application (code, status)
VALUES ('APP_DEMO', 'active')
ON CONFLICT (code) DO NOTHING;
