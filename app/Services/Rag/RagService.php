<?php

declare(strict_types=1);

namespace App\Services\Rag;

use App\Models\ExplorerSession;
use App\Models\Interaction;
use App\Services\AI\LlmManager;
use App\Services\Audit\AuditLogger;

/**
 * Chat RAG sourcé (story 2.1). Récupère les extraits pertinents, demande au LLM
 * (routage `chat` → Groq) une réponse FONDÉE UNIQUEMENT sur le contexte, cite
 * les sources et trace l'audit.
 *
 * Anti-hallucination : le prompt interdit tout calcul ; les chiffres présents
 * dans le contexte viennent des chunks de tableaux (valeurs verbatim). Le LLM
 * commente, il ne calcule pas.
 */
final class RagService
{
    public function __construct(
        private readonly Retriever $retriever,
        private readonly SourceFormatter $sources,
        private readonly LlmManager $llm,
        private readonly AuditLogger $audit,
    ) {
    }

    public function answer(ExplorerSession $session, string $question, int $k = 6): Interaction
    {
        $startedAt = microtime(true);

        $chunks = $this->retriever->retrieve($session->document_id, $question, $k);
        $context = $this->sources->buildContext($chunks);
        $sourceList = $this->sources->format($chunks);

        $client = $this->llm->for('chat');
        $answer = trim($client->complete([
            ['role' => 'system', 'content' => $this->systemPrompt()],
            ['role' => 'user', 'content' => $this->userPrompt($question, $context)],
        ], ['temperature' => 0.2, 'max_tokens' => 800]));

        $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);

        $interaction = Interaction::create([
            'explorer_session_id' => $session->id,
            'document_id' => $session->document_id,
            'role' => 'assistant',
            'mode' => 'chat',
            'question' => $question,
            'answer' => $answer,
            'meta' => ['sources' => $sourceList],
        ]);

        $this->audit->log($session, $interaction, 'chat', $question, $sourceList, $client->provider(), $latencyMs);

        return $interaction;
    }

    private function systemPrompt(): string
    {
        return implode(' ', [
            "Tu es l'assistant d'analyse d'un business plan, et tu réponds en français.",
            'Réponds UNIQUEMENT à partir du contexte fourni ci-dessous.',
            "Tu ne calcules JAMAIS de chiffres : n'utilise que les valeurs présentes telles quelles dans le contexte.",
            'Cite tes sources entre crochets en indiquant la slide et la section.',
            "Si l'information n'est pas dans le contexte, dis-le clairement plutôt que d'inventer.",
        ]);
    }

    private function userPrompt(string $question, string $context): string
    {
        $context = $context === '' ? '(aucun extrait pertinent trouvé)' : $context;

        return "Contexte (extraits sourcés du BP) :\n\n{$context}\n\n---\n\nQuestion : {$question}";
    }
}
