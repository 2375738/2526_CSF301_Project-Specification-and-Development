<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    public function __construct(protected Request $request)
    {
    }

    public function log(string $eventType, ?Model $auditable = null, array $payload = [], ?User $actor = null): void
    {
        $actor ??= $this->request->user();

        AuditLog::create([
            'actor_id' => $actor?->id,
            'event_type' => $eventType,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'payload' => empty($payload) ? null : $payload,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'occurred_at' => now(),
        ]);
    }
}
