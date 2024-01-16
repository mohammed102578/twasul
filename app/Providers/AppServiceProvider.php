<?php

namespace App\Providers;

use App\Models\AdminNotification;
use App\Models\Deposit;
use App\Models\Frontend;
use App\Models\GeneralSetting;
use App\Models\Language;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Category;
use App\Models\Features;
use App\Models\Rank;
use App\Models\Service;
use App\Models\Software;
use App\Models\Job;
use App\Models\Withdrawal;
use App\Models\ProductType;
use Exception;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class AppServiceProvider extends ServiceProvider
{
    protected $code;
    public function __construct()
    {
        
    }
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        
        // $this->app['request']->server->set('HTTPS', true);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $activeTemplate = activeTemplate();
        $general = GeneralSetting::first();
       $code = "";
    
       $name = cache('code');
       if(!empty($name )&&isset($name)&&!is_null($name)){
        $code = cache($name);
    }
   
        $info['code'] = $code;
        $currency_code = [
            "AE"=> ["AED",3.67],
            "SA"=>["SAR",3.75],
            "EG"=>["EGP",30.91]
        ];
        try{ 
            if(!empty($info['code'])&&isset($info['code'])&&!is_null($info['code'])){
            if(array_key_exists(strtoupper($info['code']) ,$currency_code)){
                $viewShare['code']=[
                    'rate'=>$currency_code[$info['code']][1],
                    'currency'=> $currency_code[$info['code']][0]
                ];
            }else{
                $viewShare['code']=[
                    'rate'=>1,
                    'currency'=> $general->cur_sym
                ];
            }
        }else{
            $viewShare['code']=[
                'rate'=>1,
                'currency'=> $general->cur_sym
            ];
        };
        }catch(Exception $e){
            $viewShare['code']=[
                'rate'=>1,
                'currency'=> $general->cur_sym
            ];
        }
       
        $viewShare['paginator']=Paginator::useBootstrap();
        $viewShare['general'] = $general;
        $viewShare['activeTemplate'] = $activeTemplate;
        $viewShare['activeTemplateTrue'] = activeTemplate(true);
        $viewShare['language'] = Language::all();
        $viewShare['categorys'] = Category::where('status', 1)->orderby('id', 'DESC')->inRandomOrder()->get();
        $viewShare['ranks'] = Rank::where('status', 1)->get();
        $viewShare['features'] = Features::latest()->get();
        $viewShare['productTypes'] = ProductType::latest()->get();
        $viewShare['fservices'] = Service::where('status', 1)->where('featured', 1)->whereHas('category', function($q){
            $q->where('status', 1);
        })->paginate(4);
        $viewShare['countries']= json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $viewShare['shippingTypes']=['Self Shipping','I`m free Shipping','External Shipping(Soon)'];
        
         //======================Sorting Country by largest products
         $SQLcountry=Software::whereNotNull('available_in_country')->get();
         $country_array=array();
                    foreach($SQLcountry as $key => $country){
                      $a=explode(",",$country->available_in_country); 
                      $x=count($a);
                        for ($y=0; $y<$x; $y++) {
                            array_push($country_array,$a[$y]);
                            }
                    }
        $ss=array_count_values($country_array);
        arsort($ss);
        $viewShare['countriesls'] = collect($ss);
        //======================Sorting Country by largest products

        view()->share($viewShare);
        
        view()->composer('admin.partials.sidenav', function ($view) {
            $view->with([
                'banned_users_count'           => User::banned()->count(),
                'email_unverified_users_count' => User::emailUnverified()->count(),
                'sms_unverified_users_count'   => User::smsUnverified()->count(),
                'pending_ticket_count'         => SupportTicket::whereIN('status', [0,2])->count(),
                'pending_deposits_count'    => Deposit::pending()->count(),
                'pending_withdraw_count'    => Withdrawal::pending()->count(),
                'servicePending'    => Service::where('status', 0)->count(),
                'softwarePending'    => Software::where('status', 0)->count(),
                'jobPending'    => Job::where('status', 0)->count(),
            ]);
        });

        view()->composer('admin.partials.topnav', function ($view) {
            $view->with([
                'adminNotifications'=>AdminNotification::where('read_status',0)->with('user')->orderBy('id','desc')->get(),
            ]);
        });


        view()->composer('partials.seo', function ($view) {
            $seo = Frontend::where('data_keys', 'seo.data')->first();
            $view->with([
                'seo' => $seo ? $seo->data_values : $seo,
            ]);
        });

        // if($general->force_ssl){
        //     \URL::forceScheme('https');
        // }


        Paginator::useBootstrap();
        
    }
}
