<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_number',
        'requestor_id',
        'requestor_role',
        'current_status',
        'current_step',
        'current_owner_role',
        'current_owner_id',
        'is_late',
        'is_exception_flow',
        'exception_status',
        'title',
        'request_type',
        'farm_name',
        'purpose',
        'date_needed',
        'chick_in_date',
        'capacity',
        'description',
        'preferred_meeting_date',
        'preferred_meeting_time',
        'submitted_at',
        'first_reviewed_at',
        'locked_at',
        'last_transitioned_at',
        'completed_at',
        'cancelled_at',
        'withdrawn_at',
        'latest_remarks',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_late' => 'boolean',
            'is_exception_flow' => 'boolean',
            'date_needed' => 'date',
            'chick_in_date' => 'date',
            'preferred_meeting_date' => 'date',
            'preferred_meeting_time' => 'string',
            'submitted_at' => 'datetime',
            'first_reviewed_at' => 'datetime',
            'locked_at' => 'datetime',
            'last_transitioned_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function isEditableByRequestor(): bool
    {
        return $this->withdrawn_at === null && $this->first_reviewed_at === null && $this->locked_at === null;
    }

    public function requestor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requestor_id');
    }

    public function currentOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_owner_id');
    }

    public function transitions(): HasMany
    {
        return $this->hasMany(RequestTransition::class)->orderBy('acted_at');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(RequestAttachment::class);
    }
}
