<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'telegram_user_id',
        'topic_id',
        'status',
        'subject',
    ];

    public function telegramUser()
    {
        return $this->belongsTo(TelegramUser::class);
    }
}
