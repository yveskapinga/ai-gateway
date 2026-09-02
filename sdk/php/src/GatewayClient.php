<?php

declare(strict_types=1);

namespace AiGateway\Sdk;

/**
 * Client PEP — demande generate/embed, n’évalue pas les routes.
 *
 * Pourquoi : AIGW-006. Un SDK, les mêmes sémantiques que AIGW-005.
 * Garantit : transport down / 503 → exception, jamais un texte inventé.
 * Ne fait pas : résoudre une route SQL ; client d’inférence locale ; SDK cloud ; RAG.
 *
 * Debug : correlationId ; error.code AIGW.* / AIGW.PEP.*.
 */
final class GatewayClient
{
    public function __construct(
        private readonly GatewayTransport $transport,
        private readonly string $apiKey = '',
    ) {
    }

    /**
     * Construit depuis l’environnement de l’app (clé gateway, pas Gemini).
     */
    public static function fromEnvironment(?GatewayTransport $transport = null): self
    {
        $base = getenv('AIGW_BASE_URL') ?: '';
        $key = getenv('AIGW_API_KEY') ?: '';
        $transport ??= new HttpTransport($base);

        return new self($transport, $key);
    }

    public function health(?string $correlationId = null): array
    {
        $result = $this->transport->request('GET', '/v1/health', $this->headers($correlationId), []);
        $this->assertTransport($result);
        $body = $result['body'] ?? [];
        if (($result['status'] ?? 0) >= 400) {
            throw $this->fromErrorBody($result);
        }

        return $body;
    }

    /**
     * @param array<string, mixed>|null $jsonSchema
     */
    public function generate(
        string $application,
        string $task,
        string $complexity,
        string $prompt,
        ?array $jsonSchema = null,
        ?string $correlationId = null,
    ): GenerateSuccess {
        $payload = [
            'application' => $application,
            'task' => $task,
            'complexity' => $complexity,
            'prompt' => $prompt,
        ];
        if ($jsonSchema !== null) {
            $payload['jsonSchema'] = $jsonSchema;
        }
        $result = $this->transport->request('POST', '/v1/generate', $this->headers($correlationId), $payload);
        $this->assertTransport($result);
        if (($result['status'] ?? 0) >= 400) {
            throw $this->fromErrorBody($result);
        }
        $body = $result['body'] ?? [];
        $text = (string) ($body['text'] ?? '');
        $provider = (string) ($body['provider'] ?? '');
        $model = (string) ($body['model'] ?? '');
        $cid = (string) ($body['correlationId'] ?? $result['correlationId'] ?? '');
        if ($text === '' || $provider === '' || $model === '') {
            throw TransportException::unavailable('Réponse generate incomplète (pas une complétion inventée).', $cid !== '' ? $cid : null);
        }
        $json = isset($body['json']) && is_array($body['json']) ? $body['json'] : null;

        return new GenerateSuccess($text, $provider, $model, $cid, $json);
    }

    /**
     * @param list<string> $texts
     */
    public function embed(
        string $application,
        string $family,
        array $texts,
        ?string $correlationId = null,
    ): EmbedSuccess {
        $result = $this->transport->request('POST', '/v1/embed', $this->headers($correlationId), [
            'application' => $application,
            'family' => $family,
            'texts' => $texts,
        ]);
        $this->assertTransport($result);
        if (($result['status'] ?? 0) >= 400) {
            throw $this->fromErrorBody($result);
        }
        $body = $result['body'] ?? [];
        $vectors = $body['vectors'] ?? null;
        if (!is_array($vectors) || $vectors === []) {
            throw TransportException::unavailable('Réponse embed incomplète (pas un vecteur inventé).', $body['correlationId'] ?? $result['correlationId'] ?? null);
        }

        return new EmbedSuccess(
            $vectors,
            (string) ($body['family'] ?? $family),
            (int) ($body['dimension'] ?? 0),
            (string) ($body['provider'] ?? ''),
            (string) ($body['model'] ?? ''),
            (string) ($body['correlationId'] ?? $result['correlationId'] ?? ''),
        );
    }

    /**
     * @param array{status: int, body: array<string, mixed>|null, correlationId: string|null, error: string|null} $result
     */
    private function assertTransport(array $result): void
    {
        if (($result['status'] ?? 0) === 0) {
            throw TransportException::timeout($result['correlationId'] ?? null);
        }
        if ($result['body'] === null) {
            throw TransportException::unavailable('Payload non JSON.', $result['correlationId'] ?? null);
        }
    }

    /**
     * @param array{status: int, body: array<string, mixed>|null, correlationId: string|null, error: string|null} $result
     */
    private function fromErrorBody(array $result): GatewayClientException
    {
        $error = is_array($result['body']['error'] ?? null) ? $result['body']['error'] : [];
        $code = (string) ($error['code'] ?? 'AIGW.COMMON.INTERNAL');
        $message = (string) ($error['message'] ?? 'Erreur gateway.');
        $cid = $error['correlationId'] ?? $result['correlationId'] ?? null;

        return new GatewayClientException(
            $code,
            $message,
            (int) $result['status'],
            is_string($cid) ? $cid : null,
            $error['details'] ?? null,
        );
    }

    /**
     * @return array<string, string>
     */
    private function headers(?string $correlationId): array
    {
        $headers = [];
        if ($this->apiKey !== '') {
            $headers['X-API-Key'] = $this->apiKey;
        }
        if ($correlationId !== null && $correlationId !== '') {
            $headers['X-Correlation-Id'] = $correlationId;
        }

        return $headers;
    }
}
