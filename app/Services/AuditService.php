<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Loan;
use App\Models\User;

class AuditService
{
    public function log(?Loan $loan, ?User $actor, string $action, ?string $description = null, array $meta = []): ActivityLog
    {
        return ActivityLog::create([
            'loan_id' => $loan?->id,
            'actor_id' => $actor?->id,   // null = sistem/scheduler
            'action' => $action,
            'description' => $description,
            'meta' => $meta ?: null,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'created_at' => now(),
        ]);
    }
}
