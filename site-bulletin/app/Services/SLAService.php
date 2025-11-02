<?php
namespace App\Services;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\SLASetting;
use App\Models\Ticket;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class SLAService
{
    public function targets(string|TicketPriority $priority): array
    {
        $priorityValue = $priority instanceof TicketPriority ? $priority->value : $priority;
        $setting = SLASetting::firstWhere('priority', $priorityValue);

        return [
            'priority' => $priorityValue,
            'first_response_minutes' => $setting->first_response_minutes ?? $this->defaultFirstResponse($priorityValue),
            'resolution_minutes' => $setting->resolution_minutes ?? $this->defaultResolution($priorityValue),
            'pause_statuses' => $setting?->pause_statuses ?? ['waiting_employee'],
        ];
    }

    public function evaluate(Ticket $ticket): array
    {
        $targets = $this->targets($ticket->priority);
        $timeline = $ticket->statusChanges()->orderBy('created_at')->get();

        $firstResponseChange = $timeline->first(function ($change) {
            $to = $this->statusValue($change->to_status);
            return $to !== TicketStatus::New->value;
        });

        $firstResponseMinutes = $firstResponseChange
            ? $ticket->created_at?->diffInMinutes($firstResponseChange->created_at)
            : null;

        $pauseStatuses = array_map(
            fn ($status) => $status instanceof TicketStatus ? $status->value : (string) $status,
            (array) ($targets['pause_statuses'] ?? [])
        );
        $activeMinutes = 0;
        $currentStatus = TicketStatus::New->value;
        $cursor = $ticket->created_at ?? Carbon::now();

        foreach ($timeline as $change) {
            $changeTime = $change->created_at ?? $cursor;

            if (! in_array($currentStatus, $pauseStatuses, true)) {
                $activeMinutes += $cursor->diffInMinutes($changeTime);
            }

            $cursor = $changeTime;
            $currentStatus = $this->statusValue($change->to_status);
        }

        $finalTimestamp = $ticket->closed_at ?? Carbon::now();
        if (! in_array($currentStatus, $pauseStatuses, true)) {
            $activeMinutes += $cursor->diffInMinutes($finalTimestamp);
        }

        return [
            'targets' => $targets,
            'first_response_minutes' => $firstResponseMinutes,
            'first_response_breached' => $firstResponseMinutes !== null && $firstResponseMinutes > $targets['first_response_minutes'],
            'resolution_active_minutes' => $activeMinutes,
            'resolution_breached' => $activeMinutes > $targets['resolution_minutes'],
        ];
    }

    public function isBreached(Ticket $ticket): array
    {
        return Arr::only($this->evaluate($ticket), [
            'first_response_breached',
            'resolution_breached',
        ]);
    }

    protected function defaultFirstResponse(string $priority): int
    {
        return match ($priority) {
            'critical' => 60,
            'high' => 120,
            'medium' => 240,
            default => 480,
        };
    }

    protected function defaultResolution(string $priority): int
    {
        return match ($priority) {
            'critical' => 240,
            'high' => 720,
            'medium' => 1440,
            default => 2880,
        };
    }

    protected function statusValue(mixed $status): string
    {
        return match (true) {
            $status instanceof TicketStatus => $status->value,
            $status instanceof \BackedEnum => $status->value,
            default => (string) $status,
        };
    }
}
