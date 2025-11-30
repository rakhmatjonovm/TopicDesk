<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = ['id'];

    // Связь с пользователем
    public function user(): BelongsTo
    {
        // Указываем внешний ключ явно, чтобы Laravel не путался
        return $this->belongsTo(TelegramUser::class, 'telegram_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }

    public function getShortIdAttribute(): string
    {
        return substr($this->id, 0, 8);
    }
}