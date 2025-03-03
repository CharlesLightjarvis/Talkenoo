<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    protected $fillable = [
        'fullName',
        'email',    
        'phone',
        'profile_picture',
        'is_active',
        'status',
        'last_active',
        'password',
    ];

   
    protected $hidden = [
        'password',
        'remember_token',
    ];

   
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'last_active' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }


    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_user', 'user_id', 'conversation_id');
    }
    
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function messageStatus(): HasMany
    {
        return $this->hasMany(MessageStatus::class);
    }

    // Un utilisateur peut participer à plusieurs conversations
    public function participatedConversations()
    {
        return $this->hasMany(ConversationUser::class);
    }
}
