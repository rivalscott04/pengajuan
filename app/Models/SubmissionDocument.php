<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubmissionDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_id',
        'document_type',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }
}

