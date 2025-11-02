<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\TicketStatus;

class TicketStatusChange extends Model
{
    /** @use HasFactory<\Database\Factories\TicketStatusChangeFactory> */
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'from_status',
        'to_status',
        'reason',
    ];

    protected $casts = [
        'from_status' => TicketStatus::class,
        'to_status' => TicketStatus::class,
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
