<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Debate;
use App\Models\Document;
use App\Services\Debate\DebateOrchestrator;
use App\Services\Presentation\PresentationService;
use App\Services\Rag\RagService;
use App\Services\Session\SessionService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Validation end-to-end des features cloud (LLM) sur un document DÉJÀ ingéré.
 * Exécute chat → présentation → débat avec les vrais providers configurés et
 * signale OK / échec par service. Utile après avoir renseigné les clés (.env).
 *
 *   php artisan bp:validate-cloud {document_id} --question="..."
 */
final class ValidateCloudCommand extends Command
{
    protected $signature = 'bp:validate-cloud {document : Id d\'un document indexé} {--question=Quelle est la trajectoire financière ?}';

    protected $description = 'Valide chat / présentation / débat (LLM réels) sur un document ingéré.';

    public function handle(
        SessionService $sessions,
        RagService $rag,
        PresentationService $presentations,
        DebateOrchestrator $orchestrator,
    ): int {
        $document = Document::find((int) $this->argument('document'));
        if ($document === null) {
            $this->error('Document introuvable.');

            return self::FAILURE;
        }

        if ($document->status->value !== 'indexed') {
            $this->warn("Le document n'est pas 'indexed' (statut : {$document->status->value}). Lancez l'ingestion d'abord.");
        }

        $question = (string) $this->option('question');
        $session = $sessions->start($document);
        $this->line("Document #{$document->id} · session {$session->uuid}");
        $this->newLine();

        // 1. Chat RAG (Groq)
        $this->step('Chat RAG (chat → Groq)', function () use ($rag, $session, $question): string {
            $interaction = $rag->answer($session, $question);
            $sources = count($interaction->meta['sources'] ?? []);

            return 'réponse '.mb_strlen((string) $interaction->answer)." car., {$sources} source(s)";
        });

        // 2. Présentation express (Groq)
        $this->step('Présentation express (presentation → Groq)', function () use ($presentations, $session, $question): string {
            $presentation = $presentations->create($session, $question);

            return count($presentation->script)." slide(s), durée {$presentation->duration_total}s";
        });

        // 3. Débat du board (Claude)
        $this->step('Débat du board (debate → Claude)', function () use ($orchestrator, $session, $question): string {
            $debate = Debate::create([
                'explorer_session_id' => $session->id,
                'document_id' => $session->document_id,
                'question' => $question,
                'status' => 'pending',
                'stop_condition' => ['max_rounds' => 1],
            ]);
            $orchestrator->run($debate);
            $flagged = $debate->turns->sum(
                fn ($turn) => collect($turn->verified_figures)->where('status', 'a_verifier')->count()
            );

            return $debate->turns()->count()." répliques, {$flagged} chiffre(s) signalé(s)";
        });

        $this->newLine();
        $this->info('Validation terminée. Les services en échec indiquent généralement une clé manquante dans .env.');

        return self::SUCCESS;
    }

    private function step(string $label, callable $run): void
    {
        try {
            $detail = $run();
            $this->line("  <fg=green>✓</> {$label} — {$detail}");
        } catch (Throwable $e) {
            $this->line("  <fg=red>✗</> {$label} — ".$e->getMessage());
        }
    }
}
