<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        $filename = fake()->words(3, true).'.pdf';

        return [
            'tenant_id' => Tenant::factory(),
            'title' => fake()->sentence(4),
            'type' => 'business_plan',
            'original_filename' => $filename,
            'original_path' => '1/'.Str::ulid().'.pdf',
            'mime' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(10_000, 5_000_000),
            'status' => DocumentStatus::Uploaded,
            'page_count' => null,
            'meta' => null,
        ];
    }
}
