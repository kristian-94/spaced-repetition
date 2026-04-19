<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deck_daily_boosts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deck_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('extra_cards')->default(0);
            $table->timestamps();

            $table->unique(['deck_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deck_daily_boosts');
    }
};
