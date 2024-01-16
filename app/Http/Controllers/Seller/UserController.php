<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Lib\GoogleAuthenticator;
use App\Models\AdminNotification;
use App\Models\GeneralSetting;
use App\Models\Transaction;
use App\Models\WithdrawMethod;
use App\Models\Withdrawal;
use App\Models\SubCategory;
use App\Models\FavoriteItem;
use App\Models\Service;
use App\Models\Job;
use App\Models\User;
use App\Models\Reward;
use App\Models\RewardReview;


use App\Models\Software;
use App\Models\Booking;
use App\Models\GatewayCurrency;
use App\Rules\FileTypeValidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function __construct()
    {
        $this->activeTemplate = activeTemplate();
    }

    public function home()
    {

        $user = Auth::user();
        $pageTitle = 'Dashboard';
        $emptyMessage = "No data found";
        $transactions = Transaction::where('user_id', $user->id)->orderBy('id', 'DESC')->limit(5)->get();
        $totaltransactions = Transaction::where('user_id', $user->id)->count();
        $totalJob = Job::where('user_id', $user->id)->count();
        $serviceBookings = Booking::where('user_id', $user->id)->where('status', '!=', 0)->whereNotNull('service_id')->count();
        $softwarePurchases = Booking::where('user_id', $user->id)->where('status', '!=', 0)->whereNotNull('software_id')->count();
        // $softwarePurchases = Booking::where('user_id', $user->id)->where('status', '!=', 0)->whereNotNull('software_id')->OrwhereNotNull('service_id')->count();
        $hireEmploys = Booking::where('user_id', $user->id)->where('status', '!=', 0)->whereNotNull('job_biding_id')->count();
        $totalService = Service::where('user_id', $user->id)->count();
        $totalSoftware = Software::where('user_id', $user->id)->count();
        $totalServiceBooking = Booking::whereHas('service', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->where('status', '!=', '0')->whereNotNull('service_id')->count();
        $totalSoftwareBooking = Booking::whereHas('software', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->where('status', '!=', '0')->whereNotNull('software_id')->count();
        $withdrawAmount = Withdrawal::where('user_id', Auth::id())->where('status', '!=', 0)->sum('amount');
        $totalSale =$totalServiceBooking + $totalSoftwareBooking;
        $totalReferrals = User::where('ref_by', $user->id)->count();
        $reward = Reward::where('reward_category', '5')->where('end_date', '>=', date('Y-m-d'))->first(); // 5= Referral
        $profitAbleReferral = 0;
        $totalRewaredReferrals = 0;
        $leftReferrals = 0;
        $totalProfitFromReferral = 0;
        if ($reward) {
            $profitAbleReferral = round($totalReferrals / $reward->number_of_referrals);
            $totalProfitFromReferral = $profitAbleReferral * $reward->profit;
            $leftReferrals = $totalReferrals % $reward->number_of_referrals;
            $totalRewaredReferrals = $totalReferrals - $leftReferrals;
        }
        $rewards = Reward::where('reward_category', '5')
            ->where('end_date', '>=', date('Y-m-d'))
            ->get(); // 5= Referral

        $totalReward = RewardReview::where('user_id', $user->id)->where('status', 1)->sum('total_profit_amount');
        /*0=pending,1=approved,2=declined*/

        return view($this->activeTemplate . 'user.seller.dashboard', compact('pageTitle', 'totalReward', 'totalReferrals', 'totalRewaredReferrals', 'leftReferrals', 'rewards', 'reward', 'profitAbleReferral', 'totalProfitFromReferral', 'transactions', 'emptyMessage', 'withdrawAmount', 'totalService', 'totalSoftware', 'totalServiceBooking', 'totalSoftwareBooking', 'totaltransactions', 'totalJob', 'serviceBookings', 'softwarePurchases', 'hireEmploys','totalSale'));
    }

    public function profile()
    {
        $pageTitle = "Profile Setting";
        $user = Auth::user();
        $info = json_decode(json_encode(getIpInfo()), true);
        if ($info) {
            if (is_array($info['code'])) {
                $mobile_code = @implode(',', $info['code']);
            } else {
                $mobile_code = '';
            }
            $countries = json_decode(file_get_contents(resource_path('views/partials/country.json')));
        }
        return view($this->activeTemplate . 'user.seller.profile_setting', compact('pageTitle', 'user', 'mobile_code', 'countries'));
    }

    public function submitProfile(Request $request)
    {

        $countryData = (array) json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $countryCodes = implode(',', array_keys($countryData));
        $mobileCodes = implode(',', array_column($countryData, 'dial_code'));
        $countries = implode(',', array_column($countryData, 'country'));
        $user = Auth::user();
        $id = $user->id;
        $request->validate([
            'firstname' => 'required|string|max:50',
            'lastname' => 'required|string|max:50',
            'address' => 'sometimes|required|max:80',
            'mobile' => 'required|string|max:15|unique:users,mobile,' . ($id ? $id : ''),
            'designation' => 'required|max:255',
            'state' => 'sometimes|required|max:80',
            'zip' => 'sometimes|required|max:40',
            'city' => 'sometimes|required|max:50',
            'mobile_code' => 'required|in:' . $mobileCodes,
            'country_code' => 'required|in:' . $countryCodes,
            'image' => ['image', new FileTypeValidate(['jpg', 'jpeg', 'png'])]
        ], [
            'firstname.required' => 'First name field is required',
            'lastname.required' => 'Last name field is required'
        ]);

        $in['firstname'] = $request->firstname;
        $in['lastname'] = $request->lastname;
        $in['designation'] = $request->designation;
        $in['mobile'] = $request->mobile;
        $in['about_me'] = $request->about_me;
        $in['country_code'] = $request->country_code;
        $in['address'] = [
            'address' => $request->address,
            'state' => $request->state,
            'zip' => $request->zip,
            'country' => @$user->address->country,
            'city' => $request->city,
        ];
        if ($request->hasFile('image')) {
            $location = imagePath()['profile']['user']['path'];
            $size = imagePath()['profile']['user']['size'];
            $filename = uploadImage($request->image, $location, $size, $user->image);
            $in['image'] = $filename;
        }
        if ($request->hasFile('bg_image')) {
            $location = imagePath()['profile']['user_bg']['path'];
            $size = imagePath()['profile']['user_bg']['size'];
            $filename = uploadImage($request->bg_image, $location, $size, $user->bg_image);
            $in['bg_image'] = $filename;
        }
        $user->fill($in)->save();

        if(isset($user->country_code)&&!empty($user->country_code)){
            cache(['code'=> $user->username],100000);
            cache([''.$user->username => $user->country_code ]);
        }
        $notify[] = ['success', 'Profile updated successfully.'];
        return back()->withNotify($notify);
    }

    public function changePassword()
    {
        $pageTitle = 'Change password';
        return view($this->activeTemplate . 'user.seller.password', compact('pageTitle'));
    }

    public function submitPassword(Request $request)
    {

        $password_validation = Password::min(6);
        $general = GeneralSetting::first();
        $password_validation = '';
        // if ($general->secure_password) {
        //     $password_validation = $password_validation->mixedCase()->numbers()->symbols()->uncompromised();
        // }

        $this->validate($request, [
            'current_password' => 'required',
            'password' => ['required', 'min:6', 'confirmed', $password_validation]
        ]);


        try {
            $user = auth()->user();
            if (Hash::check($request->current_password, $user->password)) {
                $password = Hash::make($request->password);
                $user->password = $password;
                $user->save();
                $notify[] = ['success', 'Password changes successfully.'];
                return back()->withNotify($notify);
            } else {
                $notify[] = ['error', 'The password doesn\'t match!'];
                return back()->withNotify($notify);
            }
        } catch (\PDOException $e) {
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify);
        }
    }


    /*
     * Withdraw Operation
     */

    public function withdrawMoney()
    {
        $withdrawMethod = WithdrawMethod::where('status', 1)->get();
        $pageTitle = 'Withdraw Money';
        $emptyMessage = "No data found";
        return view($this->activeTemplate . 'user.withdraw.methods', compact('pageTitle', 'withdrawMethod', 'emptyMessage'));
    }
    public function payment()
    {
        $withdrawMethod = WithdrawMethod::where('status', 1)->get();
        $gatewayCurrency = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', 1);
        })->with('method')->orderby('method_code')->get();
        
        $pageTitle = 'Payment';
        $emptyMessage = "No data found";
        return view($this->activeTemplate . 'user.payment.index', compact('pageTitle', 'withdrawMethod', 'emptyMessage','gatewayCurrency'));
    }



    public function withdrawStore(Request $request)
    {
        $this->validate($request, [
            'method_code' => 'required',
            'amount' => 'required|numeric'
        ]);
        $method = WithdrawMethod::where('id', $request->method_code)->where('status', 1)->firstOrFail();
        $user = auth()->user();
        if ($request->amount < $method->min_limit) {
            $notify[] = ['error', 'Your requested amount is smaller than minimum amount.'];
            return back()->withNotify($notify);
        }
        if ($request->amount > $method->max_limit) {
            $notify[] = ['error', 'Your requested amount is larger than maximum amount.'];
            return back()->withNotify($notify);
        }

        if ($request->amount > $user->balance) {
            $notify[] = ['error', 'You do not have sufficient balance for withdraw.'];
            return back()->withNotify($notify);
        }


        $charge = $method->fixed_charge + ($request->amount * $method->percent_charge / 100);
        $afterCharge = $request->amount - $charge;
        $finalAmount = $afterCharge * $method->rate;

        $withdraw = new Withdrawal();
        $withdraw->method_id = $method->id; // wallet method ID
        $withdraw->user_id = $user->id;
        $withdraw->amount = $request->amount;
        $withdraw->currency = $method->currency;
        $withdraw->rate = $method->rate;
        $withdraw->charge = $charge;
        $withdraw->final_amount = $finalAmount;
        $withdraw->after_charge = $afterCharge;
        $withdraw->trx = getTrx();
        $withdraw->save();
        session()->put('wtrx', $withdraw->trx);
        return redirect()->route('user.withdraw.preview');
    }

    public function withdrawPreview()
    {
        $withdraw = Withdrawal::with('method', 'user')->where('trx', session()->get('wtrx'))->where('status', 0)->orderBy('id', 'desc')->firstOrFail();
        $pageTitle = 'Withdraw Preview';
        return view($this->activeTemplate . 'user.withdraw.preview', compact('pageTitle', 'withdraw'));
    }


    public function withdrawSubmit(Request $request)
    {
        $general = GeneralSetting::first();
        $withdraw = Withdrawal::with('method', 'user')->where('trx', session()->get('wtrx'))->where('status', 0)->orderBy('id', 'desc')->firstOrFail();

        $rules = [];
        $inputField = [];
        if ($withdraw->method->user_data != null) {
            foreach ($withdraw->method->user_data as $key => $cus) {
                $rules[$key] = [$cus->validation];
                if ($cus->type == 'file') {
                    array_push($rules[$key], 'image');
                    array_push($rules[$key], new FileTypeValidate(['jpg', 'jpeg', 'png']));
                    array_push($rules[$key], 'max:2048');
                }
                if ($cus->type == 'text') {
                    array_push($rules[$key], 'max:191');
                }
                if ($cus->type == 'textarea') {
                    array_push($rules[$key], 'max:300');
                }
                $inputField[] = $key;
            }
        }

        $this->validate($request, $rules);

        $user = auth()->user();
        if ($user->ts) {
            $response = verifyG2fa($user, $request->authenticator_code);
            if (!$response) {
                $notify[] = ['error', 'Wrong verification code'];
                return back()->withNotify($notify);
            }
        }


        if ($withdraw->amount > $user->balance) {
            $notify[] = ['error', 'Your request amount is larger then your current balance.'];
            return back()->withNotify($notify);
        }

        $directory = date("Y") . "/" . date("m") . "/" . date("d");
        $path = imagePath()['verify']['withdraw']['path'] . '/' . $directory;
        $collection = collect($request);
        $reqField = [];
        if ($withdraw->method->user_data != null) {
            foreach ($collection as $k => $v) {
                foreach ($withdraw->method->user_data as $inKey => $inVal) {
                    if ($k != $inKey) {
                        continue;
                    } else {
                        if ($inVal->type == 'file') {
                            if ($request->hasFile($inKey)) {
                                try {
                                    $reqField[$inKey] = [
                                        'field_name' => $directory . '/' . uploadImage($request[$inKey], $path),
                                        'type' => $inVal->type,
                                    ];
                                } catch (\Exception $exp) {
                                    $notify[] = ['error', 'Could not upload your ' . $request[$inKey]];
                                    return back()->withNotify($notify)->withInput();
                                }
                            }
                        } else {
                            $reqField[$inKey] = $v;
                            $reqField[$inKey] = [
                                'field_name' => $v,
                                'type' => $inVal->type,
                            ];
                        }
                    }
                }
            }
            $withdraw['withdraw_information'] = $reqField;
        } else {
            $withdraw['withdraw_information'] = null;
        }


        $withdraw->status = 2;
        $withdraw->save();
        $user->balance -= $withdraw->amount;
        $user->save();



        $transaction = new Transaction();
        $transaction->user_id = $withdraw->user_id;
        $transaction->amount = $withdraw->amount;
        $transaction->post_balance = $user->balance;
        $transaction->charge = $withdraw->charge;
        $transaction->trx_type = '-';
        $transaction->details = getAmount($withdraw->final_amount) . ' ' . $withdraw->currency . ' Withdraw Via ' . $withdraw->method->name;
        $transaction->trx = $withdraw->trx;
        $transaction->save();

        $adminNotification = new AdminNotification();
        $adminNotification->user_id = $user->id;
        $adminNotification->title = 'New withdraw request from ' . $user->username;
        $adminNotification->click_url = urlPath('admin.withdraw.details', $withdraw->id);
        $adminNotification->save();

        notify($user, 'WITHDRAW_REQUEST', [
            'method_name' => $withdraw->method->name,
            'method_currency' => $withdraw->currency,
            'method_amount' => getAmount($withdraw->final_amount),
            'amount' => getAmount($withdraw->amount),
            'charge' => getAmount($withdraw->charge),
            'currency' => $general->cur_text,
            'rate' => getAmount($withdraw->rate),
            'trx' => $withdraw->trx,
            'post_balance' => getAmount($user->balance),
            'delay' => $withdraw->method->delay
        ]);

        $notify[] = ['success', 'Withdraw request sent successfully'];
        return redirect()->route('user.withdraw.history')->withNotify($notify);
    }

    public function withdrawLog()
    {
       
        $pageTitle = "Withdraw Log";
        $withdraws = Withdrawal::where('user_id', Auth::id())->where('status', '!=', 0)->with('method')->orderBy('id', 'desc')->paginate(getPaginate());
        $emptyMessage = "No data found";
        $data['emptyMessage'] = "No Data Found!";
        return view($this->activeTemplate . 'user.withdraw.log', compact('pageTitle', 'withdraws', 'emptyMessage'));
    }


  public function history()
    {
        $user = Auth::user();

        $pageTitle = "History";
        $withdraws = Withdrawal::where('user_id', Auth::id())->where('status', '!=', 0)->with('method')->orderBy('id', 'desc')->paginate(getPaginate());
        $logs = auth()->user()->deposits()->with(['gateway'])->orderBy('id', 'desc')->paginate(getPaginate());
        $transactions = Transaction::where('user_id', $user->id)->paginate(getPaginate());
        
        $emptyMessage = "No data found";
        $data['emptyMessage'] = "No Data Found!";
        return view($this->activeTemplate . 'user.log.payment', compact('pageTitle', 'withdraws', 'emptyMessage','logs','transactions'));
    }

    public function show2faForm()
    {
        $general = GeneralSetting::first();
        $ga = new GoogleAuthenticator();
        $user = auth()->user();
        $secret = $ga->createSecret();
        $qrCodeUrl = $ga->getQRCodeGoogleUrl($user->username . '@' . $general->sitename, $secret);
        $pageTitle = 'Two Factor';
        return view($this->activeTemplate . 'user.seller.twofactor', compact('pageTitle', 'secret', 'qrCodeUrl'));
    }

    public function create2fa(Request $request)
    {
        $user = auth()->user();
        $this->validate($request, [
            'key' => 'required',
            'code' => 'required',
        ]);
        $response = verifyG2fa($user, $request->code, $request->key);
        if ($response) {
            $user->tsc = $request->key;
            $user->ts = 1;
            $user->save();
            $userAgent = getIpInfo();
            $osBrowser = osBrowser();
            notify($user, '2FA_ENABLE', [
                'operating_system' => @$osBrowser['os_platform'],
                'browser' => @$osBrowser['browser'],
                'ip' => @$userAgent['ip'],
                'time' => @$userAgent['time']
            ]);
            $notify[] = ['success', 'Google authenticator enabled successfully'];
            return back()->withNotify($notify);
        } else {
            $notify[] = ['error', 'Wrong verification code'];
            return back()->withNotify($notify);
        }
    }


    public function disable2fa(Request $request)
    {
        $this->validate($request, [
            'code' => 'required',
        ]);

        $user = auth()->user();
        $response = verifyG2fa($user, $request->code);
        if ($response) {
            $user->tsc = null;
            $user->ts = 0;
            $user->save();
            $userAgent = getIpInfo();
            $osBrowser = osBrowser();
            notify($user, '2FA_DISABLE', [
                'operating_system' => @$osBrowser['os_platform'],
                'browser' => @$osBrowser['browser'],
                'ip' => @$userAgent['ip'],
                'time' => @$userAgent['time']
            ]);
            $notify[] = ['success', 'Two factor authenticator disable successfully'];
        } else {
            $notify[] = ['error', 'Wrong verification code'];
        }
        return back()->withNotify($notify);
    }


    public function category(Request $request)
    {
        $sub_category = SubCategory::where('category_id', $request->category)->get();
        if ($sub_category->isEmpty()) {
            return response()->json(['error' => "Sub category not available under this category"]);
        } else {
            return response()->json($sub_category);
        }
    }


    public function serviceFavorite(Request $request)
    {
        $user = Auth::user();
        $service = Service::find($request->id);
        if (!$service) {
            return response()->json(['error' => 'Invalid service']);
        }
        if ($service->user_id == $user->id) {
            return response()->json(['error' => 'You can not be added your self service']);
        }
        $favorite = FavoriteItem::where('service_id', $service->id)->where('user_id', $user->id)->first();
        if ($favorite) {
            $service->favorite -= 1;
            $service->save();
            $favorite->delete();
            return response()->json(['success' => 'Service remove to your favorites item']);
        } else {
            $favoriteItem = new FavoriteItem();
            $favoriteItem->user_id = $user->id;
            $favoriteItem->service_id = $service->id;
            $favoriteItem->save();

            $service->favorite += 1;
            $service->save();
            return response()->json(['success' => 'Service added to your favorites item']);
        }
    }

    public function softwareFavorite(Request $request)
    {
        $user = Auth::user();
        $software = Software::find($request->id);
        if (!$software) {
            return response()->json(['error' => 'Invalid software']);
        }
        if ($software->user_id == $user->id) {
            return response()->json(['error' => 'You can not be added your self software']);
        }
        $favorite = FavoriteItem::where('software_id', $software->id)->where('user_id', $user->id)->first();
        if ($favorite) {
            $software->favorite -= 1;
            $software->save();
            $favorite->delete();
            return response()->json(['success' => 'Software remove to your favorites item']);
        } else {
            $favoriteItem = new FavoriteItem();
            $favoriteItem->user_id = $user->id;
            $favoriteItem->software_id = $software->id;
            $favoriteItem->save();

            $software->favorite += 1;
            $software->save();
            return response()->json(['success' => 'Software added to your favorites item']);
        }
    }
}