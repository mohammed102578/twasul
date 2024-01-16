<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavoriteItem extends Model
{
    use HasFactory;

    public function service()
    {
    	return $this->belongsTo(Service::class, 'service_id');
    }

     public function software()
    {
    	return $this->belongsTo(Software::class, 'software_id');
    }
}
