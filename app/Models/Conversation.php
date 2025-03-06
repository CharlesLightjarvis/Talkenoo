<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use SoftDeletes, HasUuids;

    protected $fillable = [
        'id',
        'name',
        'is_group',
    ];

    protected $casts = [
        'is_group' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_user', 'conversation_id', 'user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    // Une conversation peut avoir plusieurs participants
    public function participants()
    {
        return $this->hasMany(ConversationUser::class);
    }

    // Récupérer le dernier message de la conversation
    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latest('sent_at');
    }

    // Vérifier si un utilisateur est membre de la conversation
    public function isMember(User $user)
    {
        return $this->participants()
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->exists();
    }
}
