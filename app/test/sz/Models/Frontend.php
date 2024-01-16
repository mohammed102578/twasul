<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Frontend extends Model
{
    // protected $guarded = ['id'];

    protected $table = "frontends";
    protected $casts = [
        'data_values' => 'object'
    ];

    public static function scopeGetContent($data_keys)
    {
        return Frontend::where('data_keys', $data_keys);
    }
    public function category()
    {
    	return $this->belongsTo(Category::class, 'category_id');
    }
    public function sub_category()
    {
    	return $this->belongsTo(SubCategory::class, 'sub_category_id');
    }
}
