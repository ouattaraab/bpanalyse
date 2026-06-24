<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentSlide;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentSlide>
 */
class DocumentSlideFactory extends Factory
{
    protected $model = DocumentSlide::class;

    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'slide_index' => fake()->unique()->numberBetween(1, 200),
            'title' => fake()->sentence(3),
            'section' => fake()->word(),
            'image_path' => null,
            'raw_markdown' => '## '.fake()->sentence(),
        ];
    }
}
