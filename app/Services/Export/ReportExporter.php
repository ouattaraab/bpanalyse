<?php

declare(strict_types=1);

namespace App\Services\Export;

use App\Models\ExplorerSession;
use App\Models\PinnedItem;
use App\Services\Session\PinService;
use Dompdf\Dompdf;
use InvalidArgumentException;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

/**
 * Compte rendu one-shot : synthèse des réponses épinglées d'une session,
 * exportée en DOCX (PhpWord) ou PDF (Dompdf). L'utilisateur repart avec son livrable.
 */
final class ReportExporter
{
    public function __construct(private readonly PinService $pins) {}

    /**
     * @return array{0:string, 1:string, 2:string} [chemin du fichier, nom de téléchargement, mime]
     */
    public function generate(ExplorerSession $session, string $format): array
    {
        return match ($format) {
            'docx' => $this->docx($session),
            'pdf' => $this->pdf($session),
            default => throw new InvalidArgumentException("Format d'export non supporté : {$format}"),
        };
    }

    /** @return array{0:string, 1:string, 2:string} */
    private function docx(ExplorerSession $session): array
    {
        $phpWord = new PhpWord;
        $docSection = $phpWord->addSection();
        $docSection->addTitle('Compte rendu — BP Explorer', 1);
        $docSection->addText('Session : '.$session->uuid);

        foreach ($this->items($session) as $index => $item) {
            $docSection->addTitle('Q'.($index + 1).' — '.$item['question'], 2);
            $docSection->addText($item['answer']);
            if ($item['note']) {
                $docSection->addText('Note : '.$item['note'], ['italic' => true]);
            }
        }

        if ($this->items($session) === []) {
            $docSection->addText('Aucune réponse épinglée.');
        }

        $path = $this->tempPath('docx');
        IOFactory::createWriter($phpWord, 'Word2007')->save($path);

        return [$path, 'compte-rendu.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    }

    /** @return array{0:string, 1:string, 2:string} */
    private function pdf(ExplorerSession $session): array
    {
        $rows = '';
        foreach ($this->items($session) as $index => $item) {
            $note = $item['note'] ? '<p><em>Note : '.e($item['note']).'</em></p>' : '';
            $rows .= '<h2>Q'.($index + 1).' — '.e($item['question']).'</h2>'
                .'<p>'.nl2br(e($item['answer'])).'</p>'.$note;
        }
        if ($rows === '') {
            $rows = '<p>Aucune réponse épinglée.</p>';
        }

        $html = '<html><head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,sans-serif;}'
            .'h1{font-size:18px;} h2{font-size:14px;}</style></head><body>'
            .'<h1>Compte rendu — BP Explorer</h1><p>Session : '.e($session->uuid).'</p>'.$rows.'</body></html>';

        $dompdf = new Dompdf;
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4');
        $dompdf->render();

        $path = $this->tempPath('pdf');
        file_put_contents($path, $dompdf->output());

        return [$path, 'compte-rendu.pdf', 'application/pdf'];
    }

    /** @return array<int, array{question:string, answer:string, note:?string}> */
    private function items(ExplorerSession $session): array
    {
        return $this->pins->forSession($session)->map(static fn (PinnedItem $item): array => [
            'question' => (string) ($item->interaction?->question ?? ''),
            'answer' => (string) ($item->interaction?->answer ?? ''),
            'note' => $item->note,
        ])->all();
    }

    private function tempPath(string $extension): string
    {
        return tempnam(sys_get_temp_dir(), 'bpr').'.'.$extension;
    }
}
