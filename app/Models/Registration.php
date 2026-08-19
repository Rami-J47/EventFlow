<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Registration extends Model
{
    use HasFactory;

    protected $fillable = ['event_id', 'registration_reference', 'first_name', 'last_name', 'email', 'phone', 'status', 'ticket_id'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function getRouteKeyName(): string
    {
        return 'registration_reference';
    }
}
