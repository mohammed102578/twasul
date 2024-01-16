<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OptionalImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'software_id',
        'image',
        'original_image',
        'created_at',
        'created_at',
        'updated_at',
    ];
}
