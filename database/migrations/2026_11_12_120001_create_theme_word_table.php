<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theme_word', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('theme_id')->constrained()->cascadeOnDelete();
            $table->foreignId('word_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['theme_id', 'word_id']);
            $table->index('word_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_word');
    }
};
