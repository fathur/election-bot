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
        Schema::create('report_choices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained(
                table: 'reports'
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
        Schema::dropIfExists('report_choices');
    }
};
