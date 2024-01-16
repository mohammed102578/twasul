<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    const UPDATED_AT = null;
    
    protected $casts = [
        'tag' => 'object'
    ];

    public function featuresService()
    {
        return $this->belongsToMany(Features::class, 'features_services', 'service_id', 'features_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category()
    {
    	return $this->belongsTo(Category::class, 'category_id');
    }

    public function subCategory()
    {
    	return $this->belongsTo(SubCategory::class, 'sub_category_id');
    }

    public function extraService()
    {
        return $this->hasMany(ExtraService::class, 'service_id');
    }

    public function optionalImage()
    {
        return $this->hasMany(OptionalImage::class, 'service_id');
    }


    public function reviewCount()
    {
        return $this->hasMany(ReviewRating::class, 'service_id');
    }

}
