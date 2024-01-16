<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JobBiding;
use App\Models\Service;
use App\Models\GeneralSetting;
use App\Models\User;
use App\Models\Booking;
use Carbon\Carbon;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class BidingOrderController extends Controller
{

    public function __construct()
    {
        $this->activeTemplate = activeTemplate();
    }

    public function jobBiding($slug, $id)
    {
        $user = Auth::user();
        $pageTitle = "Job biding order";
        $jobBiding = JobBiding::where('id', decrypt($id))->firstOrFail();
        $otherServices = Service::where('status', 1)->whereHas('category', function ($q) {
            $q->where('status', 1);
        })->where('user_id', $jobBiding->user_id)->with(['user'])->orderBy('id', 'DESC')->take(4)->get();
        if ($jobBiding->order_type == 1) {
            return view($this->activeTemplate . 'biding_order', compact('pageTitle', 'jobBiding', 'otherServices'));
        } else {
            if ($user->id == $jobBiding->job->user_id) {
                return view($this->activeTemplate . 'biding_order', compact('pageTitle', 'jobBiding', 'otherServices'));
            } else {
                $notify[] = ["error", "Only job posters can be hire"];
                return back()->withNotify($notify);
            }
        }
    }


    public function hireEmploy(Request $request)
    {
        $request->validate([
            'job_biding_id' => 'required|exists:job_bidings,id',
            'payment' => 'required|in:wallet,checkout,'
        ]);
        $user = Auth::user();
        $jobBiding = JobBiding::where('id', $request->job_biding_id)->firstOrFail();
        if ($jobBiding->user_id == $user->id) {
            $notify[] = ["error", "You can not hire your job biding"];
            return back()->withNotify($notify);
        }
        if ($request->payment == "wallet") {
            $this->orderWithWallet($jobBiding->id);
            return back();
        } elseif ($request->payment == "checkout") {
            $this->orderWithCheckout($jobBiding->id);
            return redirect()->route('user.payment.method');
        }  
          else {
            $notify[] = ["error", "Something is wrong"];
            return back()->withNotify($notify);
        }
    }


    private function orderWithWallet($id)
    {
        $general = GeneralSetting::first();
        $user = Auth::user();
        $jobBiding = JobBiding::findOrFail($id);
        if ($jobBiding->price > $user->balance) {
            $notify[] = ['error', 'Your account ' . getAmount($user->balance) . ' ' . $general->cur_text . ' balance not enough! please deposit money'];
            return back()->withNotify($notify);
        }
        $booking = new Booking();
        $booking->user_id = $user->id;
        $booking->job_biding_id = $jobBiding->id;
        $booking->qty = 1;
        $booking->amount = $jobBiding->price;
        $booking->discount = 0;
        $booking->order_number = getTrx();
        $booking->status = 1;
        $booking->working_status = 4;
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
        $transaction->details = "Payment for hire employ";
        $transaction->save();

        $bidingUser = User::where('id', $jobBiding->user_id)->firstOrFail();
        notify($bidingUser, 'HIRE_EMPLOY', [
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
        $notify[] = ["success", "Candidate has been employed"];
        return back()->withNotify($notify);
    }


    private function orderWithCheckout($id)
    {
        $general = GeneralSetting::first();
        $user = Auth::user();

        $jobBiding = JobBiding::findOrFail($id);
        $booking = new Booking();
        $booking->user_id = $user->id;
        $booking->job_biding_id = $jobBiding->id;
        $booking->qty = 1;
        $booking->amount = $jobBiding->price;
        $booking->order_number = getTrx();
        $booking->status = 0;
        $booking->updated_at = Carbon::now();
        $booking->status_updated_at = Carbon::now();
        $booking->save();

        session()->put('booking', $booking->order_number);
        return back();
    }
}
