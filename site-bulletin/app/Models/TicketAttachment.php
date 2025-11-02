<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAttachment extends Model
{
    /** @use HasFactory<\Database\Factories\TicketAttachmentFactory> */
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'path',
        'disk',
        'original_name',
        'mime',
        'size',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    protected $appends = [
        'download_url',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getDownloadUrlAttribute(): string
    {
        return route('tickets.attachments.download', $this);
    }
}
