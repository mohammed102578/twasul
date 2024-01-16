<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;
use App\Models\Transaction;
use App\Models\GeneralSetting;
use App\Models\ExtraService;
use App\Models\WorkDelivery;

class ServiceBookingController extends Controller
{
    
    public function details($id)
    {
    	$pageTitle = "Service Booking Details";
    	$emptyMessage = "No data found";
    	$booking = Booking::where('status', '!=', '0')->where('id', $id)->whereNotNull('service_id')->firstOrFail();
    	$extraPrice = 0;
        if($booking->extra_service){  
            $extraArray = explode(",",$booking->extra_service);
            foreach($extraArray as $value){
                $extra = ExtraService::find($value);
                $extraPrice += $extra->price;
            }
        }
    	return view('admin.booking.details', compact('pageTitle', 'booking', 'extraPrice'));
    }
    public function index()
    {
    	$pageTitle = "Service Booking List";
    	$emptyMessage = "No data found";
    	$bookings = Booking::where('status', '!=', '0')->whereNotNull('service_id')->with('user', 'service.user')->latest()->paginate(getPaginate());
    	return view('admin.booking.index', compact('pageTitle', 'emptyMessage', 'bookings'));
    }

    public function pending()
    {
    	$pageTitle = "Service Bookings Pending List";
    	$emptyMessage = "No data found";
    	$bookings = Booking::where('status', '!=', '0')->where('working_status', 0)->whereNotNull('service_id')->with('user', 'service.user')->latest()->paginate(getPaginate());
    	return view('admin.booking.index', compact('pageTitle', 'emptyMessage', 'bookings'));
    }
    public function completed()
    {
    	$pageTitle = "Service Bookings Complete List";
    	$emptyMessage = "No data found";
    	$bookings = Booking::where('status', '!=', '0')->where('working_status', 1)->whereNotNull('service_id')->with('user', 'service.user')->latest()->paginate(getPaginate());
    	return view('admin.booking.index', compact('pageTitle', 'emptyMessage', 'bookings'));
    }
    public function delivered()
    {
    	$pageTitle = "Service Bookings Delivered List";
    	$emptyMessage = "No data found";
    	$bookings = Booking::where('status', '!=', '0')->where('working_status', 2)->whereNotNull('service_id')->with('user', 'service.user')->latest()->paginate(getPaginate());
    	return view('admin.booking.index', compact('pageTitle', 'emptyMessage', 'bookings'));
    }
    public function inProgress()
    {
    	$pageTitle = "Service Bookings In-progress List";
    	$emptyMessage = "No data found";
    	$bookings = Booking::where('status', '!=', '0')->where('working_status', 3)->whereNotNull('service_id')->with('user', 'service.user')->latest()->paginate(getPaginate());
    	return view('admin.booking.index', compact('pageTitle', 'emptyMessage', 'bookings'));
    }
    public function dispute()
    {
    	$pageTitle = "Service Bookings Dispute list";
    	$emptyMessage = "No data found";
    	$bookings = Booking::where('status', '!=', '0')->where('working_status', 6)->whereNotNull('service_id')->with('user', 'service.user')->latest()->paginate(getPaginate());
    	return view('admin.booking.index', compact('pageTitle', 'emptyMessage', 'bookings'));
    }
    public function expired()
    {
    	$pageTitle = "Service Bookings Expired List";
    	$emptyMessage = "No data found";
    	$bookings = Booking::where('status', '!=', '0')->where('working_status', 5)->whereNotNull('service_id')->with('user', 'service.user')->latest()->paginate(getPaginate());
    	return view('admin.booking.index', compact('pageTitle', 'emptyMessage', 'bookings'));
    }
    public function cacnel()
    {
    	$pageTitle = "Service Bookings Cancel List";
    	$emptyMessage = "No data found";
    	$bookings = Booking::where('status', '!=', '0')->where('working_status', 4)->whereNotNull('service_id')->with('user', 'service.user')->latest()->paginate(getPaginate());
    	return view('admin.booking.index', compact('pageTitle', 'emptyMessage', 'bookings'));
    }


    public function payment(Request $request)
    {

    	$request->validate([
            'payment' => 'required|in:seller,buyer'
        ]);
        $general = GeneralSetting::first();
        if($request->payment == "seller")
        {
	        $booking = Booking::where('id', $request->id)->whereNotNull('service_id')->where('status', '!=', 0)->firstOrFail();
	        $booking->status = 3;
	        $booking->working_status = 6;
            $booking->status_updated_at = Carbon::now();
	        $booking->save();

	        $charge = ((($booking->amount + $booking->discount) / 100) * $general->charge);
        	$payableAmount = (($booking->amount + $booking->discount) - $charge);

	        $seller = User::where('id', $booking->service->user_id)->firstOrFail();
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
	        $notify[] = ['success', 'Seller payment completed.'];
        	return back()->withNotify($notify);
        }
        elseif($request->payment == "buyer")
        {
        	$booking = Booking::where('id', $request->id)->whereNotNull('job_biding_id')->where('status', '!=', 0)->firstOrFail();
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
        $bookings = Booking::where('status', '!=', '0')->whereNotNull('service_id')->where(function($q) use($search){
            $q->whereHas('user', function($q) use ($search){
                $q->where('username', 'like', "%$search%");
            });
            $q->orWhereHas('service.user', function($q) use ($search){
                $q->where('username', 'like', "%$search%");
            });
        });
        $pageTitle = '';
        if($scope == 'pending'){
            $pageTitle .= 'Pending ';
            $bookings = $bookings->where('working_status', 0);
        }
        elseif($scope == 'completed'){
            $pageTitle .= 'Completed ';
            $bookings = $bookings->where('working_status', 1); 
        }
        elseif($scope == 'delivered'){
            $pageTitle .= 'Delivered ';
            $bookings = $bookings->where('working_status', 2);
        }
        elseif($scope == 'inProgress'){
            $pageTitle .= 'Inprogress ';
            $bookings = $bookings->where('working_status', 4);
        }
        elseif($scope == 'expired'){
            $pageTitle .= 'Expired ';
            $bookings = $bookings->where('working_status', 5);
        }
        elseif($scope == 'dispute'){
            $pageTitle .= 'Dispute ';
            $bookings = $bookings->where('working_status', 6);
        }
        elseif($scope == 'cacnel'){
            $pageTitle .= 'Cacnel ';
            $bookings = $bookings->where('working_status', 3);
        }
        $bookings = $bookings->latest()->paginate(getPaginate());
        $pageTitle .= 'Service Bookings search by - ' . $search;
        $emptyMessage = 'No data found';
        return view('admin.booking.index', compact('pageTitle', 'search', 'scope', 'emptyMessage', 'bookings'));
    }



    public function workDeliveryDownload($id)
    {
        $work = WorkDelivery::where('id',decrypt($id))->firstOrFail();
        $file = $work->work_file;
        $path = imagePath()['workFile']['path'];
        $full_path = $path.'/'. $file;
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $mimetype = mime_content_type($full_path);
        header('Content-Disposition: softwareFile; filename="' . $file . '.' . $ext . '";');
        header("Content-Type: " . $mimetype);
        return readfile($full_path);
    }
}
