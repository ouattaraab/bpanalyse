<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ExplorerSession;
use App\Services\Export\ReportExporter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function download(Request $request, ExplorerSession $session, ReportExporter $exporter): BinaryFileResponse
    {
        $format = (string) $request->query('format', 'docx');
        abort_unless(in_array($format, ['docx', 'pdf'], true), 422, 'Format non supporté (docx | pdf).');

        [$path, $filename, $mime] = $exporter->generate($session, $format);

        return response()
            ->download($path, $filename, ['Content-Type' => $mime])
            ->deleteFileAfterSend();
    }
}
