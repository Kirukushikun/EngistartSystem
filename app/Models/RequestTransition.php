<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestTransition extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_request_id',
        'acted_by_id',
        'acted_by_role',
        'action',
        'from_status',
        'to_status',
        'from_step',
        'to_step',
        'from_owner_role',
        'to_owner_role',
        'to_owner_id',
        'is_rework',
        'is_exception_path',
        'is_terminal',
        'remarks',
        'context',
        'acted_at',
    ];

    protected function casts(): array
    {
        return [
            'is_rework' => 'boolean',
            'is_exception_path' => 'boolean',
            'is_terminal' => 'boolean',
            'context' => 'array',
            'acted_at' => 'datetime',
        ];
    }

    public function projectRequest(): BelongsTo
    {
        return $this->belongsTo(ProjectRequest::class);
    }

    public function actedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by_id');
    }

    public function toOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_owner_id');
    }
}
