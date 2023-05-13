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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->enum('interval', ['daily', 'weekly', 'monthly', 'quarterly', 'semesterly', 'yearly']);
            $table->timestamp('start_at');
            $table->timestamp('end_at');
            $table->unsignedInteger('total_voters')->nullable();
            $table->json('resume')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
