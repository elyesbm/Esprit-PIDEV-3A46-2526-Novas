<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeocodingService
{
    private string $endpoint;

    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire('%env(default::NOMINATIM_URL)%')]
        ?string $nominatimUrl = null
    ) {
        $this->endpoint = trim((string) $nominatimUrl);
        if ($this->endpoint === '') {
            $this->endpoint = 'https://nominatim.openstreetmap.org/search';
        }
    }

    /**
     * @return array{lat: float, lon: float, display_name: string}|null
     */
    public function geocode(string $adresse): ?array
    {
        $adresse = trim($adresse);
        if ($adresse === '') {
            return null;
        }

        $response = $this->httpClient->request('GET', $this->endpoint, [
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'NoVasJobs/1.0 (symfony-app)',
            ],
            'query' => [
                'q' => $adresse,
                'format' => 'json',
                'limit' => 1,
                'addressdetails' => 1,
            ],
            'timeout' => 15,
        ]);

        $status = $response->getStatusCode();
        if ($status >= 400) {
            throw new \RuntimeException('Le service de geocodage est indisponible.');
        }

        $payload = $response->toArray(false);
        if (!isset($payload[0]) || !is_array($payload[0])) {
            return null;
        }

        $first = $payload[0];
        $lat = isset($first['lat']) ? (float) $first['lat'] : null;
        $lon = isset($first['lon']) ? (float) $first['lon'] : null;
        $displayName = isset($first['display_name']) ? trim((string) $first['display_name']) : '';

        if ($lat === null || $lon === null) {
            return null;
        }

        return [
            'lat' => $lat,
            'lon' => $lon,
            'display_name' => $displayName,
        ];
    }
}

