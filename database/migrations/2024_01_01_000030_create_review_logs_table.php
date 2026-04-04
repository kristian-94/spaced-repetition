<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('deck_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('rating'); // 1=again,2=hard,3=good,4=easy
            $table->tinyInteger('state_before');
            $table->tinyInteger('state_after');
            $table->integer('scheduled_days');
            $table->integer('elapsed_days');
            $table->integer('review_duration_ms')->nullable();
            $table->dateTime('reviewed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_logs');
    }
};
