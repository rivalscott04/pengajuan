<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kp_type_id')->constrained('kp_types');
            $table->foreignId('region_id')->constrained('regions');
            $table->enum('status', ['draft', 'diajukan', 'dikembalikan', 'disetujui', 'ditolak'])->default('draft');
            $table->string('pangkat_target')->nullable();
            $table->string('golongan_target')->nullable();
            $table->string('employee_external_id');
            $table->string('nip');
            $table->string('applicant_name');
            $table->string('satuan_kerja');
            $table->string('jabatan');
            $table->string('pangkat_sekarang');
            $table->date('tmt_pangkat');
            $table->string('masa_kerja')->nullable();
            $table->string('pendidikan')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('verifikator_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};





