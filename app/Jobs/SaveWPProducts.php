<?php

namespace App\Jobs;

use App\Models\GeneralSetting;
use App\Models\OptionalImage;
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
class SaveWPProducts implements ShouldQueue
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
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function handle()
    {
        if(!empty($this->data))
        {
            if($this->country == 'Egypt'){
                $delivery_day = 3;
            }elseif ($this->country == 'Saudi Arabia'){
                $delivery_day = 5;
            }else{
                $delivery_day = 2;
            }

            foreach ($this->data as $product)
            {
                $product['sku'] = isset($product['sku']) && !empty($product['sku']) ? $product['sku'] : $product['id'];
                if(isset($product['sku']) && !empty($product['sku']))
                {
                    $product_exist = Software::where('product_code',$product['sku'])->first();
                    if(empty($product_exist))
                    {
                        $matchSKU = array('product_code' => $product['sku']);
                        $image = isset($product['images'][0]['src']) ? $product['images'][0]['src'] : '';
                        $imageName = $original_image = "";
                        if(!empty($image))
                        {
                            $headers = get_headers($image, 1);
                            $mine_type = isset($headers['Content-Type']) && !empty($headers['Content-Type']) ? $headers['Content-Type'] : '';
                            if (isset($headers[0]) && strpos($headers[0], '200 OK') !== false && !empty($mine_type))
                            {
                                //$directoryPath = public_path('assets/images/software/');
                                $directoryPath = $_SERVER['DOCUMENT_ROOT'].'/assets/images/software/';
                                if (!File::exists($directoryPath)) {
                                    File::makeDirectory($directoryPath, 0775, true, true);
                                }
                                //$imageName = basename($image);
                                $path = imagePath()['product']['path'];
                                $max_size = imagePath()['product']['max_size'];
                                echo $product['images'][0]['src'].'<br>';
                                $dimensions = getimagesize($product['images'][0]['src']);
                                $size = decide_resize($dimensions, $max_size);
                                $imageName = uploadImage($product['images'][0]['src'], $path, $size,'','',1);
                                $original_image = uploadImage($product['images'][0]['src'], $path,'','','',1);
                            }
                        }
                        if(isset($product['price']) && !empty($product['price'])){
                            $price = $this->currencyConversion($product);
                        }
                        $result = Product_storage::updateOrCreate(
                            $matchSKU,
                            [
                                'featured'          => isset($product['featured']) ? $product['featured'] : '',
                                'product_code'      => isset($product['sku']) ? $product['sku'] : '',
                                'available_in_country'=> $this->country,
                                'wp_product'        => '1',
                                'user_id'           => '0',
                                'category_id'       => '24',
                                'sub_category_id'   => '316',
                                'title'             => isset($product['name']) ? $product['name'] : '',
                                //'image'             => isset($product['permalink']) ? $product['permalink'] : '',
                                'tag'               => !empty($product['tags']) ? json_encode($product['tags']) : '',
                                'amount'            => isset($price) && !empty($price) ? $price :'',
                                'amount_eg'         => $this->country =='Egypt' && isset($product['price']) && !empty($product['price']) ? $product['price'] : '',
                                'description'       => isset($product['description']) ? $product['description'] : '',
                                'rating'            => isset($product['rating_count']) ? $product['rating_count'] : '',
                                'stock_status'      => isset($product['stock_status']) ? $product['stock_status'] : '',
                                'delivery_day'      => $delivery_day,
                                'status'            => '1',
                                'product_type'      => '1',
                                'image'             => isset($imageName) ? $imageName : '',
                                'original_image'    => isset($original_image) ? $original_image : '',
                                //'image'             => isset($product['images'][0]['src']) ? $product['images'][0]['src'] : '',
                                //'image'             => isset($product['categories'][0]['src']) ? $product['categories'][0]['src'] : '',
                            ]
                        );
                        if(isset($product['images']) && !empty($product['images']))
                        {
                            foreach ($product['images'] as $key=> $images)
                            {
                                if($key ==0){
                                    continue;
                                }
                                $imageName_sub = "";
                                if(!empty($images['src']))
                                {
                                    $headers = get_headers($images['src'], 1);
                                    if (isset($headers[0]) && strpos($headers[0], '200 OK') !== false)
                                    {
                                        $directoryPath = $_SERVER['DOCUMENT_ROOT'].'/assets/images/software/';
                                        if (!File::exists($directoryPath)) {
                                            File::makeDirectory($directoryPath, 0775, true, true);
                                        }
                                        //$imageName = basename($image);
                                        $max_size = imagePath()['product']['max_size'];
                                        $path = imagePath()['screenshot']['path'];
                                        $dimensions = getimagesize($images['src']);
                                        $size = decide_resize($dimensions, $max_size);

                                        $imageName_sub = uploadImage($images['src'], $path, $size,'','',1);
                                        $original_image_sub = uploadImage($images['src'], $path,'','','',1);
                                        if(!empty($imageName_sub))
                                        {
                                            $matchProductId = array('product_id' => $result->id,'name' => $imageName_sub);
                                            Product_images::updateOrCreate(
                                                $matchProductId,
                                                [
                                                    'product_id'    => $result->id,
                                                    'name'          => isset($images['name']) ? trim($images['name']) :'',
                                                    'src'           => $imageName_sub,
                                                    'alt'           => isset($images['alt']) ? $images['alt'] : '',
                                                ]
                                            );
                                            $matchProductId = array('software_id' => $result->id,'image' => $imageName_sub);
                                            OptionalImage::updateOrCreate(
                                                $matchProductId,
                                                [
                                                    'software_id'   => $result->id,
                                                    'image'         => $imageName_sub,
                                                    'original_image'=> isset($original_image_sub) ? $original_image_sub : '',
                                                    'created_at'    => date('Y-m-d H:i:s'),
                                                    'updated_at'    => date('Y-m-d H:i:s'),
                                                ]
                                            );
                                        }
                                    }
                                }
                            }
                        }
                        if(isset($product['stock_status']) && !empty($product['stock_status']) && $product['stock_status'] =='instock')
                        {
                            if(isset($product['attributes']) && !empty($product['attributes']))
                            {
                                if(isset($product['attributes'][0]['options']) && !empty($product['attributes'][0]['options']) && count($product['attributes'][0]['options'])>1)
                                {
                                    foreach ($product['attributes'][0]['options'] as $option)
                                    {
                                        $record_exist = array('software_id' => $result->id,'name' => trim($option));
                                        ProductInventory::updateOrCreate(
                                            $record_exist,
                                            [
                                                'software_id'   => $result->id,
                                                'name'          => isset($option) ? trim($option) : '',
                                                'inventory'     => 100,
                                            ]
                                        );
                                    }
                                }else{
                                    $record_exist = array('software_id' => $result->id);
                                    ProductInventory::updateOrCreate(
                                        $record_exist,
                                        [
                                            'software_id'   => $result->id,
                                            'name'          => isset($product['name']) ? trim($product['name']) : '',
                                            'inventory'     => 100,
                                        ]
                                    );
                                }
                            }else{
                                $record_exist = array('software_id' => $result->id);
                                ProductInventory::updateOrCreate(
                                    $record_exist,
                                    [
                                        'software_id'   => $result->id,
                                        'name'          => isset($product['name']) ? trim($product['name']) : '',
                                        'inventory'     => 100,
                                    ]
                                );
                            }
                        }
                        // out of stock
                        else{
                            if(isset($product['attributes']) && !empty($product['attributes']))
                            {
                                if(isset($product['attributes'][0]['options']) && !empty($product['attributes'][0]['options']) && count($product['attributes'][0]['options'])>1)
                                {
                                    foreach ($product['attributes'][0]['options'] as $option)
                                    {
                                        $record_exist = array('software_id' => $result->id,'name' => trim($option));
                                        ProductInventory::updateOrCreate(
                                            $record_exist,
                                            [
                                                'software_id'   => $result->id,
                                                'name'          => isset($option) ? trim($option) : '',
                                                'inventory'     => 0,
                                            ]
                                        );
                                    }
                                }else{
                                    $record_exist = array('software_id' => $result->id);
                                    ProductInventory::updateOrCreate(
                                        $record_exist,
                                        [
                                            'software_id'   => $result->id,
                                            'name'          => isset($product['name']) ? trim($product['name']) : '',
                                            'inventory'     => 0,
                                        ]
                                    );
                                }
                            }else{
                                $record_exist = array('software_id' => $result->id);
                                ProductInventory::updateOrCreate(
                                    $record_exist,
                                    [
                                        'software_id'   => $result->id,
                                        'name'          => isset($product['name']) ? trim($product['name']) : '',
                                        'inventory'     => 0,
                                    ]
                                );
                            }
                        }
                    }else{
                        if(isset($product['stock_status']) && !empty($product['stock_status']) && $product['stock_status'] =='instock')
                        {
                            if(isset($product['attributes']) && !empty($product['attributes']))
                            {
                                if(isset($product['attributes'][0]['options']) && !empty($product['attributes'][0]['options']) && count($product['attributes'][0]['options'])>1)
                                {
                                    foreach ($product['attributes'][0]['options'] as $option)
                                    {
                                        $record_exist = array('software_id' => $product_exist->id,'name' => trim($option));
                                        ProductInventory::updateOrCreate(
                                            $record_exist,
                                            [
                                                'software_id'   => $product_exist->id,
                                                'name'          => isset($option) ? trim($option) : '',
                                                'inventory'     => 100,
                                            ]
                                        );
                                    }
                                }else{
                                    $record_exist = array('software_id' => $product_exist->id);
                                    ProductInventory::updateOrCreate(
                                        $record_exist,
                                        [
                                            'software_id'   => $product_exist->id,
                                            'name'          => isset($product['name']) ? $product['name'] : '',
                                            'inventory'     => 100,
                                        ]
                                    );
                                }
                            }else{
                                $record_exist = array('software_id' => $product_exist->id);
                                ProductInventory::updateOrCreate(
                                    $record_exist,
                                    [
                                        'software_id'   => $product_exist->id,
                                        'name'          => isset($product['name']) ? $product['name'] : '',
                                        'inventory'     => 100,
                                    ]
                                );
                            }
                        }
                        // out of stock
                        else{
                            if(isset($product['attributes']) && !empty($product['attributes']))
                            {
                                if(isset($product['attributes'][0]['options']) && !empty($product['attributes'][0]['options']) && count($product['attributes'][0]['options'])>1)
                                {
                                    foreach ($product['attributes'][0]['options'] as $option)
                                    {
                                        $record_exist = array('software_id' => $product_exist->id,'name' => trim($option));
                                        ProductInventory::updateOrCreate(
                                            $record_exist,
                                            [
                                                'software_id'   => $product_exist->id,
                                                'name'          => isset($option) ? trim($option) : '',
                                                'inventory'     => 0,
                                            ]
                                        );
                                    }
                                }else{
                                    $record_exist = array('software_id' => $product_exist->id);
                                    ProductInventory::updateOrCreate(
                                        $record_exist,
                                        [
                                            'software_id'   => $product_exist->id,
                                            'name'          => isset($product['name']) ? $product['name'] : '',
                                            'inventory'     => 0,
                                        ]
                                    );
                                }
                            }else{
                                $record_exist = array('software_id' => $product_exist->id);
                                ProductInventory::updateOrCreate(
                                    $record_exist,
                                    [
                                        'software_id'   => $product_exist->id,
                                        'name'          => isset($product['name']) ? $product['name'] : '',
                                        'inventory'     => 0,
                                    ]
                                );
                            }
                        }

                        if(isset($product['price']) && !empty($product['price'])){
                            $price = $this->currencyConversion($product);
                            $price_array = array(
                                'amount'    => isset($price) && !empty($price) ? $price : '',
                                'amount_eg' => $this->country =='Egypt' && isset($product['price']) && !empty($product['price']) ? $product['price'] : '',
                            );
                            $res_product = Product_storage::where('product_code',$product['sku'])->first();
                            $res_product->update($price_array);
                        }
                    }
                }
            }
        }
    }
    // currency conversion
    public function currencyConversion($product)
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
                    $price = isset($product['price']) ? $product['price'] : 0;
                    return ((float)$price) / ((float)$currency_code[$code][1]);
                }else
                {
                    $price = isset($product['price']) ? $product['price'] : 0;
                    return 1 * (float)$price;
                }
            }else{
                $price = isset($product['price']) ? $product['price'] : 0;
                return 1 * (float)$price;
            }
        }catch(\Exception $e){
            $price = isset($product['price']) ? $product['price'] : 0;
            return 1 * (float)$price;
        }
    }
}