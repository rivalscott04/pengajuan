<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'nip',
        'nip_baru',
        'nama_lengkap',
        'pangkat_asn',
        'gol_ruang',
        'jabatan',
        'satuan_kerja',
        'kode_satuan_kerja',
        'kab_kota',
        'jenjang_pendidikan',
        'tmt_pangkat',
        'mk_tahun',
        'mk_bulan',
        'raw',
    ];

    protected $casts = [
        'tmt_pangkat' => 'date',
        'raw' => 'array',
    ];

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }
}
