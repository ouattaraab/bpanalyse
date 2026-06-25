<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Document\Contracts\StructuredDataService;
use App\Services\Document\FinancialQueryService;
use App\Services\Ingestion\Contracts\DocumentParser;
use App\Services\Ingestion\DoclingParser;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DocumentParser::class, function (): DoclingParser {
            $config = config('ingestion.docling');
            $config = is_array($config) ? $config : [];

            return new DoclingParser(
                python: (string) ($config['python'] ?? ''),
                script: (string) ($config['script'] ?? ''),
                timeout: (int) ($config['timeout'] ?? 600),
            );
        });

        // Accès déterministe aux chiffres (BP). Le LLM ne calcule jamais.
        $this->app->bind(StructuredDataService::class, FinancialQueryService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
