<?php

namespace App\Service;

use App\Exception\AiApiException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiOfferOptimizerService
{
    public function __construct(
        private GeminiClientService $geminiClient,
        #[Autowire(service: 'ai.http_client')]
        private HttpClientInterface $httpClient,
        #[Autowire('%env(default::HF_TOKEN)%')]
        private ?string $hfToken = null,
        #[Autowire('%env(default::AI_API_KEY)%')]
        private ?string $aiApiKey = null,
        #[Autowire('%env(default:ai_hf_model:AI_HF_MODEL)%')]
        private ?string $hfModel = null,
        #[Autowire('%kernel.debug%')]
        private bool $debug = false,
        private ?LoggerInterface $logger = null
    ) {
    }

    /**
     * @return array{
     *     optimizedTitle: string,
     *     optimizedDescription: string,
     *     suggestedCategory: string,
     *     tags: array<int, string>
     * }
     */
    public function optimizeOffer(string $title, string $description): array
    {
        $title = trim($title);
        $description = trim($description);

        if ($title === '' || $description === '') {
            throw new \InvalidArgumentException('Le titre et la description sont obligatoires.');
        }

        $systemPrompt = <<<PROMPT
Tu es un assistant RH expert en offres jobs etudiantes.
Retourne UNIQUEMENT un JSON valide, sans markdown, sans texte additionnel.
Le JSON doit contenir exactement ces cles:
- optimizedTitle (string)
- optimizedDescription (string)
- suggestedCategory (string): une valeur parmi tutorat, aide_academique, creation
- tags (array de 3 a 8 tags courts)
PROMPT;

        $userPrompt = <<<PROMPT
Optimise cette offre:
Titre: {$title}
Description: {$description}

Objectifs:
- Rendre le titre plus clair et attractif
- Rendre la description plus professionnelle, concise et lisible
- Proposer la meilleure categorie
- Proposer des tags utiles

Retour strict en JSON uniquement.
PROMPT;

        try {
            $decoded = $this->geminiClient->generateJson($systemPrompt, $userPrompt);
        } catch (AiApiException $geminiException) {
            if (!$this->shouldTryHfFallback($geminiException)) {
                throw $geminiException;
            }

            $this->logger?->warning('Gemini unavailable for offer optimization, trying Hugging Face fallback', [
                'status' => $geminiException->getHttpStatus(),
                'message' => $geminiException->getMessage(),
            ]);

            $decoded = $this->generateJsonWithHuggingFace($systemPrompt, $userPrompt);
        }

        $optimizedTitle = trim((string) ($decoded['optimizedTitle'] ?? $title));
        $optimizedDescription = trim((string) ($decoded['optimizedDescription'] ?? $description));
        $suggestedCategory = $this->normalizeCategory((string) ($decoded['suggestedCategory'] ?? 'tutorat'));
        $tags = $this->normalizeTags($decoded['tags'] ?? []);

        if ($optimizedTitle === '') {
            $optimizedTitle = $title;
        }
        if ($optimizedDescription === '') {
            $optimizedDescription = $description;
        }

        return [
            'optimizedTitle' => $optimizedTitle,
            'optimizedDescription' => $optimizedDescription,
            'suggestedCategory' => $suggestedCategory,
            'tags' => $tags,
        ];
    }

    /**
     * Fallback local optimizer when external AI is unavailable.
     *
     * @return array{
     *     optimizedTitle: string,
     *     optimizedDescription: string,
     *     suggestedCategory: string,
     *     tags: array<int, string>
     * }
     */
    public function optimizeOfferLocally(string $title, string $description): array
    {
        $title = trim($title);
        $description = trim($description);

        if ($title === '' || $description === '') {
            throw new \InvalidArgumentException('Le titre et la description sont obligatoires.');
        }

        $suggestedCategory = $this->detectCategoryFromKeywords($title . ' ' . $description);

        $optimizedTitle = $this->formatTitle($title);
        $optimizedDescription = $this->formatDescription($description, $suggestedCategory);

        return [
            'optimizedTitle' => $optimizedTitle,
            'optimizedDescription' => $optimizedDescription,
            'suggestedCategory' => $suggestedCategory,
            'tags' => $this->buildLocalTags($optimizedTitle, $optimizedDescription, $suggestedCategory),
        ];
    }

    private function normalizeCategory(string $value): string
    {
        $value = strtolower(trim($value));
        if (in_array($value, ['tutorat', 'aide_academique', 'creation'], true)) {
            return $value;
        }

        if (str_contains($value, 'tutor')) {
            return 'tutorat';
        }
        if (str_contains($value, 'crea')) {
            return 'creation';
        }
        if (str_contains($value, 'aide')) {
            return 'aide_academique';
        }

        return 'tutorat';
    }

    private function detectCategoryFromKeywords(string $text): string
    {
        $value = mb_strtolower($text, 'UTF-8');

        $creationKeywords = ['design', 'figma', 'photoshop', 'illustrator', 'ui', 'ux', 'logo', 'canva', 'video', 'montage', 'creation'];
        foreach ($creationKeywords as $keyword) {
            if (str_contains($value, $keyword)) {
                return 'creation';
            }
        }

        $academicKeywords = ['memoire', 'rapport', 'pfe', 'redaction', 'orthographe', 'correction', 'cv', 'lettre', 'aide academique'];
        foreach ($academicKeywords as $keyword) {
            if (str_contains($value, $keyword)) {
                return 'aide_academique';
            }
        }

        $tutoratKeywords = ['sql', 'python', 'java', 'c++', 'algo', 'algorithm', 'base de donnees', 'math', 'programmation', 'cours', 'soutien', 'tutorat'];
        foreach ($tutoratKeywords as $keyword) {
            if (str_contains($value, $keyword)) {
                return 'tutorat';
            }
        }

        return 'tutorat';
    }

    private function formatTitle(string $title): string
    {
        $title = preg_replace('/\s+/', ' ', trim($title)) ?? trim($title);
        if ($title === '') {
            return $title;
        }

        return mb_convert_case($title, MB_CASE_TITLE, 'UTF-8');
    }

    private function formatDescription(string $description, string $category): string
    {
        $description = preg_replace('/\s+/', ' ', trim($description)) ?? trim($description);
        if ($description === '') {
            return $description;
        }

        $first = mb_substr($description, 0, 1, 'UTF-8');
        $rest = mb_substr($description, 1, null, 'UTF-8');
        $description = mb_strtoupper($first, 'UTF-8') . $rest;

        if (!preg_match('/[.!?]\s*$/u', $description)) {
            $description .= '.';
        }

        $suffix = match ($category) {
            'creation' => ' Profil recherche: creativite, rigueur et sens du detail.',
            'aide_academique' => ' Profil recherche: bonne communication, pedagogie et sens de l organisation.',
            default => ' Profil recherche: motivation, serieux et capacite a expliquer clairement.',
        };

        if (!str_contains(mb_strtolower($description, 'UTF-8'), 'profil recherche')) {
            $description .= $suffix;
        }

        return $description;
    }

    /**
     * @return array<int, string>
     */
    private function buildLocalTags(string $title, string $description, string $category): array
    {
        $baseTags = match ($category) {
            'creation' => ['creation', 'design', 'projet', 'etudiant'],
            'aide_academique' => ['aide', 'academique', 'etudiant', 'redaction'],
            default => ['tutorat', 'formation', 'etudiant', 'soutien'],
        };

        $text = mb_strtolower($title . ' ' . $description, 'UTF-8');
        $extra = [];
        foreach (['sql', 'python', 'java', 'figma', 'photoshop', 'cv', 'rapport', 'math', 'algorithmique'] as $k) {
            if (str_contains($text, $k)) {
                $extra[] = $k;
            }
        }

        $tags = array_values(array_unique(array_merge($baseTags, $extra)));
        return array_slice($tags, 0, 8);
    }

    /**
     * @param mixed $tags
     * @return array<int, string>
     */
    private function normalizeTags(mixed $tags): array
    {
        if (!is_array($tags)) {
            return [];
        }

        $normalized = [];
        foreach ($tags as $tag) {
            if (!is_string($tag)) {
                continue;
            }
            $value = trim($tag);
            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        $normalized = array_values(array_unique($normalized));
        return array_slice($normalized, 0, 8);
    }

    private function shouldTryHfFallback(AiApiException $exception): bool
    {
        $status = $exception->getHttpStatus();
        if ($status === 429 || $status >= 500) {
            return true;
        }

        $providerResponse = $exception->getProviderResponse();
        $rawResponse = $exception->getRawResponse();
        $providerResponseText = $providerResponse !== null ? json_encode($providerResponse) : '';
        $rawResponseText = $rawResponse ?? '';
        $haystack = strtolower(implode(' ', array_filter([
            $exception->getMessage(),
            $providerResponseText ?: '',
            $rawResponseText ?: '',
        ])));

        return str_contains($haystack, 'quota')
            || str_contains($haystack, 'resource_exhausted')
            || str_contains($haystack, 'rate limit')
            || str_contains($haystack, 'limit: 0');
    }

    /**
     * @return array<string, mixed>
     */
    private function generateJsonWithHuggingFace(string $systemPrompt, string $userPrompt): array
    {
        $token = $this->resolveHfApiKey();
        if ($token === null) {
            throw new AiApiException('HF_TOKEN manquant pour le fallback IA.', 503);
        }

        $model = trim((string) $this->hfModel);
        if ($model === '') {
            $model = 'katanemo/Arch-Router-1.5B';
        }

        try {
            $response = $this->httpClient->request('POST', 'https://router.huggingface.co/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $model . ':hf-inference',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemPrompt,
                        ],
                        [
                            'role' => 'user',
                            'content' => $userPrompt,
                        ],
                    ],
                    'temperature' => 0.2,
                    'max_tokens' => 600,
                ],
                'timeout' => 30,
            ]);
        } catch (\Throwable $e) {
            throw new AiApiException('Echec de connexion au fallback Hugging Face: ' . $e->getMessage(), 503, null, null, $e);
        }

        $status = $response->getStatusCode();
        $rawBody = $response->getContent(false);
        $payload = json_decode($rawBody, true);

        if ($this->debug) {
            $this->logger?->debug('Hugging Face optimize raw response', [
                'status' => $status,
                'response' => $rawBody,
            ]);
        }

        if ($status >= 400) {
            $message = 'Service Hugging Face indisponible.';
            if (is_array($payload)) {
                $message = (string) ($payload['error']['message'] ?? $payload['error'] ?? $message);
            }
            throw new AiApiException(sprintf('[HTTP %d] %s', $status, $message), $status, is_array($payload) ? $payload : null, $rawBody);
        }

        $content = (string) ($payload['choices'][0]['message']['content'] ?? '');
        if ($content === '') {
            throw new AiApiException('Reponse Hugging Face vide.', 502, is_array($payload) ? $payload : null, $rawBody);
        }

        return $this->decodeJsonSafely($content);
    }

    private function resolveHfApiKey(): ?string
    {
        $hfToken = trim((string) $this->hfToken);
        if ($hfToken !== '' && str_starts_with($hfToken, 'hf_')) {
            return $hfToken;
        }

        $aiKey = trim((string) $this->aiApiKey);
        if ($aiKey !== '' && str_starts_with($aiKey, 'hf_')) {
            return $aiKey;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonSafely(string $text): array
    {
        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/is', $text, $matches) === 1) {
            $decoded = json_decode($matches[1], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        if (preg_match('/\{.*\}/s', $text, $matches) === 1) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        throw new AiApiException('Impossible de parser le JSON retourne par l IA.', 502, null, $text);
    }
}

