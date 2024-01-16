<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'service_id',
        'software_id',
        'job_biding_id',
        'order_country',
        'wp_order',
        'qty',
        'amount',
        'discount',
        'order_number',
        'extra_service',
        'status',
        'working_status',
        'dispute_report',
        'created_at',
        'updated_at',
        'status_updated_at',
        'product_verity_id',
        'product_verity_amount',
        'buyer_information',
        'billing',
        'shipping',
    ];

    const UPDATED_AT = null;

    protected $casts = [
        'extra_service' => 'object',
    ];

    public function service()
    {
    	return $this->belongsTo(Service::class,'service_id')->with(['user']);
    }

    public function software()
    {
    	return $this->belongsTo(Software::class,'software_id')->with(['user']);
    }

    public function biding()
    {
        return $this->belongsTo(JobBiding::class, 'job_biding_id')->with(['job','user']);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function workFile()
    {
        return $this->hasMany(WorkDelivery::class, 'booking_id');
    }
}
