<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Country;

return new class extends Migration
{
    protected $map = [
        "Turkey"    => "Turki",
        "Spain"     => "Spanyol",
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->map as $international => $indonesia) {
            Country::where('name', $international)->update([
                "locale_name"   => json_encode([
                    "id"    => $indonesia
                ])
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->map as $international) {
            Country::where('name', $international)->update([
                "locale_name"   => json_encode([
                    "id"    => ""
                ])
            ]);
        }
    }
};
