<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketMessage extends Model
{
    protected $fillable = [
        'ticket_id',
        'direction',
        'content',
        'user_message_id',
        'support_message_id',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
