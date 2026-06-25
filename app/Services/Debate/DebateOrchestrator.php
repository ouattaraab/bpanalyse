<?php

declare(strict_types=1);

namespace App\Services\Debate;

use App\Events\DebateCompleted;
use App\Events\DebateTurnCreated;
use App\Models\Debate;
use App\Services\AI\LlmManager;
use App\Services\Audit\AuditLogger;
use App\Services\Document\Contracts\StructuredDataService;
use App\Services\Rag\Retriever;
use App\Services\Rag\SourceFormatter;

/**
 * Orchestrateur du débat (Epic 4) : boucle tour-par-tour sur les 4 personas.
 * Chaque réplique part du contexte sourcé et des CHIFFRES VÉRIFIÉS (injectés
 * pour empêcher l'invention de nombres), puis ses chiffres sont re-vérifiés
 * de façon déterministe (FinancialVerifier). Routage debate → Claude.
 *
 * Condition d'arrêt : nombre de tours (stop_condition.max_rounds, défaut 2).
 */
final class DebateOrchestrator
{
    public function __construct(
        private readonly Retriever $retriever,
        private readonly SourceFormatter $sources,
        private readonly StructuredDataService $financial,
        private readonly DebatePersonas $personas,
        private readonly FinancialVerifier $verifier,
        private readonly LlmManager $llm,
        private readonly AuditLogger $audit,
    ) {}

    public function run(Debate $debate): void
    {
        $debate->update(['status' => 'running']);

        $documentId = $debate->document_id;
        $question = $debate->question;

        $chunks = $this->retriever->retrieve($documentId, $question, 8);
        $context = $this->sources->buildContext($chunks);
        $sourceList = $this->sources->format($chunks);
        $figures = $this->verifiedFiguresBlock($documentId);

        $maxRounds = (int) ($debate->stop_condition['max_rounds'] ?? 2);
        $maxTurns = max(1, $maxRounds) * $this->personas->count();

        $client = $this->llm->for('debate');
        $transcript = [];

        for ($i = 0; $i < $maxTurns; $i++) {
            $persona = $this->personas->forTurn($i);

            $reply = trim($client->complete([
                ['role' => 'system', 'content' => $this->systemPrompt($persona, $context, $figures)],
                ['role' => 'user', 'content' => $this->userPrompt($question, $transcript)],
            ], ['temperature' => 0.4, 'max_tokens' => 500]));

            $turn = $debate->turns()->create([
                'turn_index' => $i,
                'persona' => $persona['key'],
                'persona_name' => $persona['name'],
                'content' => $reply,
                'sources' => $sourceList,
                'verified_figures' => $this->verifier->verify($documentId, $reply),
            ]);

            // Diffusion temps réel (Reverb) : la réplique s'affiche en direct.
            DebateTurnCreated::dispatch($turn);

            $transcript[] = "{$persona['name']} : {$reply}";
        }

        $debate->update(['status' => 'completed']);

        DebateCompleted::dispatch($debate);

        $this->audit->log($debate->session, null, 'debate', $question, $sourceList, $client->provider(), null);
    }

    private function verifiedFiguresBlock(int $documentId): string
    {
        $lines = [];
        foreach ($this->financial->query($documentId, 'list_metrics') as $metric) {
            foreach ($this->financial->query($documentId, 'get_metric', ['label' => $metric['label']]) as $row) {
                $unit = $row['unit'] ? " {$row['unit']}" : '';
                $lines[] = "- {$row['label']} ({$row['period_year']}) : {$row['value']}{$unit}";
            }
        }

        return $lines === [] ? '(aucun chiffre structuré disponible)' : implode("\n", $lines);
    }

    /** @param array{key:string, name:string, posture:string, prompt:string} $persona */
    private function systemPrompt(array $persona, string $context, string $figures): string
    {
        return implode("\n\n", [
            $persona['prompt'],
            'Règles : réponds en français, en 2 à 4 phrases. Cite tes sources (slide/section). '
                .'Tu ne calcules JAMAIS de chiffres et n\'utilises QUE les chiffres vérifiés ci-dessous ; '
                .'n\'invente aucun nombre.',
            "Chiffres vérifiés :\n".$figures,
            "Contexte (extraits sourcés) :\n".($context === '' ? '(aucun extrait)' : $context),
        ]);
    }

    /** @param array<int, string> $transcript */
    private function userPrompt(string $question, array $transcript): string
    {
        $history = $transcript === [] ? '(début du débat)' : implode("\n\n", $transcript);

        return "Question débattue : {$question}\n\nÉchanges précédents :\n{$history}\n\nÀ toi de prendre la parole.";
    }
}
