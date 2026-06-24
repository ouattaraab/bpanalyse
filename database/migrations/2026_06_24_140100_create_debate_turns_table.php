<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Une réplique de débat : persona, contenu, sources citées et chiffres vérifiés.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debate_turns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debate_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('turn_index');
            $table->string('persona');       // clé : dg | investor | cfo | sales
            $table->string('persona_name');  // libellé affiché
            $table->text('content');
            $table->json('sources')->nullable();
            $table->json('verified_figures')->nullable();
            $table->timestamps();

            $table->index(['debate_id', 'turn_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debate_turns');
    }
};
