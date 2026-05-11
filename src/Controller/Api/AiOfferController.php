<?php

namespace App\Controller\Api;

use App\Exception\AiApiException;
use App\Service\AiOfferOptimizerService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

class AiOfferController extends AbstractController
{
    #[Route('/api/ai/offer/optimize', name: 'app_api_ai_offer_optimize', methods: ['POST'])]
    public function optimize(
        Request $request,
        AiOfferOptimizerService $optimizerService,
        KernelInterface $kernel,
        ?LoggerInterface $logger = null
    ): JsonResponse
    {
        if (!$this->getUser()) {
            return $this->json(['error' => 'Authentification requise.'], 401);
        }

        $data = json_decode((string) $request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'JSON invalide.'], 400);
        }

        $title = trim((string) ($data['title'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));

        if ($title === '' || mb_strlen($title) < 3) {
            return $this->json(['error' => 'Le titre doit contenir au moins 3 caracteres.'], 400);
        }

        if ($description === '' || mb_strlen($description) < 10) {
            return $this->json(['error' => 'La description doit contenir au moins 10 caracteres.'], 400);
        }

        try {
            $result = $optimizerService->optimizeOffer($title, $description);
            return $this->json([
                'success' => true,
                'fallback' => false,
                'warning' => null,
                ...$result,
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'success' => false,
                'fallback' => false,
                'error' => $e->getMessage(),
            ], 400);
        } catch (AiApiException $e) {
            if ($kernel->isDebug()) {
                $logger?->error('Gemini optimize failed, local fallback applied', [
                    'message' => $e->getMessage(),
                    'status' => $e->getHttpStatus(),
                    'is_quota_exceeded' => $this->isQuotaExceeded($e),
                    'provider_response' => $e->getProviderResponse(),
                    'raw_response' => $e->getRawResponse(),
                ]);
            }

            return $this->json($this->buildFallbackResponse($optimizerService, $title, $description));
        } catch (\Throwable $e) {
            if ($kernel->isDebug()) {
                $logger?->error('Unexpected AI optimize failure, local fallback applied', [
                    'message' => $e->getMessage(),
                    'exception' => $e::class,
                ]);
            }

            return $this->json($this->buildFallbackResponse($optimizerService, $title, $description));
        }
    }

    private function isQuotaExceeded(AiApiException $exception): bool
    {
        if ($exception->getHttpStatus() === 429) {
            return true;
        }

        $message = $exception->getMessage();
        $providerResponse = $exception->getProviderResponse();
        $rawResponse = $exception->getRawResponse();
        $providerResponseText = $providerResponse !== null ? json_encode($providerResponse) : '';
        $rawResponseText = $rawResponse ?? '';
        $haystack = strtolower(implode(' ', array_filter([
            $message,
            $providerResponseText ?: '',
            $rawResponseText ?: '',
        ])));

        return str_contains($haystack, 'quota')
            || str_contains($haystack, 'rate limit')
            || str_contains($haystack, 'limit: 0')
            || str_contains($haystack, 'resource_exhausted');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildFallbackResponse(
        AiOfferOptimizerService $optimizerService,
        string $title,
        string $description
    ): array {
        try {
            $fallback = $optimizerService->optimizeOfferLocally($title, $description);
        } catch (\Throwable) {
            $fallback = [
                'optimizedTitle' => $title,
                'optimizedDescription' => $description,
                'suggestedCategory' => 'tutorat',
                'tags' => ['tutorat', 'etudiant'],
            ];
        }

        return [
            'success' => false,
            'error' => 'AI service temporarily unavailable',
            'fallback' => true,
            'warning' => 'Service IA temporairement indisponible, optimisation locale appliquée.',
            ...$fallback,
        ];
    }
}
