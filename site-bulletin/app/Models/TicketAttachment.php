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
        'visibility',
        'kind',
        'label',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    protected $appends = [
        'download_url',
    ];

    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_INTERNAL = 'internal';

    public const KIND_PHOTO = 'photo';
    public const KIND_SCREENSHOT = 'screenshot';
    public const KIND_DOCUMENT = 'document';
    public const KIND_TIMESHEET = 'timesheet';
    public const KIND_OTHER = 'other';

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

    public function visibleTo(User $user): bool
    {
        if ($this->visibility === self::VISIBILITY_PUBLIC) {
            return true;
        }

        return $user->hasRole('manager', 'ops_manager', 'hr', 'admin');
    }

    public function visibilityLabel(): string
    {
        return $this->visibility === self::VISIBILITY_INTERNAL ? 'Internal only' : 'Visible to requester';
    }

    public function kindLabel(): string
    {
        return match ($this->kind) {
            self::KIND_PHOTO => 'Photo',
            self::KIND_SCREENSHOT => 'Screenshot',
            self::KIND_DOCUMENT => 'Document',
            self::KIND_TIMESHEET => 'Timesheet evidence',
            default => 'Other evidence',
        };
    }
}
