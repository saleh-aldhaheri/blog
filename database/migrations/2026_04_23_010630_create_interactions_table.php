<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('interactions', function (Blueprint $table) {
            $table->id();
            $table->string('action');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('interactable');
            $table->timestamps();
            $table->unique(
                ['user_id', 'interactable_id', 'interactable_type'],
                'user_interaction_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interactions');
    }
};
