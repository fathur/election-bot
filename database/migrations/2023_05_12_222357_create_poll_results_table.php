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
        Schema::create('poll_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained(
                table: 'polls'
            );
            $table->foreignId('poll_choice_id')->constrained(
                table: 'poll_choices'
            );
            $table->unsignedInteger('total_voters');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poll_results');
    }
};
