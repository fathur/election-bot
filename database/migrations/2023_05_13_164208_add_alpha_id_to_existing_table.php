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
        Schema::table('accounts', function (Blueprint $table) {
            $table->unsignedInteger('alpha_account_id')->nullable();
        });

        Schema::table('tweets', function (Blueprint $table) {
            $table->unsignedInteger('alpha_tweet_id')->nullable();
            $table->unsignedInteger('alpha_poll_tweet_id')->nullable();
        });

        Schema::table('poll_choices', function (Blueprint $table) {
            $table->unsignedInteger('alpha_poll_choice_id')->nullable();
        });

        Schema::table('poll_results', function (Blueprint $table) {
            $table->unsignedInteger('alpha_poll_result_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('existing', function (Blueprint $table) {
            $table->dropColumn('alpha_account_id');
        });

        Schema::table('tweets', function (Blueprint $table) {
            $table->dropColumn('alpha_tweet_id');
            $table->dropColumn('alpha_poll_tweet_id');

        });

        Schema::table('poll_choices', function (Blueprint $table) {
            $table->dropColumn('alpha_poll_choice_id');
        });

        Schema::table('poll_results', function (Blueprint $table) {
            $table->dropColumn('alpha_poll_result_id');
        });
    }
};
