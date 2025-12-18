<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Submission;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public function log(?Submission $submission, string $action, array $meta = []): void
    {
        AuditLog::create([
            'submission_id' => $submission?->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'meta' => $meta ?: null,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}

