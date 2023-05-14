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
        Schema::table('poll_results', function (Blueprint $table) {
            $table->unsignedInteger('total_voters')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('poll_results', function (Blueprint $table) {
            $table->unsignedInteger('total_voters')->change();
            
        });
    }
};
