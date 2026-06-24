<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Document;
use App\Models\ExplorerSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ExplorerSession>
 */
class ExplorerSessionFactory extends Factory
{
    protected $model = ExplorerSession::class;

    public function definition(): array
    {
        $document = Document::factory()->create();

        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $document->tenant_id,
            'document_id' => $document->id,
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addHours(6),
        ];
    }
}
