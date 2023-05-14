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
        Schema::create('poll_choices', function (Blueprint $table) {
            $table->id();
            $table->string('option');
            $table->boolean('is_considered')
                ->default(true)
                ->comment("Option when has user has doubt for the rest candidates");
            $table->timestamps();
        });

        $this->seedTable();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poll_choices');
    }

    protected function seedTable()
    {
        DB::table('poll_choices')->insert([
            ['option' => 'Anies Baswedan', 'is_considered' => true],
            ['option' => 'Prabowo Subianto', 'is_considered' => true],
            ['option' => 'Ganjar Pranowo', 'is_considered' => true],
            ['option' => 'Belum ada pilihan', 'is_considered' => false],
        ]);
    }
};
