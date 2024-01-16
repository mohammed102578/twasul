<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Software extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $casts = [
        'tag' => 'object',
        'file_include' => 'object'
    ];

    public function featuresSoftware()
    {
        return $this->belongsToMany(Features::class, 'features_software', 'software_id', 'features_id');
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

    public function optionalImage()
    {
        return $this->hasMany(OptionalImage::class, 'software_id');
    }


    public function reviewCount()
    {
        return $this->hasMany(ReviewRating::class, 'software_id');
    }

    public function productType()
    {
        return $this->belongsTo(ProductType::class,'product_type');
    }

    public function verities()
    {
        return $this->hasMany(ProductInventory::class, 'software_id');
    }
}
