<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
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
        'preview_url',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getDownloadUrlAttribute(): string
    {
        return route('messages.attachments.download', $this);
    }

    public function getPreviewUrlAttribute(): ?string
    {
        if (! $this->isImage()) {
            return null;
        }

        return route('messages.attachments.preview', $this);
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime, 'image/');
    }
}
