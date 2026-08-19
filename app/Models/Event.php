<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'event_date', 'capacity', 'status'];

    protected function casts(): array
    {
        return ['event_date' => 'datetime', 'capacity' => 'integer'];
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }
}
