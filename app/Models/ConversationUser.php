<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationUser extends Model
{
    protected $table = 'conversation_user';

    protected $fillable = [
        'conversation_id',
        'user_id',
        'joined_at',
        'left_at',
        'is_owner'
    ];

     // Un participant appartient à une conversation
     public function conversation()
     {
         return $this->belongsTo(Conversation::class);
     }
 
     // Un participant est un utilisateur
     public function user()
     {
         return $this->belongsTo(User::class);
     }

     
    // Vérifier si le participant est toujours actif dans la conversation
    public function isActive()
    {
        return $this->left_at === null;
    }
}
