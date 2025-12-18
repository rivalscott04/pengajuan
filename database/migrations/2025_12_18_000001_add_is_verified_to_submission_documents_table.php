<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submission_documents', function (Blueprint $table) {
            $table->boolean('is_verified')
                ->default(false)
                ->after('size');
        });
    }

    public function down(): void
    {
        Schema::table('submission_documents', function (Blueprint $table) {
            $table->dropColumn('is_verified');
        });
    }
};


