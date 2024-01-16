<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\WorkDelivery;
use App\Models\GeneralSetting;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;

class HireEmployController extends Controller
{


    public function details($id)
    {
        $pageTitle = "Hire Employees Details";
        $emptyMessage = "No data found";
        $booking = Booking::where('id', $id)->where('status', '!=', '0')->whereNotNull('job_biding_id')->firstOrFail();
        return view('admin.hire.details', compact('pageTitle', 'emptyMessage', 'booking'));
    }
    
    public function index()
    {
    	$pageTitle = "Hire Employees list";
    	$emptyMessage = "No data found";
    	$bookings = Booking::where('status', '!=', '0')->whereNotNull('job_biding_id')->with('user', 'biding.user')->latest()->paginate(getPaginate());
    	return view('admin.hire.index', compact('pageTitle', 'emptyMessage', 'bookings'));
    }

    public function completed()
    {
    	$pageTitle = "Work completed  list";
    	$emptyMessage = "No data found";
    	$bookings = Booking::where('status', '!=', '0')->where('working_status', 1)->whereNotNull('job_biding_id')->with('user', 'biding.user')->latest()->paginate(getPaginate());
    	return view('admin.hire.index', compact('pageTitle', 'emptyMessage', 'bookings'));
    }
    public function delivered()
    {
    	$pageTitle = "Work delivered list";
    	$emptyMessage = "No data found";
    	$bookings = Booking::where('status', '!=', '0')->where('working_status', 2)->whereNotNull('job_biding_id')->with('user', 'biding.user')->latest()->paginate(getPaginate());
    	return view('admin.hire.index', compact('pageTitle', 'emptyMessage', 'bookings'));
    }
    public function inprogress()
    {
    	$pageTitle = "Work inprogress list";
    	$emptyMessage = "No data found";
    	$bookings = Booking::where('status', '!=', '0')->where('working_status', 4)->whereNotNull('job_biding_id')->with('user', 'biding.user')->latest()->paginate(getPaginate());
    	return view('admin.hire.index', compact('pageTitle', 'emptyMessage', 'bookings'));
    }
    public function expired()
    {
    	$pageTitle = "Work expired list";
    	$emptyMessage = "No data found";
    	$bookings = Booking::where('status', '!=', '0')->where('working_status', 5)->whereNotNull('job_biding_id')->with('user', 'biding.user')->latest()->paginate(getPaginate());
    	return view('admin.hire.index', compact('pageTitle', 'emptyMessage', 'bookings'));
    }

    public function dispute()
    {
        $pageTitle = "Work dispute list";
        $emptyMessage = "No data found";
        $bookings = Booking::where('status', '!=', '0')->where('working_status', 6)->whereNotNull('job_biding_id')->with('user', 'biding.user')->latest()->paginate(getPaginate());
        return view('admin.hire.index', compact('pageTitle', 'emptyMessage', 'bookings'));
    }

    public function workFileDownload($id)
    {
        $work = WorkDelivery::where('id',$id)->firstOrFail();
        $file = $work->work_file;
        $path = imagePath()['workFile']['path'];
        $full_path = $path.'/'. $file;
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $mimetype = mime_content_type($full_path);
        header('Content-Disposition: softwareFile; filename="' . $file . '.' . $ext . '";');
        header("Content-Type: " . $mimetype);
        return readfile($full_path);
    }



    public function payment(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:bookings,id',
            'payment' => 'required|in:seller,buyer'
        ]);
        $general = GeneralSetting::first();
        if($request->payment == "seller")
        {
            $booking = Booking::where('id', $request->id)->firstOrFail();
            $booking->status = 3;
            $booking->working_status = 6;
            $booking->status_updated_at = Carbon::now();
            $booking->save();

            $charge = (($booking->amount / 100) * $general->charge);
            $payableAmount = ($booking->amount - $charge);

            $seller = User::where('id', $booking->biding->user_id)->firstOrFail();

            $seller->balance += $payableAmount;
            $seller->income += $payableAmount;
            $seller->save();
            rankUser($seller->id);

            $transaction = new Transaction();
            $transaction->user_id = $seller->id;
            $transaction->amount = $payableAmount;
            $transaction->charge = $charge;
            $transaction->post_balance = $seller->balance;
            $transaction->trx_type = '+';
            $transaction->trx = $booking->order_number;
            $transaction->details = "Payment for " . $booking->order_number;
            $transaction->save();

            notify($seller, 'PAYMENT_SELLER', [
                'amount' => getAmount($payableAmount),
                'currency' => $general->cur_text,
                'order_number' => $booking->order_number,
                'post_balance' => getAmount($seller->balance)
            ]);
            $notify[] = ['success', 'Seller payment complete.'];
            return back()->withNotify($notify);
        }
        elseif($request->payment == "buyer")
        {
            $booking = Booking::where('id', $request->id)->firstOrFail();
            $booking->status = 4;
            $booking->working_status = 6;
            $booking->status_updated_at = Carbon::now();
            $booking->save();

            $user =User::where('id', $booking->user_id)->firstOrFail();
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
            $notify[] = ['success', 'Refund money.'];
            return back()->withNotify($notify);
        }
    }



    public function search(Request $request, $scope)
    {
        $search = $request->search;
        $bookings = Booking::where('status', '!=', '0')->whereNotNull('job_biding_id')
            ->whereHas('user', function($q) use ($search){
                $q->where('username', 'like', "%$search%");
            })
            ->orWhereHas('biding.user', function($q) use ($search){
                $q->where('username', 'like', "%$search%");
            });

        $pageTitle = '';
        switch ($scope) {
            case 'completed':
                $pageTitle .= 'Completed ';
                $bookings = $bookings->where('working_status', 1);
                break;
            case 'delivered':
                $pageTitle .= 'Delivered ';
                $bookings = $bookings->where('working_status', 2);
                break;
            case 'inprogress':
                $pageTitle .= 'Inprogress ';
                $bookings = $bookings->where('working_status', 4);
                break;
            case 'expired':
                $pageTitle .= 'Expired ';
                $bookings = $bookings->where('working_status', 5);
                break;
            case 'dispute':
                $pageTitle .= 'Dispute ';
                $bookings = $bookings->where('working_status', 6);
                break;
        }
        $bookings = $bookings->latest()->paginate(getPaginate());
        $pageTitle .= 'Hire Employs search by - ' . $search;
        $emptyMessage = 'No data found';
        return view('admin.hire.index', compact('pageTitle', 'search', 'scope', 'emptyMessage', 'bookings'));
    }
}
