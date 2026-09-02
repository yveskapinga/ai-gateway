# PHP SDK — PEP d’inférence (AIGW-006)

Package `aigateway/sdk-php` (`AiGateway\Sdk`). **P8**.

Le client **demande** `POST /v1/generate` et `POST /v1/embed`. Il n’embarque pas les routes, ni une clé Gemini, ni un moteur local.

```text
PEP asks → gateway decides route + failover → PEP returns text / error
erreur transport / 503 ≠ texte inventé
```

Entrée : `GatewayClient`, `HttpTransport`, `CallableTransport` (tests).

Tests : `control-plane/tests/Unit/SdkTest.php`. Gate : `docs/reports/PHASE-P8-GATE.md`.

```php
$client = new \AiGateway\Sdk\GatewayClient(
    new \AiGateway\Sdk\HttpTransport('http://127.0.0.1:18190'),
    getenv('AIGW_API_KEY') ?: '',
);

$out = $client->generate('APP_DEMO', 'generate', 'low', 'bonjour');
```

La clé passée au transport est `AIGW_API_KEY` (service-to-service), **pas** `GEMINI_API_KEY`.
