<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    use HasFactory;

    protected $casts = [
        'skill' => 'object'
    ];

    const UPDATED_AT = null;

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


    public function jobBiding()
    {
        return $this->hasMany(JobBiding::class, 'job_id');
    }


    public function commentCount()
    {
        return $this->hasMany(Comment::class, 'job_id');
    }

}
