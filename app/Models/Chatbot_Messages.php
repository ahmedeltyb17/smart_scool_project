<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotMessage extends Model
{
    use HasFactory;

    public function conversation()
    {
        return $this->belongsTo(ChatbotConversation::class, 'conversation_id');
    }
}
