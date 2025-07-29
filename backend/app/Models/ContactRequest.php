<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'comment',
        'ip_address',
        'user_agent',
        'is_sent_to_telegram',
        'sent_to_telegram_at',
    ];

    protected $casts = [
        'is_sent_to_telegram' => 'boolean',
        'sent_to_telegram_at' => 'datetime',
    ];
}
