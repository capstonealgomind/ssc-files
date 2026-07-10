<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoterPresenceSnapshot extends Model
{
    protected $fillable = [
        'recorded_at',
        'online_total',
        'online_mobile',
        'online_desktop',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'online_total' => 'integer',
            'online_mobile' => 'integer',
            'online_desktop' => 'integer',
        ];
    }
}
