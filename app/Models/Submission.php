<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_DIAJUKAN = 'diajukan';
    public const STATUS_DIKEMBALIKAN = 'dikembalikan';
    public const STATUS_DISETUJUI = 'disetujui';
    public const STATUS_DITOLAK = 'ditolak';

    protected $fillable = [
        'user_id',
        'kp_type_id',
        'region_id',
        'employee_id',
        'status',
        'pangkat_target',
        'golongan_target',
        'employee_external_id',
        'nip',
        'applicant_name',
        'satuan_kerja',
        'jabatan',
        'pangkat_sekarang',
        'tmt_pangkat',
        'masa_kerja',
        'pendidikan',
        'tanggal_lahir',
        'submitted_at',
        'returned_at',
        'approved_at',
        'rejected_at',
        'verifikator_notes',
    ];

    protected $casts = [
        'tmt_pangkat' => 'date',
        'tanggal_lahir' => 'date',
        'submitted_at' => 'datetime',
        'returned_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function kpType()
    {
        return $this->belongsTo(KpType::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function documents()
    {
        return $this->hasMany(SubmissionDocument::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }
}

