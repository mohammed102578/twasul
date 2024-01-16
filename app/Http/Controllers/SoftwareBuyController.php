<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Software;
use App\Models\Coupon;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\GeneralSetting;
use App\Models\User;
use App\Models\AdminNotification;
use App\Models\GatewayCurrency;
use App\Models\Deposit;
use App\Models\ProductInventory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;


class SoftwareBuyController extends Controller
{
    public function __construct()
    {
        $this->activeTemplate = activeTemplate();
    }

    public function softwareBuy($slug, $id)
    {
        if (session()->has('coupon')) {
            session()->forget('coupon');
        }
        $pageTitle = "Product buy now";
        $coupon = Coupon::where('status', 1)->get();
        $software = Software::where('status', 1)->with('verities')->whereHas('category', function ($q) {
            $q->where('status', 1);
        })->where('id', decrypt($id))->firstOrFail();
        $countries = json_decode(file_get_contents(resource_path('views/partials/country.json')));
        return view($this->activeTemplate . 'software_buy', compact('pageTitle', 'software', 'coupon', 'countries'));
    }

    public function applyCouponSoftware(Request $request)
    {
        if (session()->has('coupon')) {
            $notify = 'The coupon has already been applied';
            return response()->json(['error' => $notify]);
        }
        $software = Software::find($request->softwareId);
        if (!$software) {
            $notify = 'Invalid Product';
            return response()->json(['error' => $notify]);
        }
        $coupon = Coupon::where('code', $request->couponCode)->where('status', 1)->first();
        if (!$coupon || $coupon->code !== $request->couponCode) {
            $notify = 'This coupon doesn\'t exist';
            return response()->json(['error' => $notify]);
        }
        $response = [
            'code'      => $coupon->code,
            'amount'    => getAmount($coupon->discount($request->subtotal)),
        ];
        session()->put('coupon', $coupon->code);
        $response['success'] = 'Coupon has applied successfully';
        return response()->json($response);
    }

    public function softwareBuyStore(Request $request)
    {

        $request->validate([
            'payment' => 'required|in:wallet,checkout,payment-when-delivery',
            'software_id' => 'required|exists:software,id',
        ]);
        $user = Auth::user();
        $software = Software::where('status', 1)->where('id', $request->software_id)->firstOrFail();
        if ($software->user_id == $user->id) {
            $notify[] = ["error", "You can not purchase your self Product"];
            return back()->withNotify($notify);
        }
        if ($request->payment == "wallet") {
            $this->orderWithWallet($software->id);
            return back();
        } elseif ($request->payment == "checkout") {
            $this->orderWithCheckout($software->id);
            return redirect()->route('user.payment.method');
        } elseif ($request->payment == "payment-when-delivery") {
            dd("lol2");
        } else {
            $notify[] = ["error", "Something is wrong"];
            return back()->withNotify($notify);
        }
    }

    private function orderWithWallet($serviceId)
    {
        $general = GeneralSetting::first();
        $user = Auth::user();
        $discount = 0;
        $software = Software::findOrFail($serviceId);
        if (session()->has('coupon')) {
            $coupon = Coupon::where('code', session()->get('coupon'))->where('status', 1)->first();
            if ($coupon) {
                $discount = getAmount($coupon->discount($software->amount));
            }
            session()->forget('coupon');
        }
        $totalPrice = (($software->amount) - $discount);
        if ($totalPrice > $user->balance) {
            $notify[] = ['error', 'Your account ' . getAmount($user->balance) . ' ' . $general->cur_text . ' balance not enough! please deposit money'];
            return back()->withNotify($notify);
        }
        $booking = new Booking();
        $booking->user_id = $user->id;
        $booking->software_id = $software->id;
        $booking->qty = 1;
        $booking->amount = $totalPrice;
        $booking->discount = $discount;
        $booking->order_number = getTrx();
        $booking->status = 3;
        $booking->updated_at = Carbon::now();
        $booking->working_status = 1;
        $booking->save();

        $user->balance -= $booking->amount;
        $user->save();

        $transaction = new Transaction();
        $transaction->user_id = $booking->user_id;
        $transaction->amount = $booking->amount;
        $transaction->post_balance = $user->balance;
        $transaction->trx_type = '-';
        $transaction->trx = $booking->order_number;
        $transaction->details = "Product purchase payment";
        $transaction->save();

        $softwareUser = User::where('id', $software->user_id)->first();
        $charge = (($booking->amount / 100) * $general->charge);
        $payableAmountUser = ($booking->amount - $charge);

        $softwareUser->balance += $payableAmountUser;
        $softwareUser->income += $payableAmountUser;
        $softwareUser->save();
        rankUser($softwareUser->id);

        $transaction = new Transaction();
        $transaction->user_id = $softwareUser->id;
        $transaction->amount = $payableAmountUser;
        $transaction->post_balance = $softwareUser->balance;
        $transaction->charge = $charge;
        $transaction->trx_type = '+';
        $transaction->trx = $booking->order_number;
        $transaction->details = "Payment for " . $booking->order_number;
        $transaction->save();


        $adminNotification = new AdminNotification();
        $adminNotification->user_id = $user->id;
        $adminNotification->title = 'Product purchase complete ' . $user->username;
        $adminNotification->click_url = urlPath('admin.sales.software.index');
        $adminNotification->save();

        notify($softwareUser, 'SOFTWARE_PURCHASE', [
            'order_number' => $booking->order_number,
            'amount' => getAmount($booking->amount),
            'currency' => $general->cur_text,
        ]);

        notify($softwareUser, 'PAYMENT_SELLER', [
            'amount' => getAmount($payableAmountUser),
            'currency' => $general->cur_text,
            'order_number' => $booking->order_number,
            'post_balance' => getAmount($softwareUser->balance)
        ]);

        notify($user, 'PAYMENT_COMPLETE', [
            'amount' => getAmount($booking->amount),
            'currency' => $general->cur_text,
            'order_number' => $booking->order_number,
            'post_balance' => getAmount($user->balance)
        ]);
        $notify[] = ["success", "Product purchase done"];
        return redirect()->route('user.home')->withNotify($notify);
    }

    private function orderWithCheckout($softwareId)
    {
        $general = GeneralSetting::first();
        $user = Auth::user();
        $discount = 0;
        $software = Software::findOrFail($softwareId);
        $softwareTotalPrice = $software->amount;
        if (session()->has('coupon')) {
            $coupon = Coupon::where('code', session()->get('coupon')['code'])->where('status', 1)->first();
            if ($coupon) {
                $discount = getAmount($coupon->discount($softwareTotalPrice));
            }
            session()->forget('coupon');
        }
        $totalPrice = ($softwareTotalPrice - $discount);
        $booking = new Booking();
        $booking->user_id = $user->id;
        $booking->software_id = $software->id;
        $booking->qty = 1;
        $booking->amount = $totalPrice;
        $booking->discount = $discount;
        $booking->order_number = getTrx();
        $booking->status = 0;
        $booking->updated_at = Carbon::now();
        $booking->save();
        session()->put('booking', $booking->order_number);
        return back();
    }

    public function softwareBuyRealProduct(Request $request)
    {

        $request->validate([
            'payment' => 'required|in:wallet,checkout,payment-when-delivery',
            'software_id' => 'required|exists:software,id',
        ]);
        $user = Auth::user();
        $software = Software::where('status', 1)->where('id', $request->software_id)->firstOrFail();
        if ($software->user_id == $user->id) {
            $notify[] = ["error", "You can not purchase your self Product"];
            return back()->withNotify($notify);
        }
        if ($request->payment == "wallet") {
            $this->orderRealWithWallet($software->id, $request);
            return back();
        } elseif ($request->payment == "checkout") {
            $this->orderRealWithCheckout($software->id, $request);
            return redirect()->route('user.payment.method');
        } elseif ($request->payment == "payment-when-delivery") {
            $this->PaymentWhenDelivry($software->id, $request);
            return back();
        } else {
            $notify[] = ["error", "Something is wrong"];
            return back()->withNotify($notify);
        }
    }

    private function orderRealWithWallet($serviceId, $request)
    {
        $general = GeneralSetting::first();
        $user = Auth::user();
        $discount = 0;
        $software = Software::findOrFail($serviceId);
        $qtys = $request->qtys;
        $ids = $request->ids;
        $total_qty = 0;
        foreach ($qtys as $qty) {
            $total_qty = $total_qty + $qty;
        }
        $total_product_price = ($software->amount) * ($total_qty);
        if (session()->has('coupon')) {
            $coupon = Coupon::where('code', session()->get('coupon'))->where('status', 1)->first();
            if ($coupon) {
                $discount = getAmount($coupon->discount($total_product_price));
            }
            session()->forget('coupon');
        }
        $totalPrice = ((($total_product_price) + ($software->shipping_charge)) - $discount);
        if ($totalPrice > $user->balance) {
            $notify[] = ['error', 'Your account ' . getAmount($user->balance) . ' ' . $general->cur_text . ' balance not enough! please deposit money'];
            return back()->withNotify($notify);
        }
        $order_number = getTrx();
        $bill_info = array(
            'name' => $request->name,
            'brand' => $request->brand,
            'mobile' => $request->mobile,
            'shipping_type' => $request->shipping_type,
            'country' => $request->country,
            'city' => $request->city,
            'address' => $request->address,
            'notes' => $request->notes
        );


        $i = 0;
        foreach ($ids as $id) {
            $productInvetory = ProductInventory::find($id);
            $booking = new Booking();
            $booking->user_id = $user->id;
            $booking->software_id = $software->id;
            $booking->product_verity_id = $id;
            $booking->product_verity_amount = $software->amount;
            $booking->qty = $qtys[$i];
            $booking->amount = $totalPrice;
            $booking->discount = ($discount / $total_qty) * $qtys[$i];
            $booking->order_number = $order_number;
            $booking->status = 3;
            $booking->updated_at = Carbon::now();
            $booking->working_status = 0;
            $booking->buyer_information = json_encode($bill_info);
            $booking->save();


            $productInvetory->inventory -= $qtys[$i];
            $productInvetory->save();

            $i++;
        }

        $user->balance -= $totalPrice;
        $user->save();

        $transaction = new Transaction();
        $transaction->user_id = $booking->user_id;
        $transaction->amount = $totalPrice;
        $transaction->post_balance = $user->balance;
        $transaction->trx_type = '-';
        $transaction->trx = $order_number;
        $transaction->details = "Payment When Delivery";
        $transaction->save();

        $softwareUser = User::where('id', $software->user_id)->first();
        $charge = (($totalPrice / 100) * $general->charge);
        $payableAmountUser = ($totalPrice - $charge);

        $softwareUser->balance += $payableAmountUser;
        $softwareUser->income += $payableAmountUser;
        $softwareUser->save();
        rankUser($softwareUser->id);

        $transaction = new Transaction();
        $transaction->user_id = $softwareUser->id;
        $transaction->amount = $payableAmountUser;
        $transaction->post_balance = $softwareUser->balance;
        $transaction->charge = $charge;
        $transaction->trx_type = '+';
        $transaction->trx = $order_number;
        $transaction->details = "Payment When Delivery for " . $order_number;
        $transaction->save();


        $adminNotification = new AdminNotification();
        $adminNotification->user_id = $user->id;
        $adminNotification->title = 'Product purchase complete ' . $user->username;
        $adminNotification->click_url = urlPath('admin.sales.software.index');
        $adminNotification->save();

        notify($softwareUser, 'SOFTWARE_PURCHASE', [
            'order_number' => $order_number,
            'amount' => getAmount($totalPrice),
            'currency' => $general->cur_text,
        ]);

        notify($softwareUser, 'PAYMENT_SELLER', [
            'amount' => getAmount($payableAmountUser),
            'currency' => $general->cur_text,
            'order_number' => $order_number,
            'post_balance' => getAmount($softwareUser->balance)
        ]);

        notify($user, 'PAYMENT_COMPLETE', [
            'amount' => getAmount($totalPrice),
            'currency' => $general->cur_text,
            'order_number' => $order_number,
            'post_balance' => getAmount($user->balance)
        ]);
        $notify[] = ["success", "Product purchase done"];
        return redirect()->route('user.home')->withNotify($notify);
    }

    private function orderRealWithCheckout($softwareId, $request)
    {
        $general = GeneralSetting::first();
        $user = Auth::user();
        $discount = 0;
        $software = Software::findOrFail($softwareId);
        $qtys = $request->qtys;
        $ids = $request->ids;
        $total_qty = 0;
        foreach ($qtys as $qty) {
            $total_qty = $total_qty + $qty;
        }
        $softwareTotalPrice = $software->amount * $total_qty;
        if (session()->has('coupon')) {
            $coupon = Coupon::where('code', session()->get('coupon')['code'])->where('status', 1)->first();
            if ($coupon) {
                $discount = getAmount($coupon->discount($softwareTotalPrice));
            }
            session()->forget('coupon');
        }
        $totalPrice = ($softwareTotalPrice + ($software->shipping_charge) - $discount);
        $order_number = getTrx();
        $bill_info = array(
            'name' => $request->name,
            'brand' => $request->brand,
            'mobile' => $request->mobile,
            'shipping_type' => $request->shipping_type,
            'country' => $request->country,
            'city' => $request->city,
            'address' => $request->address,
            'notes' => $request->notes
        );
        $i = 0;
        foreach ($ids as $id) {
            $productInvetory = ProductInventory::find($id);
            $booking = new Booking();
            $booking->user_id = $user->id;
            $booking->software_id = $software->id;
            $booking->product_verity_id = $id;
            $booking->product_verity_amount = $software->amount;
            $booking->qty = $qtys[$i];
            $booking->amount = $totalPrice;
            $booking->discount = ($discount / $total_qty) * $qty[$i];
            $booking->order_number = $order_number;
            $booking->status = 0;
            $booking->working_status = 0;
            $booking->updated_at = Carbon::now();
            $booking->buyer_information = json_encode($bill_info);
            $booking->save();

            $productInvetory->inventory -= $qtys[$i];
            $productInvetory->save();
            $i++;
        }
        session()->put('booking', $order_number);

        return back();
    }



    private function PaymentWhenDelivry($softwareId, $request)
    {
        $general = GeneralSetting::first();
        $user = Auth::user();
        $discount = 0;
        $software = Software::findOrFail($softwareId);
        $qtys = $request->qtys;
        $ids = $request->ids;
        $total_qty = 0;
        foreach ($qtys as $qty) {
            $total_qty = $total_qty + $qty;
        }
        $total_product_price = ($software->amount) * ($total_qty);
        if (session()->has('coupon')) {
            $coupon = Coupon::where('code', session()->get('coupon'))->where('status', 1)->first();
            if ($coupon) {
                $discount = getAmount($coupon->discount($total_product_price));
            }
            session()->forget('coupon');
        }
        $totalPrice = ((($total_product_price) + ($software->shipping_charge)) - $discount);
        // if ($totalPrice > $user->balance) {
        //     $notify[] = ['error', 'Your account ' . getAmount($user->balance) . ' ' . $general->cur_text . ' balance not enough! please deposit money'];
        //     return back()->withNotify($notify);
        // }
        $order_number = getTrx();
        $bill_info = array(
            'name' => $request->name,
            'brand' => $request->brand,
            'mobile' => $request->mobile,
            'shipping_type' => $request->shipping_type,
            'country' => $request->country,
            'city' => $request->city,
            'address' => $request->address,
            'notes' => $request->notes
        );


        $i = 0;
        foreach ($ids as $id) {
            $productInvetory = ProductInventory::find($id);
            $booking = new Booking();
            $booking->user_id = $user->id;
            $booking->software_id = $software->id;
            $booking->product_verity_id = $id;
            $booking->product_verity_amount = $software->amount;
            $booking->qty = $qtys[$i];
            $booking->amount = $totalPrice;
            $booking->discount = ($discount / $total_qty) * $qtys[$i];
            $booking->order_number = $order_number;
            $booking->status = 3;
            $booking->updated_at = Carbon::now();
            $booking->working_status = 0;
            $booking->buyer_information = json_encode($bill_info);
            $booking->save();


            $productInvetory->inventory -= $qtys[$i];
            $productInvetory->save();

            $i++;
        }
        $transaction = new Transaction();
        $transaction->user_id = $booking->user_id;
        $transaction->amount = $totalPrice;
        $transaction->post_balance = $user->balance;
        $transaction->trx_type = '-';
        $transaction->trx = $order_number;
        $transaction->details = "Product purchase payment";
        $transaction->save();

        $softwareUser = User::where('id', $software->user_id)->first();
        $charge = (($totalPrice / 100) * $general->charge);
        $payableAmountUser = ($totalPrice - $charge);

        $transaction = new Transaction();
        $transaction->user_id = $softwareUser->id;
        $transaction->amount = $payableAmountUser;
        $transaction->post_balance = $softwareUser->balance;
        $transaction->charge = $charge;
        $transaction->trx_type = '+';
        $transaction->trx = $order_number;
        $transaction->details = "Payment for " . $order_number;
        $transaction->save();
      


        $notify[] = ["success", "payment when delivery"];
        return redirect()->route('user.home')->withNotify($notify);
    }
}
