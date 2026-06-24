<?php

declare(strict_types=1);

namespace App\Services\Presentation;

use App\Models\DocumentSlide;
use App\Services\AI\LlmManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Génère le script narré [{slide_id, narration, duree}] (story 3.2).
 * Routage `presentation` → Groq (narration rapide). La durée est calculée
 * DÉTERMINISTEMENT (mots / débit), jamais demandée au LLM.
 *
 * Anti-hallucination : le prompt interdit tout calcul ; le LLM commente le
 * contenu des slides, il n'invente pas de chiffres.
 */
final class NarrationGenerator
{
    /** Débit de narration approximatif (mots / seconde). */
    private const WORDS_PER_SECOND = 2.5;

    public function __construct(private readonly LlmManager $llm)
    {
    }

    /**
     * @param  Collection<int, DocumentSlide>  $slides
     * @return array<int, array{slide_id:int, narration:string, duree:int}>
     */
    public function generate(string $question, Collection $slides): array
    {
        if ($slides->isEmpty()) {
            return [];
        }

        $raw = $this->llm->for('presentation')->complete([
            ['role' => 'system', 'content' => $this->systemPrompt()],
            ['role' => 'user', 'content' => $this->userPrompt($question, $slides)],
        ], ['temperature' => 0.3, 'max_tokens' => 1200]);

        $byId = $this->parse($raw);

        return $slides->map(function (DocumentSlide $slide) use ($byId): array {
            $narration = $byId[$slide->id] ?? $this->fallbackNarration($slide);

            return [
                'slide_id' => $slide->id,
                'narration' => $narration,
                'duree' => $this->estimateDuration($narration),
            ];
        })->all();
    }

    /**
     * @return array<int, string>  slide_id => narration
     */
    private function parse(string $raw): array
    {
        // Isole le tableau JSON même s'il est entouré de texte ou de fences ```json.
        if (preg_match('/\[.*\]/s', $raw, $m) !== 1) {
            return [];
        }

        $decoded = json_decode($m[0], true);
        if (! is_array($decoded)) {
            return [];
        }

        $map = [];
        foreach ($decoded as $item) {
            if (is_array($item) && isset($item['slide_id'], $item['narration'])) {
                $map[(int) $item['slide_id']] = trim((string) $item['narration']);
            }
        }

        return $map;
    }

    private function estimateDuration(string $narration): int
    {
        $words = max(1, str_word_count(strip_tags($narration)));

        return (int) max(4, (int) ceil($words / self::WORDS_PER_SECOND));
    }

    private function fallbackNarration(DocumentSlide $slide): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($slide->raw_markdown)) ?? '');

        return $text === ''
            ? 'Cette slide complète la présentation.'
            : Str::limit($text, 180);
    }

    private function systemPrompt(): string
    {
        return implode(' ', [
            'Tu es un narrateur de présentation en français.',
            'Pour CHAQUE slide fournie, rédige 2 à 3 phrases de narration claires, en lien avec la question.',
            "Tu ne calcules JAMAIS de chiffres : commente uniquement les valeurs présentes dans la slide.",
            'Réponds STRICTEMENT en JSON : un tableau d\'objets {"slide_id": <entier>, "narration": "<texte>"}.',
        ]);
    }

    /** @param Collection<int, DocumentSlide> $slides */
    private function userPrompt(string $question, Collection $slides): string
    {
        $blocks = $slides->map(static function (DocumentSlide $slide): string {
            $content = Str::limit(trim($slide->raw_markdown), 600);

            return "Slide id={$slide->id} (#{$slide->slide_index})\n{$content}";
        })->implode("\n\n");

        return "Question : {$question}\n\nSlides :\n\n{$blocks}";
    }
}
