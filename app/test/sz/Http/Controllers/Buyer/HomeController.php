<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\FavoriteItem;
use App\Models\Booking;
use App\Models\User;
use App\Models\Job;
use Carbon\Carbon;
use App\Models\ExtraService;
use App\Models\Software;
use App\Models\WorkDelivery;
use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{

    public function __construct()
    {
        $this->activeTemplate = activeTemplate();
    }

    public function index()
    {
        $user = Auth::user();
        $pageTitle = "Buyer Dashboard";
        $emptyMessage = "No data found";
        $transactions = Transaction::where('user_id', $user->id)->orderBy('id', 'DESC')->limit(5)->get();
        $totaltransactions = Transaction::where('user_id', $user->id)->count();
        $totalJob = Job::where('user_id', $user->id)->count();
        $serviceBookings = Booking::where('user_id', $user->id)->where('status', '!=', 0)->whereNotNull('service_id')->count();
        $softwarePurchases = Booking::where('user_id', $user->id)->where('status', '!=', 0)->whereNotNull('software_id')->count();
        $hireEmploys = Booking::where('user_id', $user->id)->where('status', '!=', 0)->whereNotNull('job_biding_id')->count();
        return view($this->activeTemplate . 'user.buyer.dashboard', compact('pageTitle', 'emptyMessage', 'transactions', 'totaltransactions', 'totalJob', 'serviceBookings', 'softwarePurchases', 'hireEmploys'));
    }

    public function serviceBookingItem()
    {
        $user = Auth::user();
        $pageTitle = "Service booking list";
        $emptyMessage = "No data found";
        $serviceBookings = Booking::where('user_id', $user->id)->where('status', '!=', 0)->whereNotNull('service_id')->latest()->paginate(getPaginate());
        return view($this->activeTemplate . 'user.buyer.service_booking', compact('pageTitle', 'emptyMessage', 'serviceBookings'));
    }

    public function serviceBookingDetails($id)
    {
        $user = Auth::user();
        $pageTitle = "Service booking details";
        $emptyMessage = "No data found";
        $booking = Booking::where('user_id', $user->id)->where('id', decrypt($id))->whereNotNull('service_id')->firstOrFail();
        $extraPrice = 0;
        if ($booking->extra_service) {
            $extraArray = explode(",", $booking->extra_service);
            foreach ($extraArray as $value) {
                $extra = ExtraService::find($value);
                $extraPrice += $extra->price;
            }
        }
        return view($this->activeTemplate . 'user.buyer.service_booking_details', compact('pageTitle', 'emptyMessage', 'booking', 'extraPrice'));
    }


    public function softwarePurchases()
    {
        $user = Auth::user();
        $pageTitle = "Service purchases list";
        $emptyMessage = "No data found";
        $softwarePurchases = Booking::where('user_id', $user->id)->where('status', '!=', 0)->whereNotNull('software_id')->latest()->paginate(getPaginate());
        return view($this->activeTemplate . 'user.buyer.software_purchases', compact('pageTitle', 'emptyMessage', 'softwarePurchases'));
    }


    public function buyerSoftwareFileDownload($id)
    {
        $softwarePurchases = Booking::where('user_id', auth()->user()->id)->where('status', '!=', 0)->whereNotNull('software_id')->where('id', decrypt($id))->firstOrFail();
        $software = Software::where('id', $softwarePurchases->id)->firstOrFail();
        $file = $software->upload_software;
        $path = imagePath()['uploadSoftware']['path'];
        $full_path = $path . '/' . $file;
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $mimetype = mime_content_type($full_path);
        header('Content-Disposition: softwareFile; filename="' . $file . '.' . $ext . '";');
        header("Content-Type: " . $mimetype);
        return readfile($full_path);
    }

    public function buyerSoftwareDocumentFile($id)
    {
        $softwarePurchases = Booking::where('user_id', auth()->user()->id)->where('status', '!=', 0)->whereNotNull('software_id')->where('id', decrypt($id))->firstOrFail();
        $software = Software::where('id', $softwarePurchases->id)->firstOrFail();
        $file = $software->document_file;
        $path = imagePath()['document']['path'];
        $full_path = $path . '/' . $file;
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $mimetype = mime_content_type($full_path);
        header('Content-Disposition: softwareFile; filename="' . $file . '.' . $ext . '";');
        header("Content-Type: " . $mimetype);
        return readfile($full_path);
    }

    public function hireEmploy()
    {
        $user = Auth::user();
        $pageTitle = "Hire employ list";
        $emptyMessage = "No data found";
        $hireEmploys = Booking::where('user_id', $user->id)->where('status', '!=', 0)->whereNotNull('job_biding_id')->latest()->paginate(getPaginate());
        return view($this->activeTemplate . 'user.buyer.hire_employ', compact('pageTitle', 'emptyMessage', 'hireEmploys'));
    }

    public function hireEmployDetails($id)
    {
        $user = Auth::user();
        $pageTitle = "Hire employe details";
        $emptyMessage = "No data found";
        $booking = Booking::where('id', decrypt($id))->where('user_id', $user->id)->where('status', '!=', 0)->whereNotNull('job_biding_id')->first();
        return view($this->activeTemplate . 'user.buyer.hire_employ_details', compact('pageTitle', 'emptyMessage', 'booking'));
    }


    public function serviceFavoriteItem()
    {
        $user = Auth::user();
        $pageTitle = "Service favorite list";
        $emptyMessage = "No data found";
        $favoriteServices = FavoriteItem::whereNotNull('service_id')->where('user_id', $user->id)->with('service')->paginate(getPaginate());
        return view($this->activeTemplate . 'user.buyer.favorite_service', compact('pageTitle', 'emptyMessage', 'favoriteServices'));
    }

    public function softwareFavoriteItem()
    {
        $user = Auth::user();
        $pageTitle = "Software favorite list";
        $emptyMessage = "No data found";
        $favoriteSoftwares = FavoriteItem::whereNotNull('software_id')->where('user_id', $user->id)->with('software')->paginate(getPaginate());
        return view($this->activeTemplate . 'user.buyer.favorite_software', compact('pageTitle', 'emptyMessage', 'favoriteSoftwares'));
    }

    /*
     * Deposit History
     */
    public function depositHistory()
    {
        $pageTitle = 'Deposit History';
        $emptyMessage = 'No history found.';
        $logs = auth()->user()->deposits()->with(['gateway'])->orderBy('id', 'desc')->paginate(getPaginate());
        return view($this->activeTemplate . 'user.deposit_history', compact('pageTitle', 'emptyMessage', 'logs'));
    }



    public function transactions()
    {
        $user = Auth::user();
        $pageTitle = "Transaction Log";
        $emptyMessage = "No data found";
        $transactions = Transaction::where('user_id', $user->id)->paginate(getPaginate());
        return view($this->activeTemplate . 'user.buyer.transactions', compact('pageTitle', 'emptyMessage', 'transactions'));
    }


    public function workDeliveryApproved(Request $request)
    {
        $request->validate([
            'order_number' => 'required|exists:bookings,order_number',
            'work_type' => 'required|in:jobBiding,service',
        ]);
        $general = GeneralSetting::first();
        $user = Auth::user();
        if ($request->work_type == "service") {
            $booking = Booking::where('order_number', $request->order_number)->whereNotNull('service_id')->where('user_id', $user->id)->first();
            if (!$booking) {
                $notify[] = ['error', 'Invalid service booking'];
                return back()->withNotify($notify);
            }
        } elseif ($request->work_type == "jobBiding") {
            $booking = Booking::where('order_number', $request->order_number)->whereNotNull('job_biding_id')->where('user_id', $user->id)->first();
            if (!$booking) {
                $notify[] = ['error', 'Invalid Job order'];
                return back()->withNotify($notify);
            }
        }
        $booking->status = 3;
        $booking->working_status = 1;
        $booking->updated_at = Carbon::now();
        $booking->status_updated_at = Carbon::now();
        $booking->save();

        $charge = ((($booking->amount + $booking->discount) / 100) * $general->charge);
        $payableAmount = (($booking->amount + $booking->discount) - $charge);
        if ($booking->service_id) {
            $seller = User::where('id', $booking->service->user_id)->firstOrFail();
            $seller->balance += $payableAmount;
            $seller->income += $payableAmount;
            $seller->save();
            rankUser($seller->id);
        } elseif ($booking->job_biding_id) {
            $seller = User::where('id', $booking->biding->user_id)->firstOrFail();
            $seller->balance += $payableAmount;
            $seller->income += $payableAmount;
            $seller->save();
            rankUser($seller->id);
        }

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

        $notify[] = ['success', 'Work has been approved.'];
        return back()->withNotify($notify);
    }


    public function workDispute(Request $request)
    {
        $request->validate([
            'order_number' => 'required|exists:bookings,order_number',
            'work_type' => 'required|in:jobBiding,service',
            'dispute' => 'required|min:50|max:1000'
        ]);
        $general = GeneralSetting::first();
        $user = Auth::user();
        if ($request->work_type == "service") {
            $booking = Booking::where('order_number', $request->order_number)->whereNotNull('service_id')->where('user_id', $user->id)->first();
            if (!$booking) {
                $notify[] = ['error', 'Invalid service booking'];
                return back()->withNotify($notify);
            }
        } elseif ($request->work_type == "jobBiding") {
            $booking = Booking::where('order_number', $request->order_number)->whereNotNull('job_biding_id')->where('user_id', $user->id)->first();
            if (!$booking) {
                $notify[] = ['error', 'Invalid job order'];
                return back()->withNotify($notify);
            }
        }
        $booking->working_status = 6;
        $booking->status = 2;
        $booking->dispute_report = $request->dispute;
        $booking->updated_at = Carbon::now();
        $booking->status_updated_at = Carbon::now();
        $booking->save();
        $notify[] = ['success', 'Work has been in dispute'];
        return back()->withNotify($notify);
    }


    public function workDeliveryDownload($id)
    {
        $work = WorkDelivery::where('id', decrypt($id))->where('receiver_id', auth()->user()->id)->firstOrFail();
        $file = $work->work_file;
        $path = imagePath()['workFile']['path'];
        $full_path = $path . '/' . $file;
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $mimetype = mime_content_type($full_path);
        header('Content-Disposition: softwareFile; filename="' . $file . '.' . $ext . '";');
        header("Content-Type: " . $mimetype);
        return readfile($full_path);
    }
}
