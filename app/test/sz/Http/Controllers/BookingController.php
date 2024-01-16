<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\Coupon;
use App\Models\ExtraService;
use App\Models\GeneralSetting;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\AdminNotification;
use App\Models\User;
use Carbon\Carbon;
use App\Models\GatewayCurrency;
use App\Models\Deposit;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
	public function __construct(){
        $this->activeTemplate = activeTemplate();
    }
    
    public function serviceBooking($slug, $id)
    {
        if(session()->has('coupon')){
            session()->forget('coupon');
        }
        $coupon = Coupon::where('status', 1)->get();
    	$pageTitle = "Service Booking";
    	$service = Service::where('status', 1)->whereHas('category', function($q){
            $q->where('status', 1);
        })->where('id', decrypt($id))->firstOrFail();
        return view($this->activeTemplate. 'service_booking', compact('pageTitle', 'service', 'coupon'));
    }


    public function applyCoupon(Request $request)
    {
        $request->validate([
            'serviceId' => 'required|exists:services,id',
        ]);
        if (session('coupon')) {
            $notify = 'The coupon has already been applied';
            return response()->json(['error'=>$notify]);
        }
        $coupon = Coupon::where('code', $request->couponCode)->where('status', 1)->first();
        if(!$coupon || $coupon->code !== $request->couponCode)
        {
            $notify = 'This coupon doesn\'t exist';
            return response()->json(['error'=>$notify]);
        }
        $service = Service::findOrFail($request->serviceId);
        $total = $service->price * $request->qty;

        $response = [
            'code'      => $coupon->code,
            'amount'    => getAmount($coupon->discount($total)),
        ];
        session()->put('coupon', $coupon->code);
        $response['success'] = 'Coupon has applied successfully';
        return response()->json($response);
    }

    public function serviceBooked(Request $request)
    {
        $request->validate([
            'serviceId' => 'required|exists:services,id',
            'qty' => 'required|min:1|max:30',
            'payment' => 'required|in:wallet,checkout,payment-when-delivery'
        ]);
        $user = Auth::user();
        $service = Service::where('status', 1)->where('id', $request->serviceId)->firstOrFail();
        if($service->user_id == $user->id){
            $notify[] = ["error","You can not be booked your self-service"];
            return back()->withNotify($notify);
        }
        if($request->payment == "wallet"){
            $this->orderWithWallet($service->id, $request->qty, $request->extraservice);
            return back();
        }
        elseif($request->payment == "checkout"){
            $this->orderWithCheckout($service->id, $request->qty, $request->extraservice);
            return redirect()->route('user.payment.method');
        }
        elseif($request->payment == "payment-when-delivery"){
            dd("hi");
        }
        else{
            $notify[] = ["error","Something is wrong"];
            return back()->withNotify($notify);
        }
    }

    private function orderWithWallet($serviceId, $qty, $extraService){
        $general = GeneralSetting::first();
        $user = Auth::user();
        $extraPrice = 0;
        $discount = 0;
        $service = Service::findOrFail($serviceId);
        if($extraService)
        {
            $extraArray = explode(",",$extraService);
            foreach ($extraArray as $newValue) {
                $extra = ExtraService::where('id',$newValue)->where('service_id', $service->id)->firstOrFail();
                $extraPrice += $extra->price;
            }
        }
        $serviceTotalPrice = $service->price * $qty;
        if(session()->has('coupon'))
        {
            $coupon   = Coupon::where('code', session()->get('coupon'))->where('status', 1)->first();
            if($coupon){
                $discount = getAmount($coupon->discount($serviceTotalPrice));
            }
            session()->forget('coupon');
        }
        $totalPrice = (($serviceTotalPrice + $extraPrice) - $discount);
        if($totalPrice > $user->balance)
        {
            $notify[] = ['error', 'Your account '.getAmount($user->balance).' '.$general->cur_text.' balance not enough! please deposit money'];
            return back()->withNotify($notify);
        }
        $booking = new Booking();
        $booking->user_id = $user->id;
        $booking->service_id = $service->id;
        $booking->qty = $qty;
        $booking->amount = $totalPrice;
        $booking->discount = $discount;
        $booking->order_number = getTrx();
        $booking->extra_service = $extraService;
        $booking->status = 1;
        $booking->updated_at = Carbon::now();
        $booking->status_updated_at = Carbon::now();
        $booking->save();

        $user->balance -= $booking->amount;
        $user->save();

        $transaction = new Transaction();
        $transaction->user_id = $booking->user_id;
        $transaction->amount = $booking->amount;
        $transaction->post_balance = $user->balance;
        $transaction->trx_type = '-';
        $transaction->trx = $booking->order_number;
        $transaction->details = "Service booking payment";
        $transaction->save();

        $adminNotification = new AdminNotification();
        $adminNotification->user_id = $user->id;
        $adminNotification->title = 'Service booking payment '.$user->username;
        $adminNotification->click_url = urlPath('admin.booking.service.details', $booking->id);
        $adminNotification->save();

        $serviceUser = User::where('id', $service->user_id)->first();
        notify($serviceUser, 'SERVICE_BOOKING', [
            'order_number' => $booking->order_number, 
            'amount' => getAmount($booking->amount),
            'currency' => $general->cur_text,
        ]);

        notify($user, 'PAYMENT_COMPLETE', [
            'amount' => getAmount($booking->amount),
            'currency' => $general->cur_text,
            'order_number' => $booking->order_number,
            'post_balance' => getAmount($user->balance)
        ]);
        $notify[] = ["success","Service booking has been created"];
        return redirect()->route('user.home')->withNotify($notify);
    }

    private function orderWithCheckout($serviceId, $qty, $extraService){
        $general = GeneralSetting::first();
        $user = Auth::user();
        $extraPrice = 0;
        $discount = 0;
        $service = Service::findOrFail($serviceId);
        if($extraService)
        {
            $extraArray = explode(",",$extraService);
            foreach ($extraArray as $newValue) {
                $extra = ExtraService::findOrFail($newValue);
                $extraPrice += $extra->price;
            }
        }
        $serviceTotalPrice = $service->price * $qty;
        if(session()->has('coupon'))
        {
            $coupon   = Coupon::where('code', session()->get('coupon'))->where('status', 1)->first();
            if($coupon)
            {
                $discount = getAmount($coupon->discount($serviceTotalPrice));
            }
            session()->forget('coupon');
        }
        $totalPrice = (($serviceTotalPrice + $extraPrice) - $discount);
        $booking = new Booking();
        $booking->user_id = $user->id;
        $booking->service_id = $service->id;
        $booking->qty = $qty;
        $booking->amount = $totalPrice;
        $booking->discount = $discount;
        $booking->order_number = getTrx();
        $booking->extra_service = $extraService;
        $booking->status = 0; 
        $booking->updated_at = Carbon::now();
        $booking->status_updated_at = Carbon::now(); 
        $booking->save();
        session()->put('booking',$booking->order_number);
        return back();
    }



    public function payment()
    {
        $gatewayCurrency = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', 1);
        })->with('method')->orderby('method_code')->get();
        $pageTitle = 'Payment Methods';
        return view($this->activeTemplate . 'user.payment', compact('gatewayCurrency', 'pageTitle'));
    }



    public function paymentInsert(Request $request)
    {

        $request->validate([
            'booking_number' => 'required|exists:bookings,order_number',
            'method_code' => 'required',
            'currency' => 'required',
        ]);
        $booking = Booking::where('status', 0)->where('order_number', $request->booking_number)->first();
        if(!$booking){
            $notify[] = ['error', 'Invalid booking number'];
            return back()->withNotify($notify);
        }
        $user = auth()->user();
        $gate = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', 1);
        })->where('method_code', $request->method_code)->where('currency', $request->currency)->first();
        if (!$gate) {
            $notify[] = ['error', 'Invalid gateway'];
            return back()->withNotify($notify);
        }

        if ($gate->min_amount > $booking->amount || $gate->max_amount < $booking->amount) {
            $notify[] = ['error', 'Please follow deposit limit'];
            return back()->withNotify($notify);
        }

        $charge = $gate->fixed_charge + ($booking->amount * $gate->percent_charge / 100);
        $payable = $booking->amount + $charge;
        $final_amo = $payable * $gate->rate;

        $data = new Deposit();
        $data->user_id = $user->id;
        $data->booking_id = $booking->id;
        $data->method_code = $gate->method_code;
        $data->method_currency = strtoupper($gate->currency);
        $data->amount = $booking->amount;
        $data->charge = $charge;
        $data->rate = $gate->rate;
        $data->final_amo = $final_amo;
        $data->btc_amo = 0;
        $data->btc_wallet = "";
        $data->trx = $booking->order_number;
        $data->try = 0;
        $data->status = 0;
        $data->save();
        session()->put('Track', $data->trx);
        return redirect()->route('user.deposit.preview');
    }
}
