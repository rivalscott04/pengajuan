<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KpType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'document_requirements',
    ];

    protected $casts = [
        'document_requirements' => 'array',
    ];

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }
}

