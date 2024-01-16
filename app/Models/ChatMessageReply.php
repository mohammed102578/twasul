<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessageReply extends Model
{
    use HasFactory;

    protected $table = 'chat_messages_reply';

    public function admin(){
        return $this->belongsTo(Admin::class, 'admin_id', 'id');
    }

}
