<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deck_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('front_content');
            $table->text('back_content');
            $table->string('front_image')->nullable();
            $table->string('back_image')->nullable();
            $table->string('card_type')->default('basic');
            $table->boolean('is_suspended')->default(false);

            // FSRS state fields
            $table->dateTime('fsrs_due')->useCurrent();
            $table->tinyInteger('fsrs_state')->default(0); // 0=new,1=learning,2=review,3=relearning
            $table->float('fsrs_stability')->nullable();
            $table->float('fsrs_difficulty')->nullable();
            $table->integer('fsrs_reps')->default(0);
            $table->integer('fsrs_lapses')->default(0);
            $table->integer('fsrs_scheduled_days')->default(0);
            $table->integer('fsrs_elapsed_days')->default(0);
            $table->integer('fsrs_step')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
