<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('province_code', 10)->default('52');
            $table->string('province_name')->default('Nusa Tenggara Barat');
            $table->string('city_name');
            $table->enum('type', ['kabkota', 'kanwil']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};





