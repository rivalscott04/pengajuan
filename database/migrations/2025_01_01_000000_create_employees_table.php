<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('nip')->index();
            $table->string('nip_baru')->nullable()->index();
            $table->string('nama_lengkap')->nullable()->index();
            $table->string('pangkat_asn')->nullable();
            $table->string('gol_ruang')->nullable();
            $table->string('satuan_kerja')->nullable()->index();
            $table->string('jabatan')->nullable();
            $table->string('jenjang_pendidikan')->nullable();
            $table->date('tmt_pangkat')->nullable();
            $table->unsignedInteger('mk_tahun')->default(0);
            $table->unsignedInteger('mk_bulan')->default(0);
            $table->string('kab_kota')->nullable()->index();
            $table->string('kode_satuan_kerja')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->unique(['nip_baru']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};





