<?php

namespace App\Jobs;

use App\Models\GeneralSetting;
use App\Models\Product_images;
use App\Models\Product_storage;
use App\Models\ProductInventory;
use App\Models\Software;
use App\Models\SubCategory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
class ShippingRateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    private $data = array();
    private $country = "";

    public function __construct($data,$country)
    {
        $this->data = $data;
        $this->country = $country;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if(!empty($this->data))
        {
            $cost = "";
            foreach ($this->data as $shipping)
            {
                if(isset($shipping['id'],$shipping['settings'],$shipping['settings']['cost']) && !empty($shipping['id']))
                {
                    $cost = $shipping['settings']['cost']['value'];
                }
            }
            if($cost)
            {
                $products = Product_storage::where('available_in_country',$this->country)->where('wp_product',1)->get();
                if(!empty($products) && !empty($cost))
                {
                    $cost = $this->currencyConversion($cost);
                    $cost_new = $cost;
                    foreach ($products as $product)
                    {
                        $price = $product->amount;
                        if(!empty($price))
                        {
                            $ten_per = (10 / 100) * ( (float)$price + (float)$cost);
                            $new_price = (float)$price + (float)$cost_new + (float)$ten_per;
                            $price_array = array('recommand_seling_price'=>$new_price,'shipping_charge'=>$cost);
                            $product->update($price_array);
                        }
                    }
                }
            }
        }
    }
    // currency conversion
    public function currencyConversion($cost)
    {
        $general = GeneralSetting::first();
        if($this->country =='Egypt'){
            $code = 'EG';
        }elseif ($this->country == 'Saudi Arabia'){
            $code = 'SA';
        }else{
            $code = 'AE';
        }
        $currency_code = [
            "AE"    => ["AED",3.67],
            "SA"    => ["SAR",3.75],
            "EG"    => ["EGP",30.91]
        ];
        try{
            if(!empty($code))
            {
                if(array_key_exists(strtoupper($code) ,$currency_code))
                {
                    $price = isset($cost) ? $cost : 0;
                    return ((float)$price) / ((float)$currency_code[$code][1]);
                }else
                {
                    $price = isset($cost) ? $cost : 0;
                    return 1 * (float)$price;
                }
            }else{
                $price = isset($cost) ? $cost : 0;
                return 1 * (float)$price;
            }
        }catch(\Exception $e){
            $price = isset($cost) ? $cost : 0;
            return 1 * (float)$price;
        }
    }
}
