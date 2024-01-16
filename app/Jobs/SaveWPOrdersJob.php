<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Models\Product_images;
use App\Models\Product_storage;
use App\Models\SubCategory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SaveWPOrdersJob implements ShouldQueue
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
            foreach ($this->data as $product){
                $order_key = array('order_number' => trim($product['order_key']));
                $qty = $this->getQty($product);
                $anc = Booking::updateOrCreate(
                    $order_key,
                    [
                        'order_country'     => 'egypt',
                        'wp_order'          => '1',
                        'user_id'           => '0',
                        //'service_id'        => '0',
                        'software_id'       => '1',
                        //'job_biding_id'     => '0',
                        'qty'               => $qty,
                        'amount'            => isset($product['total']) ? $product['total'] : '',
                        'discount'          => isset($product['discount']) ? $product['discount'] : '',
                        'order_number'      => isset($product['order_key']) ? trim($product['order_key']) : '',
                        'status'            => isset($product['status']) && $product['status']=='completed' ? '2' : '',
                        'billing'           => isset($product['billing']) && !empty($product['billing']) ? json_encode($product['billing']) : '',
                        'shipping'          => isset($product['shipping']) && !empty($product['shipping']) ? json_encode($product['shipping']) : '',
                        'updated_at'        => date('Y-m-d H:i:s'),
                    ]
                );
            }
        }
    }

    public function getQty($product)
    {
        $quantity = 0;
        if(isset($product['line_items']) && !empty($product['line_items']))
        {
            foreach ($product['line_items'] as $items)
            {
                $quantity = $quantity + $items['quantity'];
            }
        }
        return $quantity;
    }
}
