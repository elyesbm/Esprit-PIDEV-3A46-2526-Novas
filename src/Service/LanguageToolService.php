<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class LanguageToolService
{
    private string $endpoint;

    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire('%env(default::LANGUAGE_TOOL_URL)%')]
        ?string $languageToolUrl = null
    ) {
        $this->endpoint = trim((string) $languageToolUrl);
        if ($this->endpoint === '') {
            $this->endpoint = 'https://api.languagetool.org/v2/check';
        }
    }

    /**
     * @return array{
     *     matches: array<int, array<string, mixed>>,
     *     totalErrors: int,
     *     qualityScore: int
     * }
     */
    public function checkText(string $text, string $language = 'fr'): array
    {
        $normalizedText = trim($text);
        $normalizedLanguage = trim($language) !== '' ? trim($language) : 'fr';

        if ($normalizedText === '') {
            return [
                'matches' => [],
                'totalErrors' => 0,
                'qualityScore' => 100,
            ];
        }

        $response = $this->httpClient->request('POST', $this->endpoint, [
            'headers' => ['Accept' => 'application/json'],
            'body' => [
                'text' => $normalizedText,
                'language' => $normalizedLanguage,
            ],
            'timeout' => 15,
        ]);

        $status = $response->getStatusCode();
        $payload = $response->toArray(false);

        if ($status >= 400) {
            $message = 'LanguageTool indisponible.';
            $apiMessage = $payload['message'] ?? $payload['error'] ?? null;
            if (is_string($apiMessage) && trim($apiMessage) !== '') {
                $message = trim($apiMessage);
            }

            throw new \RuntimeException($message);
        }

        $rawMatches = $payload['matches'] ?? [];
        if (!is_array($rawMatches)) {
            $rawMatches = [];
        }

        $matches = [];
        foreach ($rawMatches as $rawMatch) {
            if (!is_array($rawMatch)) {
                continue;
            }

            $offset = (int) ($rawMatch['offset'] ?? 0);
            $length = (int) ($rawMatch['length'] ?? 0);
            $context = '';
            if (isset($rawMatch['context']) && is_array($rawMatch['context'])) {
                $context = (string) ($rawMatch['context']['text'] ?? '');
            }

            $replacements = [];
            $rawReplacements = $rawMatch['replacements'] ?? [];
            if (is_array($rawReplacements)) {
                foreach (array_slice($rawReplacements, 0, 5) as $replacement) {
                    if (is_array($replacement) && isset($replacement['value'])) {
                        $value = trim((string) $replacement['value']);
                        if ($value !== '') {
                            $replacements[] = $value;
                        }
                    }
                }
            }

            $matches[] = [
                'message' => (string) ($rawMatch['message'] ?? 'Erreur detectee'),
                'shortMessage' => (string) ($rawMatch['shortMessage'] ?? ''),
                'offset' => $offset,
                'length' => $length,
                'context' => $context,
                'replacements' => $replacements,
            ];
        }

        $totalErrors = count($matches);
        $qualityScore = max(0, 100 - ($totalErrors * 5));

        return [
            'matches' => $matches,
            'totalErrors' => $totalErrors,
            'qualityScore' => $qualityScore,
        ];
    }
}

