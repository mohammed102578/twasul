<?php

namespace App\Jobs;

use App\Models\Product_images;
use App\Models\Product_storage;
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
class SaveWPProductsSaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    private $data = array();

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if(!empty($this->data)){
            foreach ($this->data as $product)
            {
                if(isset($product['sku']) && !empty($product['sku']))
                {
                    $matchSKU = array('product_code' => $product['sku']);
                    $image = isset($product['images'][0]['src']) ? $product['images'][0]['src'] : '';
                    $imageName = "";
                    if(!empty($image))
                    {
                        $headers = get_headers($image, 1);
                        if (isset($headers[0]) && strpos($headers[0], '200 OK') !== false)
                        {
                            $directoryPath = public_path('assets/images/software/');
                            if (!File::exists($directoryPath)) {
                                File::makeDirectory($directoryPath, 0775, true, true);
                            }
                            //$imageName = basename($image);
                            $imageExtension = Str::lower(pathinfo($image, PATHINFO_EXTENSION));
                            $imageName = 'wpproduct_'.time().'.'.$imageExtension;
                            $file_name_path = $directoryPath.$imageName;
                            File::copy($image, $file_name_path);
                        }
                    }

                    $result = Product_storage::updateOrCreate(
                        $matchSKU,
                        [
                            'featured'          => isset($product['featured']) ? $product['featured'] : '',
                            'product_code'      => isset($product['sku']) ? $product['sku'] : '',
                            'available_in_country'=> 'saudia',
                            'wp_product'        => '1',
                            'user_id'           => '0',
                            'category_id'       => '2',
                            'sub_category_id'   => '11',
                            'title'             => isset($product['name']) ? $product['name'] : '',
                            //'image'             => isset($product['permalink']) ? $product['permalink'] : '',
                            'tag'               => !empty($product['tags']) ? json_encode($product['tags']) : '',
                            'amount'            => isset($product['price']) ? $product['price'] :'',
                            'description'       => isset($product['description']) ? $product['description'] : '',
                            'rating'            => isset($product['rating_count']) ? $product['rating_count'] : '',
                            'stock_status'      => isset($product['stock_status']) ? $product['stock_status'] : '',
                            'status'            => '1',
                            'product_type'      => '1',
                            'image'             => isset($imageName) ? $imageName : '',
                            //'image'             => isset($product['images'][0]['src']) ? $product['images'][0]['src'] : '',
                            //'image'             => isset($product['categories'][0]['src']) ? $product['categories'][0]['src'] : '',
                        ]
                    );
                    if(isset($product['images']) && !empty($product['images']))
                    {
                        foreach ($product['images'] as $images){
                            $matchProductId = array('product_id' => $result->id,'name' => trim($images['name']));
                            Product_images::updateOrCreate(
                                $matchProductId,
                                [
                                    'product_id'    => $result->id,
                                    'name'          => isset($images['name']) ? trim($images['name']) :'',
                                    'src'           => isset($images['src']) ? $images['src'] :'',
                                    'alt'           => isset($images['alt']) ? $images['alt'] : '',
                                ]
                            );
                        }
                    }
                    if(isset($product['categories']) && !empty($product['categories']))
                    {
                        foreach ($product['categories'] as $category){
                            $matchProductId = array('category_id' => 2,'name' => trim($category['name']));
                            $sub_id = SubCategory::updateOrCreate(
                                $matchProductId,
                                [
                                    'category_id'   => '2',
                                    'name'          => isset($category['name']) ? trim($category['name']) :'',
                                ]
                            );
                            $data = [
                                'sub_category_id' => $sub_id->id
                            ];
                            $result->update($data);
                        }
                    }
                }
            }
        }
    }
}
