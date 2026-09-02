-- Seed P3 — low → ollama puis gemini. Modifiable sans redéployer le PHP.

INSERT INTO gateway_route (task, complexity, primary_model_id, fallback_model_id)
SELECT 'generate', 'low',
       (SELECT m.id FROM gateway_model m JOIN gateway_provider p ON p.id = m.provider_id
         WHERE p.code = 'ollama' AND m.code = 'llama3.2:3b' AND m.capability = 'generate'),
       (SELECT m.id FROM gateway_model m JOIN gateway_provider p ON p.id = m.provider_id
         WHERE p.code = 'gemini' AND m.code = 'gemini-flash-lite-latest' AND m.capability = 'generate')
ON CONFLICT (task, complexity) DO NOTHING;

INSERT INTO gateway_route (task, complexity, primary_model_id, fallback_model_id)
SELECT 'generate', 'medium',
       (SELECT m.id FROM gateway_model m JOIN gateway_provider p ON p.id = m.provider_id
         WHERE p.code = 'gemini' AND m.code = 'gemini-2.5-flash' AND m.capability = 'generate'),
       (SELECT m.id FROM gateway_model m JOIN gateway_provider p ON p.id = m.provider_id
         WHERE p.code = 'ollama' AND m.code = 'llama3.2:3b' AND m.capability = 'generate')
ON CONFLICT (task, complexity) DO NOTHING;

INSERT INTO gateway_route (task, complexity, primary_model_id, fallback_model_id)
SELECT 'generate', 'high',
       (SELECT m.id FROM gateway_model m JOIN gateway_provider p ON p.id = m.provider_id
         WHERE p.code = 'gemini' AND m.code = 'gemini-2.5-flash' AND m.capability = 'generate'),
       (SELECT m.id FROM gateway_model m JOIN gateway_provider p ON p.id = m.provider_id
         WHERE p.code = 'gemini' AND m.code = 'gemini-flash-lite-latest' AND m.capability = 'generate')
ON CONFLICT (task, complexity) DO NOTHING;

INSERT INTO gateway_route (task, complexity, primary_model_id, fallback_model_id)
SELECT 'embed', 'low',
       (SELECT m.id FROM gateway_model m JOIN gateway_provider p ON p.id = m.provider_id
         WHERE p.code = 'gemini' AND m.code = 'gemini-embedding-001' AND m.capability = 'embed'),
       NULL
ON CONFLICT (task, complexity) DO NOTHING;
