<?php

declare(strict_types=1);

use App\Enums\DocumentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documents téléversés (BP au MVP). Le fichier source est stocké isolé par
 * tenant sur le disque privé `documents` ; ici on garde le chemin + métadonnées.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('type')->default('business_plan');
            $table->string('original_filename');
            $table->string('original_path');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('status')->default(DocumentStatus::Uploaded->value);
            $table->unsignedInteger('page_count')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
