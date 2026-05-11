<?php

namespace App\Service;

class PublicationTranslationService
{
    public function __construct(private ExternalTranslator $translator)
    {
    }

    /**
     * @return array{title: string, content: string}
     */
    public function translatePublication(string $title, string $content, string $target, string $source = 'fr'): array
    {
        return [
            'title' => $this->translator->translate($title, $target, $source),
            'content' => $this->translator->translate($content, $target, $source),
        ];
    }
}
