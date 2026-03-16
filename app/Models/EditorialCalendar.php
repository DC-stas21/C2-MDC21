<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EditorialCalendar extends Model
{
    use HasUuids;

    protected $table = 'editorial_calendar';

    protected $fillable = [
        'channel',
        'title',
        'draft',
        'status',
        'asset',
        'assigned_to',
        'scheduled_for',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'date',
            'published_at' => 'datetime',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
