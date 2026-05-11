<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;

/**
 * Compare un descripteur facial (128 floats, face-api.js) avec les utilisateurs
 * ayant un encodage enregistré.
 * Seuil plus permissif (0.75) : photo d'inscription vs caméra à la connexion donne souvent une distance > 0.6.
 */
class FaceRecognitionService
{
    /** Distance euclidienne max pour considérer une correspondance (photo vs caméra = plus de variance) */
    private const MATCH_THRESHOLD = 0.85;

    public function __construct(
        private UserRepository $userRepository
    ) {
    }

    /**
     * Trouve l'utilisateur dont l'encodage facial correspond au descripteur fourni.
     *
     * @param list<float|int> $descriptor
     */
    public function findUserByDescriptor(array $descriptor): ?User
    {
        $descriptor = $this->normalizeDescriptor($descriptor);
        if ($descriptor === null) {
            return null;
        }

        $users = $this->userRepository->findWithFaceEncoding();
        $bestUser = null;
        $bestDistance = self::MATCH_THRESHOLD;

        foreach ($users as $user) {
            $encoding = $user->getFaceEncoding();
            $encoding = $this->normalizeDescriptor($encoding);
            if ($encoding === null) {
                continue;
            }
            $distance = $this->euclideanDistance($descriptor, $encoding);
            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $bestUser = $user;
            }
        }

        return $bestUser;
    }

    /**
     * Force un tableau indexé 0..127 de floats (JSON/DB peuvent donner des clés string).
     *
     * @param array<int|string, float|int>|null $descriptor
     * @return list<float>|null
     */
    private function normalizeDescriptor(?array $descriptor): ?array
    {
        if ($descriptor === null || \count($descriptor) !== 128) {
            return null;
        }
        $out = [];
        for ($i = 0; $i < 128; $i++) {
            $out[$i] = (float) ($descriptor[$i] ?? $descriptor[(string) $i] ?? 0);
        }
        return array_values($out);
    }

    /**
     * @param list<float> $a
     * @param list<float> $b
     */
    private function euclideanDistance(array $a, array $b): float
    {
        $sum = 0.0;
        for ($i = 0; $i < 128; $i++) {
            $d = (float) ($a[$i] ?? 0) - (float) ($b[$i] ?? 0);
            $sum += $d * $d;
        }
        return sqrt($sum);
    }
}
