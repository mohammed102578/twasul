<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\GeneralSetting;
use App\Models\Service;
use App\Models\User;
use App\Models\JobBiding;
use App\Models\Transaction;
use Carbon\Carbon;

class CronController extends Controller
{
    public function service()
    {
        $bookings = Booking::where('status', 1)->whereIn('working_status', [0,4])->whereNotNull('service_id')->get();
        $general = GeneralSetting::first();
        $general->last_cron_run = Carbon::now();
        $general->save();

        foreach ($bookings as $booking) {
            $service = Service::where('id', $booking->service_id)->first();
            $serviceUser = User::where('id', $service->user_id)->first();
            $deliveryTime = $booking->created_at->addDays($service->delivery_time);
            $nowTime = Carbon::now()->toDateTimeString();
            if($nowTime > $deliveryTime){
                $booking->status = 4;
                $booking->working_status = 5;
                $booking->save();
                $booking->status_updated_at = Carbon::now();

                $user = User::where('id', $booking->user_id)->firstOrFail();
                $user->balance += $booking->amount;
                $user->save();

                $transaction = new Transaction();
                $transaction->user_id = $user->id;
                $transaction->amount = $booking->amount;
                $transaction->post_balance = $user->balance;
                $transaction->trx_type = '+';
                $transaction->trx = $booking->order_number;
                $transaction->details = "Refund Money ". $booking->order_number;
                $transaction->save();

                notify($user, 'MONEY_REFUND', [
                    'amount' => getAmount($booking->amount),
                    'currency' => $general->cur_text,
                    'order_number' => $booking->order_number,
                    'post_balance' => getAmount($user->balance)
                ]);

                notify($serviceUser, 'SERVICE_BOOKING_EXPIRED', [
                    'order_number' => $booking->order_number,
                    'amount' => getAmount($booking->amount),
                    'currency' => $general->cur_text,
                ]);
            }
            return 0;
        }
    }

    public function job()
    {
        $bookings = Booking::where('status', 1)->where('working_status', 4)->whereNotNull('job_biding_id')->get();
        $general = GeneralSetting::first();
        $general->last_cron_run = Carbon::now();
        $general->save();
        foreach ($bookings as $booking) {
            $bidingUser = User::where('id', $booking->biding->user_id)->firstOrFail();
            $deliveryTime = $booking->created_at->addDays($booking->biding->job->delivery_time);
            $nowTime = Carbon::now()->toDateTimeString();
            if($nowTime > $deliveryTime){
                $booking->status = 4;
                $booking->working_status = 5;
                $booking->save();
                $booking->status_updated_at = Carbon::now();

                $user = User::where('id', $booking->user_id)->firstOrFail();
                $user->balance += $booking->amount;
                $user->save();

                $transaction = new Transaction();
                $transaction->user_id = $user->id;
                $transaction->amount = $booking->amount;
                $transaction->post_balance = $user->balance;
                $transaction->trx_type = '+';
                $transaction->trx = $booking->order_number;
                $transaction->details = "Refund Money ". $booking->order_number;
                $transaction->save();

                notify($user, 'MONEY_REFUND', [
                    'amount' => getAmount($booking->amount),
                    'currency' => $general->cur_text,
                    'order_number' => $booking->order_number,
                    'post_balance' => getAmount($user->balance)
                ]);
                notify($bidingUser, 'JOB_EXPIRED', [
                    'order_number' => $booking->order_number,
                    'amount' => getAmount($booking->amount),
                    'currency' => $general->cur_text,
                ]);
            }
            return 0;
        }
    }
}
