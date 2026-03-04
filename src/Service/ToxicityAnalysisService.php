<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Toxicity analysis using Hugging Face Toxic-BERT.
 * - If HF_TOXICITY_SCRIPT is configured, use Python helper script.
 * - Otherwise, call HTTP inference endpoint.
 */
class ToxicityAnalysisService
{
    private const DEFAULT_API_URL = 'https://router.huggingface.co/hf-inference/models/unitary/toxic-bert';
    private const TOXICITY_THRESHOLD = 0.5;
    private const TOXIC_LABELS = ['toxic', 'severe_toxic', 'threat', 'insult', 'obscene', 'identity_hate'];

    public function __construct(
        private HttpClientInterface $httpClient,
        private ?string $hfToken = null,
        private ?string $inferenceUrl = null,
        private ?string $scriptPath = null,
        private string $projectDir = ''
    ) {
    }

    /**
     * Returns true if text is toxic.
     */
    public function isToxic(string $text): bool
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return false;
        }

        if (empty(trim($this->hfToken ?? '')) && $this->resolveScriptPath() === null) {
            return false;
        }

        try {
            $scores = $this->analyze($trimmed);
            return $this->isToxicFromScores($scores);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            throw new \RuntimeException(
                'Verification de toxicite indisponible. ' . ($msg ?: 'Reessayez plus tard.'),
                0,
                $e
            );
        }
    }

    /**
     * Source used (URL or script) for debug/testing.
     */
    public function getApiUrl(): string
    {
        if ($this->resolveScriptPath() !== null) {
            return 'script: ' . trim($this->scriptPath ?? '');
        }

        return $this->resolveApiUrl();
    }

    /**
     * @return array<array{label: string, score: float}>
     */
    public function getToxicityScores(string $text): array
    {
        $trimmed = trim($text);
        if ($trimmed === '' || (empty(trim($this->hfToken ?? '')) && $this->resolveScriptPath() === null)) {
            return [];
        }

        try {
            return $this->analyze($trimmed);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<array{label: string, score: float}>
     */
    private function analyze(string $text): array
    {
        $script = $this->resolveScriptPath();
        if ($script !== null) {
            try {
                return $this->runScript($script, $text);
            } catch (\Throwable $e) {
                if (empty(trim($this->hfToken ?? ''))) {
                    throw $e;
                }

                return $this->callApi($text);
            }
        }

        return $this->callApi($text);
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
     * @return array<array{label: string, score: float}>
     */
    private function runScript(string $scriptPath, string $text): array
    {
        $env = array_merge(getenv(), ['HF_TOKEN' => $this->hfToken ?? '']);
        $proc = proc_open(
            [PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3', $scriptPath],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            $env,
            ['bypass_shell' => true]
        );

        if (!\is_resource($proc)) {
            throw new \RuntimeException('Unable to start toxicity helper script.');
        }

        fwrite($pipes[0], $text);
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $code = proc_close($proc);
        if ($code !== 0 && $stdout === '') {
            throw new \RuntimeException('Toxicity script error: ' . ($stderr ?: "exit $code"));
        }

        $data = json_decode($stdout, true);
        if (!\is_array($data)) {
            throw new \RuntimeException('Toxicity script returned invalid JSON.');
        }
        if (isset($data['error'])) {
            throw new \RuntimeException('Toxicity script error: ' . $data['error']);
        }

        return $this->normalizeScores($data);
    }

    private function resolveApiUrl(): string
    {
        $url = trim($this->inferenceUrl ?? '');
        if ($url === '') {
            return self::DEFAULT_API_URL;
        }

        // Backward compatibility for old/deprecated Hugging Face endpoints.
        $url = preg_replace(
            '#^https://api-inference\.huggingface\.co/models/(.+)$#i',
            'https://router.huggingface.co/hf-inference/models/$1',
            $url
        ) ?? $url;
        $url = preg_replace(
            '#^https://router\.huggingface\.co/models/(.+)$#i',
            'https://router.huggingface.co/hf-inference/models/$1',
            $url
        ) ?? $url;

        return $url;
    }

    /**
     * @return array<array{label: string, score: float}>
     */
    private function callApi(string $text): array
    {
        $url = $this->resolveApiUrl();
        $token = trim($this->hfToken ?? '');

        $response = $this->httpClient->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'json' => ['inputs' => $text],
            'timeout' => 20,
        ]);

        $status = $response->getStatusCode();
        $body = $response->getContent(false);

        if ($status === 401) {
            throw new \RuntimeException('Invalid Hugging Face token or missing permissions.');
        }

        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException('Hugging Face API: HTTP ' . $status . ' - ' . substr((string) $body, 0, 200));
        }

        $data = json_decode($body, true);
        if (!\is_array($data)) {
            throw new \RuntimeException('Hugging Face API returned invalid JSON.');
        }

        if (isset($data['error'])) {
            throw new \RuntimeException((string) $data['error']);
        }

        return $this->normalizeScores($data);
    }

    /**
     * @param mixed $data
     * @return array<array{label: string, score: float}>
     */
    private function normalizeScores(mixed $data): array
    {
        if (!\is_array($data)) {
            return [];
        }

        // HF often returns nested arrays: [[{label, score}, ...]]
        if (isset($data[0]) && \is_array($data[0]) && isset($data[0][0]) && \is_array($data[0][0])) {
            $data = $data[0];
        }

        $result = [];
        foreach ($data as $item) {
            if (\is_array($item) && isset($item['label'], $item['score'])) {
                $result[] = [
                    'label' => (string) $item['label'],
                    'score' => (float) $item['score'],
                ];
            }
        }

        return $result;
    }

    /**
     * @param array<array{label: string, score: float}> $scores
     */
    private function isToxicFromScores(array $scores): bool
    {
        foreach ($scores as $item) {
            $label = strtolower((string) ($item['label'] ?? ''));
            $score = (float) ($item['score'] ?? 0);

            if ($score >= self::TOXICITY_THRESHOLD && \in_array($label, self::TOXIC_LABELS, true)) {
                return true;
            }
        }

        return false;
    }

}
