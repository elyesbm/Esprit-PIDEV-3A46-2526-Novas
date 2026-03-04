<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Image generation via Hugging Face Inference providers.
 * - If HF_IMAGE_SCRIPT is configured: uses Python helper script.
 * - Otherwise: uses HTTP API.
 */
class HuggingFaceImageService
{
    private const DEFAULT_BASE_URL = 'https://router.huggingface.co/hf-inference/models';
    private const DEFAULT_MODEL = 'ByteDance/SDXL-Lightning';

    public function __construct(
        private HttpClientInterface $httpClient,
        private ?string $hfToken = null,
        private ?string $model = null,
        private ?string $baseUrl = null,
        private ?string $scriptPath = null,
        private string $projectDir = '',
        private ?string $provider = null
    ) {
    }

    /**
     * @return array<array{base64: string}>
     */
    public function generateImages(string $prompt, int $sampleCount = 1): array
    {
        $token = trim($this->hfToken ?? '');
        if ($token === '') {
            throw new \RuntimeException(
                'HF_TOKEN is missing. Add a token with "Inference Providers" permission on huggingface.co/settings/tokens.'
            );
        }

        $prompt = trim($prompt);
        if ($prompt === '') {
            throw new \RuntimeException('Prompt is required.');
        }

        $script = $this->resolveScriptPath();
        if ($script !== null) {
            try {
                return $this->runScript($script, $prompt);
            } catch (\Throwable $e) {
                // Fallback to HTTP if local Python script fails.
                try {
                    return $this->callHttpApi($prompt);
                } catch (\Throwable) {
                    throw new \RuntimeException('Image generation unavailable. ' . $e->getMessage(), 0, $e);
                }
            }
        }

        return $this->callHttpApi($prompt);
    }

    private function resolveScriptPath(): ?string
    {
        $raw = trim($this->scriptPath ?? '');
        if ($raw === '') {
            return null;
        }

        $path = $raw;
        if ($this->projectDir !== '' && !str_starts_with($path, '/') && !preg_match('#^[A-Za-z]:\\\\#', $path)) {
            $path = rtrim($this->projectDir, '/\\') . \DIRECTORY_SEPARATOR . ltrim($path, '/\\');
        }

        return is_file($path) ? $path : null;
    }

    /**
     * @return array<array{base64: string}>
     */
    private function runScript(string $scriptPath, string $prompt): array
    {
        $provider = trim($this->provider ?? '');
        if ($provider === '') {
            $provider = 'replicate';
        }

        $env = array_merge(getenv(), [
            'HF_TOKEN' => $this->hfToken ?? '',
            'HF_IMAGE_MODEL' => trim($this->model ?? '') !== '' ? trim($this->model) : self::DEFAULT_MODEL,
            'HF_IMAGE_PROVIDER' => $provider,
        ]);

        $proc = proc_open(
            [PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3', $scriptPath],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            $env,
            ['bypass_shell' => true]
        );

        if (!\is_resource($proc)) {
            throw new \RuntimeException('Unable to start hf_image.py script.');
        }

        fwrite($pipes[0], $prompt);
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $code = proc_close($proc);
        if ($code !== 0) {
            throw new \RuntimeException('HF image script error: ' . ($stderr ?: "exit $code"));
        }

        $stdout = trim($stdout);
        if ($stdout === '') {
            throw new \RuntimeException('HF image script returned empty output.');
        }

        if (base64_decode($stdout, true) === false) {
            throw new \RuntimeException('HF image script returned invalid base64.');
        }

        return [['base64' => $stdout]];
    }

    /**
     * @return array<array{base64: string}>
     */
    private function callHttpApi(string $prompt): array
    {
        $modelId = trim($this->model ?? '') !== '' ? trim($this->model) : self::DEFAULT_MODEL;
        $base = trim($this->baseUrl ?? '') !== '' ? rtrim($this->baseUrl, '/') : self::DEFAULT_BASE_URL;

        // Backward compatibility for old/deprecated endpoints.
        if (preg_match('#^https://api-inference\.huggingface\.co/models#i', $base)) {
            $base = preg_replace(
                '#^https://api-inference\.huggingface\.co/models#i',
                'https://router.huggingface.co/hf-inference/models',
                $base
            ) ?? $base;
        } elseif (preg_match('#^https://router\.huggingface\.co/models#i', $base)) {
            $base = preg_replace(
                '#^https://router\.huggingface\.co/models#i',
                'https://router.huggingface.co/hf-inference/models',
                $base
            ) ?? $base;
        }

        $modelPath = str_replace('%2F', '/', rawurlencode($modelId));
        $url = str_contains($base, '{model}')
            ? str_replace('{model}', $modelPath, $base)
            : $base . '/' . $modelPath;

        $payload = [
            'inputs' => $prompt,
            'parameters' => [
                'num_inference_steps' => 4,
            ],
        ];

        $response = $this->httpClient->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . trim($this->hfToken ?? ''),
                'Content-Type' => 'application/json',
                'Accept' => 'image/png,application/json',
            ],
            'json' => $payload,
            'timeout' => 120,
        ]);

        $status = $response->getStatusCode();
        $body = $response->getContent(false);

        if ($status === 404 || $status === 410) {
            throw new \RuntimeException(
                'API image Hugging Face indisponible pour ce modele/provider (HTTP ' . $status . '). ' .
                'Configurez HF_IMAGE_SCRIPT=scripts/hf_image.py pour forcer le mode script Python.'
            );
        }

        if ($status === 503) {
            $decoded = json_decode($body, true);
            $msg = $decoded['error'] ?? $decoded['estimated_time'] ?? $body;
            throw new \RuntimeException(
                'Modele en cours de chargement. Reessayez dans quelques secondes. ' .
                (is_string($msg) ? $msg : json_encode($msg))
            );
        }

        if ($status < 200 || $status >= 300) {
            $decoded = json_decode($body, true);
            $msg = is_array($decoded) ? ($decoded['error'] ?? $decoded['message'] ?? json_encode($decoded)) : $body;
            throw new \RuntimeException(sprintf(
                'Hugging Face API (%s): HTTP %d - %s',
                $modelId,
                $status,
                (string) $msg
            ));
        }

        if ($body === '') {
            throw new \RuntimeException('Hugging Face API returned an empty response.');
        }

        return [['base64' => base64_encode($body)]];
    }
}
