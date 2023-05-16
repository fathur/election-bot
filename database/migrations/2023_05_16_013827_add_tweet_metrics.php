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
        Schema::table('tweets', function (Blueprint $table) {
            $table->unsignedInteger('total_retweets')->nullable();
            $table->unsignedInteger('total_likes')->nullable();
            $table->unsignedInteger('total_comments')->nullable();
            $table->unsignedInteger('total_quotes')->nullable();
            $table->unsignedInteger('total_impressions')->nullable();
            $table->unsignedInteger('total_profile_clicks')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
