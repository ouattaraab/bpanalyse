<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ChunkType;
use App\Models\Chunk;
use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Chunk>
 */
class ChunkFactory extends Factory
{
    protected $model = Chunk::class;

    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'document_slide_id' => null,
            'section' => fake()->word(),
            'type' => ChunkType::Text,
            'content' => fake()->paragraph(),
            'caption' => null,
            'metadata' => [],
        ];
    }

    public function table(): static
    {
        return $this->state(fn () => [
            'type' => ChunkType::Table,
            'content' => "| A | B |\n|---|---|\n| 1 | 2 |",
            'caption' => 'Tableau de test',
        ]);
    }
}
