<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'farm',
        'department',
        'is_active',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function submittedRequests(): HasMany
    {
        return $this->hasMany(ProjectRequest::class, 'requestor_id');
    }

    public function assignedRequests(): HasMany
    {
        return $this->hasMany(ProjectRequest::class, 'current_owner_id');
    }

    public function requestTransitions(): HasMany
    {
        return $this->hasMany(RequestTransition::class, 'acted_by_id');
    }

    public function requestAttachments(): HasMany
    {
        return $this->hasMany(RequestAttachment::class, 'uploaded_by_id');
    }
}
