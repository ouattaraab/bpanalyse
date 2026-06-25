<?php

declare(strict_types=1);

namespace App\Services\Ingestion;

use App\Enums\ChunkType;
use App\Services\Ingestion\Data\ParsedChunk;

/**
 * Découpage sémantique du Markdown d'une slide.
 *
 * RÈGLE ABSOLUE : un tableau = un chunk (jamais scindé), accompagné de sa
 * légende (la ligne de texte qui le précède). Le texte est découpé par section
 * (titres Markdown). Ce service est déterministe et ne fait appel à aucun LLM.
 */
final class SemanticChunker
{
    /**
     * @return array<int, ParsedChunk>
     */
    public function chunkMarkdown(string $markdown, ?string $defaultSection = null): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $markdown) ?: [];
        $count = count($lines);

        $chunks = [];
        $textBuffer = [];
        $currentSection = $defaultSection;

        $flushText = function () use (&$chunks, &$textBuffer, &$currentSection): void {
            $content = trim(implode("\n", $textBuffer));
            $textBuffer = [];
            if ($content !== '') {
                $chunks[] = new ParsedChunk(ChunkType::Text, $content, $currentSection);
            }
        };

        for ($i = 0; $i < $count; $i++) {
            $line = $lines[$i];

            // Titre de section : délimite un nouveau bloc de texte.
            if (preg_match('/^#{1,6}\s+(.*)$/', trim($line), $m) === 1) {
                $flushText();
                $currentSection = trim($m[1]);

                continue;
            }

            // Début de tableau : ligne avec pipes suivie d'une ligne de séparation.
            if ($this->rowHasPipe($line) && isset($lines[$i + 1]) && $this->isSeparatorRow($lines[$i + 1])) {
                // La légende = dernière ligne de texte non vide accumulée.
                $caption = $this->popCaption($textBuffer);
                $flushText();

                [$tableMarkdown, $i] = $this->collectTable($lines, $i, $count);

                $content = $caption !== null ? $caption."\n\n".$tableMarkdown : $tableMarkdown;
                $chunks[] = new ParsedChunk(ChunkType::Table, $content, $currentSection, $caption);

                continue;
            }

            $textBuffer[] = $line;
        }

        $flushText();

        return $chunks;
    }

    /**
     * Retire et renvoie la dernière ligne non vide du buffer (la légende).
     *
     * @param  array<int, string>  $buffer
     */
    private function popCaption(array &$buffer): ?string
    {
        for ($j = count($buffer) - 1; $j >= 0; $j--) {
            if (trim($buffer[$j]) !== '') {
                $caption = trim($buffer[$j]);
                array_splice($buffer, $j);

                return $caption;
            }
        }

        return null;
    }

    /**
     * Collecte les lignes du tableau à partir de l'index $start.
     *
     * @param  array<int, string>  $lines
     * @return array{0:string, 1:int} [markdown du tableau, index de la dernière ligne consommée]
     */
    private function collectTable(array $lines, int $start, int $count): array
    {
        // En-tête + ligne de séparation, garantis.
        $table = [$lines[$start], $lines[$start + 1]];
        $i = $start + 2;

        while ($i < $count && $this->rowHasPipe($lines[$i]) && trim($lines[$i]) !== '') {
            $table[] = $lines[$i];
            $i++;
        }

        return [implode("\n", $table), $i - 1];
    }

    private function rowHasPipe(string $line): bool
    {
        return str_contains($line, '|');
    }

    private function isSeparatorRow(string $line): bool
    {
        $trimmed = trim($line);

        return $trimmed !== ''
            && str_contains($trimmed, '-')
            && preg_match('/^[\s|:\-]+$/', $trimmed) === 1;
    }
}
