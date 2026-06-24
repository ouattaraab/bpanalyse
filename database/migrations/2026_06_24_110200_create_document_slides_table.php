<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Une ligne par page/slide issue du parsing. Le Markdown par slide préserve les
 * tableaux ; l'image de slide (image_path) est renseignée à la story 1.4.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_slides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('slide_index');
            $table->string('title')->nullable();
            $table->string('section')->nullable();
            $table->string('image_path')->nullable();
            $table->text('raw_markdown');
            $table->timestamps();

            $table->unique(['document_id', 'slide_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_slides');
    }
};
