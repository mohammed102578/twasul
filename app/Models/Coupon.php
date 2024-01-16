<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    public function discount($total)
    {
    	if($this->type == 1){
    		return $this->value;
    	}
    	elseif($this->type == 2){
    		return ($this->value / 100 ) * $total;
    	}
    	else{
    		return 0;
    	}
    }
}
