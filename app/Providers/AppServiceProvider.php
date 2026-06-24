<?php

namespace App\Providers;

use App\Services\Ingestion\Contracts\DocumentParser;
use App\Services\Ingestion\DoclingParser;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DocumentParser::class, function (Application $app): DoclingParser {
            $config = $app['config']->get('ingestion.docling');

            return new DoclingParser(
                python: (string) $config['python'],
                script: (string) $config['script'],
                timeout: (int) $config['timeout'],
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
