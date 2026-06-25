<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ChunkType;
use App\Models\Chunk;
use App\Models\Document;
use App\Services\Ingestion\FinancialTableExtractor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Extrait les tableaux financiers (chunks de type `table` contenant des valeurs)
 * vers financial_tables + financial_metrics. Étape déterministe : aucun LLM.
 * Les tableaux sans cellule numérique sont ignorés.
 */
final class ExtractFinancialsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $documentId) {}

    public function handle(FinancialTableExtractor $extractor): void
    {
        $document = Document::findOrFail($this->documentId);

        $tableChunks = $document->chunks()
            ->where('type', ChunkType::Table->value)
            ->with('slide')
            ->get();

        DB::transaction(function () use ($document, $tableChunks, $extractor): void {
            // Réindexation : on repart de zéro (cascade sur les metrics).
            $document->financialTables()->delete();

            foreach ($tableChunks as $chunk) {
                /** @var Chunk $chunk */
                $metrics = $extractor->extractMetrics($chunk->content);

                if ($metrics === []) {
                    continue; // tableau non financier
                }

                $financialTable = $document->financialTables()->create([
                    'document_slide_id' => $chunk->document_slide_id,
                    'chunk_id' => $chunk->id,
                    'name' => $chunk->section,
                    'caption' => $chunk->caption,
                    'raw_markdown' => $chunk->content,
                ]);

                $sourceRef = [
                    'slide_id' => $chunk->document_slide_id,
                    'slide_index' => $chunk->slide?->slide_index,
                    'chunk_id' => $chunk->id,
                    'section' => $chunk->section,
                ];

                foreach ($metrics as $metric) {
                    $financialTable->metrics()->create([
                        'document_id' => $document->id,
                        'label' => $metric->label,
                        'period_label' => $metric->periodLabel,
                        'period_year' => $metric->periodYear,
                        'value' => $metric->value,
                        'unit' => $metric->unit,
                        'source_ref' => $sourceRef,
                    ]);
                }
            }
        });
    }
}
