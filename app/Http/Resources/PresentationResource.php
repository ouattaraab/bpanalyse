<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\DocumentSlide;
use App\Models\Presentation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Présentation prête à jouer : le script (slide_id, narration, durée) est
 * enrichi du contenu de chaque slide pour l'affichage (front Reveal.js).
 *
 * @mixin Presentation
 */
class PresentationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $script = $this->script ?? [];

        $slides = DocumentSlide::query()
            ->whereIn('id', array_column($script, 'slide_id'))
            ->get()
            ->keyBy('id');

        return [
            'id' => $this->id,
            'question' => $this->question,
            'status' => $this->status,
            'duration_total' => $this->duration_total,
            'slides' => array_map(static function (array $item) use ($slides): array {
                /** @var DocumentSlide|null $slide */
                $slide = $slides->get($item['slide_id']);

                return [
                    'slide_id' => $item['slide_id'],
                    'slide_index' => $slide?->slide_index,
                    'title' => $slide?->title,
                    'section' => $slide?->section,
                    'markdown' => $slide?->raw_markdown,
                    'narration' => $item['narration'],
                    'duree' => $item['duree'],
                ];
            }, $script),
        ];
    }
}
