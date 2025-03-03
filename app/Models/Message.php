<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use  SoftDeletes;

    protected $fillable = [
        'id',
        'conversation_id',
        'user_id',
        'content',
        'is_edited',
        'is_deleted',
        'sent_at',
        'edited_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'edited_at' => 'datetime',
        'is_edited' => 'boolean',
        'is_deleted' => 'boolean',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messageStatus(): HasMany
    {
        return $this->hasMany(MessageStatus::class);
    }

      // Un message peut avoir plusieurs statuts de lecture (un pour chaque utilisateur)
      public function readStatuses()
      {
          return $this->hasMany(MessageStatus::class);
      }
  
      // Helper pour vérifier si un utilisateur spécifique a lu ce message
      public function isReadBy(User $user)
      {
          return $this->readStatuses()->where('user_id', $user->id)->where('is_read', true)->exists();
      }
}
