<?php

declare(strict_types=1);

namespace AiGateway\Infrastructure\Inference;

use AiGateway\Domain\GatewayException;

/**
 * Décodage JSON d’une complétion structurée — refuse un contrat incomplet.
 *
 * Pourquoi : un petit modèle local peut renvoyer un objet JSON « valide »
 * sans les champs requis, ou un answer qui décrit le schéma au lieu de répondre.
 *
 * Garantit : jsonSchema.required manquant / answer inutilisable → GatewayException
 * (failover P5), pas une complétion affichée telle quelle.
 *
 * Ne fait pas : appeler Ollama / Gemini ; décider la route.
 */
final class StructuredGenerateParser
{
    /**
     * @param array<string, mixed>|null $jsonSchema
     * @return array<string, mixed>|null
     */
    public static function decode(string $text, ?array $jsonSchema): ?array
    {
        if ($jsonSchema === null) {
            return null;
        }

        $decoded = json_decode(self::unwrap($text), true);
        if (!is_array($decoded)) {
            throw GatewayException::unavailable('JSON du modèle invalide.');
        }

        $required = $jsonSchema['required'] ?? [];
        if (is_array($required)) {
            foreach ($required as $key) {
                if (!is_string($key) || !array_key_exists($key, $decoded)) {
                    throw GatewayException::unavailable('JSON du modèle incomplet.');
                }
            }
        }

        if (isset($decoded['answer']) && is_string($decoded['answer']) && self::isUnusableAnswer($decoded['answer'])) {
            throw GatewayException::unavailable('Réponse structurée inutilisable.');
        }

        return $decoded;
    }

    public static function isUnusableAnswer(string $answer): bool
    {
        $trimmed = trim($answer);
        if ($trimmed === '') {
            return true;
        }
        $normalized = mb_strtolower($trimmed);
        if (preg_match('/pas reç[ue]\s+(d[\'’]?)?answer/', $normalized) === 1) {
            return true;
        }

        return preg_match('/\b(missing required|did not receive|no answer (was )?provided)\b/', $normalized) === 1;
    }

    private static function unwrap(string $text): string
    {
        $trimmed = trim($text);
        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/s', $trimmed, $match) === 1) {
            return $match[1];
        }

        return $trimmed;
    }
}
