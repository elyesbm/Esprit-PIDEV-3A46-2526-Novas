<?php

namespace App\Service;

use App\Entity\CvProfile;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Smalot\PdfParser\Parser as PdfParser;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service d'analyse IA du CV.
 * Extrait le texte, interroge OpenAI GPT-4o-mini, parse la réponse JSON
 * et enrichit l'entité CvProfile avec l'ADN professionnel complet.
 */
class CvAnalysisService
{
    public function __construct(
        private EntityManagerInterface $em,
        private HttpClientInterface    $httpClient,
        private string                 $geminiApiKey
    ) {}

    /**
     * Analyse le CV et met à jour le CvProfile de l'utilisateur.
     */
    public function analyzeAndStore(User $user, string $filePath, string $fileName): CvProfile
    {
        // 1. Extraire le texte du PDF/DOCX
        $text = $this->extractText($filePath, $fileName);

        // 2. Interroger Gemini (avec le fichier natif si PDF/Image)
        $analysis = $this->analyzeWithAI($filePath, $fileName, $text);

        // 3. Mettre à jour ou créer le CvProfile
        $profile = $user->getCvProfile() ?? new CvProfile();
        $profile->setUser($user);
        $profile->setCvFilePath($filePath);
        $profile->setCvFileName($fileName);
        $profile->setAnalyzedAt(new \DateTime());

        // Scores
        $profile->setCvScore($analysis['cv_score'] ?? 0);
        $profile->setMaturityScore($analysis['maturity_score'] ?? 0);
        $profile->setCompetitivenessIndex($analysis['competitiveness_index'] ?? 0);

        // ADN professionnel
        $profile->setEstimatedLevel($analysis['estimated_level'] ?? 'junior');
        $profile->setMainDomain($analysis['main_domain'] ?? 'Non défini');
        $profile->setTechnicalSkills($analysis['technical_skills'] ?? []);
        $profile->setSoftSkills($analysis['soft_skills'] ?? []);
        $profile->setWeakPoints($analysis['weak_points'] ?? []);
        $profile->setRecommendations($analysis['recommendations'] ?? []);
        $profile->setEvolutionSimulation($analysis['evolution_simulation'] ?? []);
        $profile->setAiSummary($analysis['summary'] ?? '');
        $profile->setRawAnalysis($analysis);

        $this->em->persist($profile);
        $this->em->flush();

        return $profile;
    }

    /**
     * Extrait le texte du fichier CV.
     */
    private function extractText(string $filePath, string $fileName): string
    {
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($ext === 'pdf') {
            try {
                $parser = new PdfParser();
                $pdf    = $parser->parseFile($filePath);
                return $pdf->getText();
            } catch (\Throwable $e) {
                return "Erreur extraction PDF: " . $e->getMessage();
            }
        }

        if (in_array($ext, ['docx', 'doc'])) {
            return $this->extractDocxText($filePath);
        }

        // Fichier texte brut
        return file_get_contents($filePath) ?: '';
    }

    /**
     * Extrait le texte d'un fichier DOCX (ZIP avec XML).
     */
    private function extractDocxText(string $filePath): string
    {
        try {
            $zip = new \ZipArchive();
            if ($zip->open($filePath) !== true) {
                return '';
            }
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            if (!$xml) return '';

            $dom = new \DOMDocument();
            @$dom->loadXML($xml);
            $text = '';
            foreach ($dom->getElementsByTagNameNS('*', 't') as $node) {
                $text .= $node->nodeValue . ' ';
            }
            return trim($text);
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Interroge l'IA Gemini (Google AI Studio) pour analyser le CV.
     *
     * @return array<string, mixed>
     */
    private function analyzeWithAI(string $filePath, string $fileName, string $cvText): array
    {
        if (empty($this->geminiApiKey) || $this->geminiApiKey === 'votre_cle_gemini_ici') {
            return $this->getFallbackAnalysis($cvText, "Clé API manquante ou non configurée dans .env.local");
        }

        $prompt = <<<PROMPT
Tu es un expert RH Senior et spécialiste du recrutement technique. Analyse le document joint qui est un CV.
RETOURNE EXCLUSIVEMENT UN OBJET JSON VALIDE. NE PAS AJOUTER DE TEXTE AVANT OU APRÈS.
LE JSON DOIT ÊTRE EN FRANÇAIS.

STRUCTURE JSON ATTENDUE :
{
  "summary": "Résumé en 3 phrases maximum",
  "estimated_level": "junior|mid|senior|expert",
  "main_domain": "Le domaine d'expertise principal",
  "technical_skills": ["liste des hard skills"],
  "soft_skills": ["liste des soft skills"],
  "weak_points": ["points d'amélioration identifiés"],
  "cv_score": 0-100,
  "maturity_score": 0-100,
  "competitiveness_index": 0-100,
  "recommendations": [
    {"priority": "haute|moyenne|faible", "action": "action concrète", "impact": "description de l'impact sur le score"}
  ],
  "evolution_simulation": [
    {"scenario": "nom du scénario", "current_score": 0, "predicted_score": 0, "timeline": "durée"}
  ]
}
PROMPT;

        $parts = [['text' => $prompt]];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Gemini Vision OCR supporte PDF et Images nativement
        $supportedMimeTypes = [
            'pdf'  => 'application/pdf',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'heic' => 'image/heic',
            'heif' => 'image/heif',
        ];

        if (file_exists($filePath) && isset($supportedMimeTypes[$ext])) {
            $mimeType = $supportedMimeTypes[$ext];
            $fileBinary = file_get_contents($filePath);
            if ($fileBinary === false) {
                return $this->getFallbackAnalysis($cvText, 'Impossible de lire le fichier CV.');
            }
            $base64Data = base64_encode($fileBinary);
            $parts[] = [
                'inlineData' => [
                    'mimeType' => $mimeType,
                    'data'     => $base64Data
                ]
            ];
        } else {
            // Pour les DOCX ou fichiers texte, on utilise l'extracteur PHP
            $cvText = trim($cvText);
            if (empty($cvText)) {
                return $this->getFallbackAnalysis($cvText, "Le contenu du CV n'a pas pu être lu. Veuillez fournir un PDF.");
            }
            $cvText = mb_substr($cvText, 0, 30000);
            $parts[] = ['text' => "TXT DU CV :\n" . $cvText];
        }

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $this->geminiApiKey;

            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'contents' => [
                        [
                            'parts' => $parts
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'topP' => 0.8,
                        'topK' => 40,
                        'responseMimeType' => 'application/json',
                    ]
                ],
                'timeout' => 45,
            ]);


            $statusCode = $response->getStatusCode();
            $body = $response->toArray(false);

            if ($statusCode !== 200) {
                $errorMsg = $body['error']['message'] ?? json_encode($body);
                return $this->getFallbackAnalysis($cvText, "Erreur API Gemini ($statusCode) : $errorMsg");
            }

            $content = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;
            
            if (!$content) {
                return $this->getFallbackAnalysis($cvText, "Réponse vide de Gemini");
            }

            $content = trim($content);
            $content = preg_replace('/^```json\s*|\s*```$/i', '', $content) ?? $content;
            $data = json_decode($content, true);

            if (!is_array($data)) {
                return $this->getFallbackAnalysis($cvText, "JSON invalide reçu de l'IA");
            }

            $data['_source'] = 'gemini';
            return $data;

        } catch (\Throwable $e) {
            return $this->getFallbackAnalysis($cvText, "Exception : " . $e->getMessage());
        }
    }

    /**
     * Analyse de secours basée sur des mots-clés si l'IA n'est pas disponible.
     *
     * @return array<string, mixed>
     */
    private function getFallbackAnalysis(string $cvText, ?string $errorReason = null): array
    {
        $text = strtolower($cvText);

        // Détection de compétences techniques par mots-clés
        $techKeywords = [
            'PHP', 'Symfony', 'Laravel', 'JavaScript', 'TypeScript', 'React', 'Vue', 'Angular',
            'Python', 'Django', 'Flask', 'Java', 'Spring', 'Node.js', 'Express',
            'MySQL', 'PostgreSQL', 'MongoDB', 'Redis', 'Docker', 'Kubernetes',
            'AWS', 'Azure', 'GCP', 'Git', 'Linux', 'CI/CD', 'REST', 'GraphQL',
            'HTML', 'CSS', 'Tailwind', 'Bootstrap', 'Figma', 'Photoshop',
            'TensorFlow', 'PyTorch', 'Scikit-learn', 'Pandas', 'NumPy',
        ];

        $foundTech = [];
        foreach ($techKeywords as $skill) {
            if (str_contains($text, strtolower($skill))) {
                $foundTech[] = $skill;
            }
        }

        $softKeywords = ['communication', 'équipe', 'leadership', 'autonomie', 'créativité', 'organisation', 'adaptabilité', 'rigueur'];
        $foundSoft    = [];
        foreach ($softKeywords as $skill) {
            if (str_contains($text, $skill)) {
                $foundSoft[] = ucfirst($skill);
            }
        }

        // Score basé sur le nombre de compétences trouvées
        $techCount             = count($foundTech);
        $cvScore               = min(40 + $techCount * 3, 75);
        $maturityScore         = min(35 + $techCount * 2, 65);
        $competitivenessIndex  = min(30 + $techCount * 2.5, 60);

        // Niveau estimé
        $level = match (true) {
            $techCount >= 12 => 'senior',
            $techCount >= 7  => 'mid',
            default          => 'junior',
        };

        return [
            '_source'               => 'fallback',
            'summary'               => $errorReason ? "ÉCHEC IA : $errorReason" : 'Profil analysé par détection de mots-clés (analyse IA non disponible — vérifiez votre clé Gemini dans .env.local).',
            'estimated_level'       => $level,
            'main_domain'           => 'Développement',
            'technical_skills'      => array_slice($foundTech, 0, 10),
            'soft_skills'           => array_slice($foundSoft, 0, 5) ?: ['Travail en équipe', 'Communication'],
            'weak_points'           => [
                $errorReason ?: 'Analyse IA non disponible (vérifiez votre clé Gemini dans .env.local)',
                'Résultats basés uniquement sur la détection de mots-clés',
            ],
            'cv_score'              => (int) $cvScore,
            'maturity_score'        => (int) $maturityScore,
            'competitiveness_index' => (int) $competitivenessIndex,
            'recommendations'       => [
                ['priority' => 'haute',   'action' => 'Ajouter des projets concrets avec liens GitHub',              'impact' => '+8 points CV'],
                ['priority' => 'moyenne', 'action' => 'Obtenir une certification reconnue dans votre domaine',        'impact' => '+10 points compétitivité'],
                ['priority' => 'faible',  'action' => 'Améliorer la mise en forme avec un template ATS-friendly',    'impact' => '+3 points'],
            ],
            'evolution_simulation'  => [
                ['scenario' => 'Obtenir une certification',    'current_score' => (int) $cvScore, 'predicted_score' => min((int) $cvScore + 12, 100), 'timeline' => '3 mois'],
                ['scenario' => 'Ajouter 2 projets open source', 'current_score' => (int) $cvScore, 'predicted_score' => min((int) $cvScore + 8,  100), 'timeline' => '2 mois'],
                ['scenario' => 'Profil complet',               'current_score' => (int) $cvScore, 'predicted_score' => min((int) $cvScore + 23, 100), 'timeline' => '6 mois'],
            ],
        ];
    }
}
