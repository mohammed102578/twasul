<?php

namespace App\Console\Commands;

use App\Jobs\SaveWPProducts;
use App\Models\Product_images;
use App\Models\Product_storage;
use Illuminate\Console\Command;

use Carbon\Carbon;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class GetWordpressProducts extends Command
{
    /**a
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get_products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        set_time_limit(0);
        $countries = array(
            'Egypt'                 => 'Egypt',
            'United Arab Emirates'  => 'United Arab Emirates',
            'Saudi Arabia'          => 'Saudi Arabia',
        );
        foreach ($countries as $country)
        {
            if($country == 'Egypt'){
                $apiUrl = env("WOOCOMMERCE_STORE_URL", "");
                $username = env("WOOCOMMERCE_CONSUMER_KEY", "");
                $password = env("WOOCOMMERCE_CONSUMER_SECRET", "");
            }elseif ($country == 'Saudi Arabia'){
                $apiUrl = env("WOOCOMMERCE_STORE_URL_SA", "");
                $username = env("WOOCOMMERCE_CONSUMER_KEY_SA", "");
                $password = env("WOOCOMMERCE_CONSUMER_SECRET_SA", "");
            }else{
                $apiUrl = env("WOOCOMMERCE_STORE_URL_UAE", "");
                $username = env("WOOCOMMERCE_CONSUMER_KEY_UAE", "");
                $password = env("WOOCOMMERCE_CONSUMER_SECRET_UAE", "");
            }

            if(!empty($apiUrl))
            {
                for ($i=1; $i<=400; $i++)
                {
                    $apiUrl_perpage = $apiUrl.'?page='.$i;
                    $ch = curl_init($apiUrl_perpage);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'content-type: text/html; charset=utf-8'
                    ]);
                    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                    curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    $response = curl_exec($ch);
                    if (curl_errno($ch)) {
                        echo 'cURL error: ' . curl_error($ch);
                    } else {
                        $data = json_decode($response, true);
                        $error_code = isset($data['data']['status']) && $data['data']['status'] ==401 ? 1 : '';
                        if (!empty($data) && $data !== null && empty($error_code)) {
                            //echo "<pre>";print_r($data);exit();
                            SaveWPProducts::dispatch($data,$country);
                            //$this->saveProducts($data);
                        } else {
                            $i = 400;
                            //echo 'Failed to decode JSON response.';
                        }
                    }
                    curl_close($ch);
                }
            }
        }
    }
}
