<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Skill;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service pour récupérer les statistiques marché des skills via l'API Adzuna.
 * Si les clés API ne sont pas configurées, utilise des données simulées.
 */
class SkillMarketStatsService
{
    private const ADZUNA_BASE_URL = 'https://api.adzuna.com/v1/api/jobs';
    private const COUNTRY_FR = 'fr';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $adzunaAppId = null,
        private readonly ?string $adzunaAppKey = null,
    ) {
    }

    /**
     * Met à jour les statistiques marché d'un skill.
     *
     * @return array{nombre_offres: int, score_demande: int, tendance: string}|null
     */
    public function fetchAndUpdateSkillStats(Skill $skill): ?array
    {
        $stats = $this->fetchJobCountForKeyword((string) $skill->getNomSkill());

        if ($stats === null) {
            return null;
        }

        $skill->setNombreOffresAssociees($stats['nombre_offres']);
        $skill->setScoreDemande($stats['score_demande']);
        $skill->setTendanceMarche($stats['tendance']);
        $skill->setDateMiseAJourStats(new \DateTime());

        return $stats;
    }

    /**
     * Récupère le nombre d'offres d'emploi pour un mot-clé.
     *
     * @return array{nombre_offres: int, score_demande: int, tendance: string}|null
     */
    public function fetchJobCountForKeyword(string $keyword): ?array
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return null;
        }

        if ($this->adzunaAppId && $this->adzunaAppKey) {
            return $this->fetchFromAdzunaApi($keyword);
        }

        return $this->getMockStats($keyword);
    }

    /**
     * Appel à l'API Adzuna (France).
     *
     * @return array{nombre_offres: int, score_demande: int, tendance: string}
     */
    private function fetchFromAdzunaApi(string $keyword): array
    {
        try {
            $url = sprintf(
                '%s/%s/search/1?app_id=%s&app_key=%s&what=%s&results_per_page=1',
                self::ADZUNA_BASE_URL,
                self::COUNTRY_FR,
                $this->adzunaAppId,
                $this->adzunaAppKey,
                rawurlencode($keyword),
            );

            $response = $this->httpClient->request('GET', $url);
            $data = $response->toArray();

            $count = (int) ($data['count'] ?? 0);

            return [
                'nombre_offres' => $count,
                'score_demande' => $this->computeScoreDemande($count),
                'tendance' => $this->computeTendance($count),
            ];
        } catch (\Throwable) {
            return $this->getMockStats($keyword);
        }
    }

    /**
     * Données simulées pour le développement (quand pas de clé API).
     *
     * @return array{nombre_offres: int, score_demande: int, tendance: string}
     */
    private function getMockStats(string $keyword): array
    {
        $hash = crc32(strtolower($keyword));
        $baseCount = abs($hash % 1500) + 50;

        return [
            'nombre_offres' => $baseCount,
            'score_demande' => $this->computeScoreDemande($baseCount),
            'tendance' => $this->computeTendance($baseCount),
        ];
    }

    private function computeScoreDemande(int $count): int
    {
        if ($count >= 1000) {
            return 5;
        }
        if ($count >= 500) {
            return 4;
        }
        if ($count >= 200) {
            return 3;
        }
        if ($count >= 50) {
            return 2;
        }
        return 1;
    }

    private function computeTendance(int $count): string
    {
        if ($count >= 500) {
            return 'en_hausse';
        }
        if ($count >= 100) {
            return 'stable';
        }
        return 'en_baisse';
    }

    /**
     * Vérifie si l'API Adzuna est configurée.
     */
    public function isApiConfigured(): bool
    {
        return !empty($this->adzunaAppId) && !empty($this->adzunaAppKey);
    }
}
