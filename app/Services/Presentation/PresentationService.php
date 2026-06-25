<?php

declare(strict_types=1);

namespace App\Services\Presentation;

use App\Models\ExplorerSession;
use App\Models\Presentation;
use App\Services\AI\LlmManager;
use App\Services\Audit\AuditLogger;

/**
 * Orchestration de la présentation express (Epic 3) : sélection des slides,
 * génération du script narré, persistance et audit.
 */
final class PresentationService
{
    public function __construct(
        private readonly SlideSelector $selector,
        private readonly NarrationGenerator $narrator,
        private readonly AuditLogger $audit,
        private readonly LlmManager $llm,
    ) {}

    public function create(ExplorerSession $session, string $question): Presentation
    {
        $startedAt = microtime(true);

        $slides = $this->selector->select($session->document_id, $question);
        $script = $this->narrator->generate($question, $slides);

        $presentation = Presentation::create([
            'explorer_session_id' => $session->id,
            'document_id' => $session->document_id,
            'question' => $question,
            'script' => $script,
            'status' => 'ready',
            'duration_total' => array_sum(array_column($script, 'duree')),
        ]);

        $sources = $slides->map(static fn ($slide): array => [
            'slide_id' => $slide->id,
            'slide_index' => $slide->slide_index,
            'section' => $slide->section,
        ])->all();

        $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);

        $this->audit->log(
            $session,
            null,
            'presentation',
            $question,
            $sources,
            $this->llm->resolveProviderKey('presentation'),
            $latencyMs,
        );

        return $presentation;
    }
}
