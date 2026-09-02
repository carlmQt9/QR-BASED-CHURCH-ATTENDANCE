<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceSession extends Model
{
    use SoftDeletes;

    protected $fillable = ['started_by', 'name', 'type', 'location', 'started_at', 'ended_at', 'duration_minutes'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'ended_at' => 'datetime'];
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by')->withTrashed();
    }

    public function records(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }
}