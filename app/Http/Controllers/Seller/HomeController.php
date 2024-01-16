<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;
use App\Rules\FileTypeValidate;
use App\Models\Transaction;
use App\Models\ProductInventory;
use App\Models\ExtraService;
use App\Models\WorkDelivery;
use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{

	public function __construct()
	{
		$this->activeTemplate = activeTemplate();
	}

	public function serviceBookeds()
	{
		$user = Auth::user();
		$pageTitle = "Service booking list";
		$emptyMessage = "No data found";
		$serviceBookings = Booking::whereHas('service', function ($q) use ($user) {
			$q->where('user_id', $user->id);
		})->where('status', '!=', 0)->with('user', 'service')->latest()->paginate(getPaginate());
		$salesSoftwares = Booking::whereHas('software', function ($q) use ($user) {
			$q->where('user_id', $user->id);
		})->where('status', '!=', '0')->with('user', 'software')->latest()->paginate(getPaginate());

		$jobVacancys = Booking::whereHas('biding', function ($q) use ($user) {
			$q->where('user_id', $user->id);
		})->where('status', '!=', '0')->with('user')->latest()->paginate(getPaginate());
		return view($this->activeTemplate . 'user.seller.service_booking', compact('pageTitle', 'emptyMessage', 'serviceBookings','salesSoftwares','jobVacancys'));
	}


	public function serviceBookingDetails($id)
	{
		$user = Auth::user();
		$pageTitle = "Service booking details";
		$booking = Booking::where('id', decrypt($id))->with('service')->firstOrFail();
		$extraPrice = 0;
		if ($booking->extra_service) {

			$extraArray = explode(",", $booking->extra_service);
			foreach ($extraArray as $value) {
				$extra = ExtraService::find($value);
				$extraPrice += $extra->price;
			}
		}
		return view($this->activeTemplate . 'user.seller.service_booking_details', compact('pageTitle', 'booking', 'extraPrice'));
	}

	public function salesSoftware()
	{
		$user = Auth::user();
		$pageTitle = "Software sales list";
		$emptyMessage = "No data found";
		$salesSoftwares = Booking::whereHas('software', function ($q) use ($user) {
			$q->where('user_id', $user->id);
		})->where('status', '!=', '0')->with('user', 'software')->latest()->paginate(getPaginate());

		return view($this->activeTemplate . 'user.seller.sales_software', compact('pageTitle', 'emptyMessage', 'salesSoftwares'));
	}


	public function jobVacancy()
	{
		$user = Auth::user();
		$pageTitle = "Job list";
		$emptyMessage = "No data found";
		$jobVacancys = Booking::whereHas('biding', function ($q) use ($user) {
			$q->where('user_id', $user->id);
		})->where('status', '!=', '0')->with('user')->latest()->paginate(getPaginate());
		return view($this->activeTemplate . 'user.seller.job_vacancy', compact('pageTitle', 'emptyMessage', 'jobVacancys'));
	}


	public function jobListDetails($id)
	{
		$user = Auth::user();
		$pageTitle = "Job list details";
		$jobListDetails = Booking::whereHas('biding', function ($q) use ($user) {
			$q->where('user_id', $user->id);
		})->where('status', '!=', '0')->where('id', decrypt($id))->firstOrFail();
		return view($this->activeTemplate . 'user.seller.job_details', compact('pageTitle', 'jobListDetails'));
	}


	public function bookingConfirm(Request $request)
	{
		$request->validate([
			'order_number' => 'required|exists:bookings,order_number',
			'confirm' => 'required|in:approved,cancel'
		]);
		$user = Auth::user();
		$general = GeneralSetting::first();
		$booking = Booking::where('order_number', $request->order_number)->whereNotNull('service_id')->whereHas('service', function ($q) use ($user) {
			$q->where('user_id', $user->id);
		})->first();
		if (!$booking) {
			$notify[] = ['error', 'Invalid service booking'];
			return back()->withNotify($notify);
		}
		if ($request->confirm == "approved") {
			$booking->working_status = 4;
			$booking->updated_at = Carbon::now();
			$booking->save();
			$notify[] = ['success', 'Thanks For Receiving the booking'];
			return back()->withNotify($notify);
		} elseif ($request->confirm == "cancel") {
			$booking->status = 4;
			$booking->working_status = 3;
			$booking->save();

			$user = User::where('id', $booking->user_id)->firstOrFail();
			$user->balance += $booking->amount;
			$user->save();

			$transaction = new Transaction();
			$transaction->user_id = $user->id;
			$transaction->amount = $booking->amount;
			$transaction->post_balance = $user->balance;
			$transaction->trx_type = '+';
			$transaction->trx = $booking->order_number;
			$transaction->details = "Refund Money " . $booking->order_number;
			$transaction->save();

			notify($user, 'MONEY_REFUND', [
				'amount' => getAmount($booking->amount),
				'currency' => $general->cur_text,
				'order_number' => $booking->order_number,
				'post_balance' => getAmount($user->balance)
			]);
			$notify[] = ['success', 'Refund money.'];
			return back()->withNotify($notify);
		} else {
			$notify[] = ['error', 'Invalid booking'];
			return back()->withNotify($notify);
		}
	}


	public function workUpload(Request $request)
	{
		$request->validate([
			'order_number' => 'required|exists:bookings,order_number',
			'file' => ['required', new FileTypeValidate(['zip'])],
			'details' => 'required|max:1000',
			'work_type' => 'required|in:jobBiding,service',
		]);
		$user = Auth::user();
		if ($request->work_type == "service") {
			$booking = Booking::where('order_number', $request->order_number)->whereNotNull('service_id')->whereHas('service', function ($q) use ($user) {
				$q->where('user_id', $user->id);
			})->first();
			if (!$booking) {
				$notify[] = ['error', 'Invalid service booking'];
				return back()->withNotify($notify);
			}
		} elseif ($request->work_type == "jobBiding") {
			$booking = Booking::where('order_number', $request->order_number)->whereNotNull('job_biding_id')->whereHas('biding', function ($q) use ($user) {
				$q->where('user_id', $user->id);
			})->first();

			if (!$booking) {
				$notify[] = ['error', 'Invalid Job order'];
				return back()->withNotify($notify);
			}
		}
		$booking->working_status = 2;
		$booking->updated_at = Carbon::now();
		$booking->save();

		$work = new WorkDelivery();
		$work->booking_id = $booking->id;
		$work->sender_id = auth()->user()->id;
		$work->receiver_id = $booking->user_id;
		$workFile = imagePath()['workFile']['path'];
		if ($request->hasFile('file')) {
			$file = $request->file;
			try {
				$filename = uploadFile($file, $workFile);
			} catch (\Exception $exp) {
				$notify[] = ['error', 'Zip could not be uploaded.'];
				return back()->withNotify($notify);
			}
			$work->work_file = $filename;
		}
		$work->details = $request->details;
		$work->save();
		$notify[] = ['success', 'Work has been submitted'];
		return back()->withNotify($notify);
	}

	public function workFileDownload($id)
	{
		$work = WorkDelivery::where('id', decrypt($id))->Where('sender_id', auth()->user()->id)->firstOrFail();
		$file = $work->work_file;
		$path = imagePath()['workFile']['path'];
		$full_path = $path . '/' . $file;
		if (file_exists($full_path)) {
			$ext = pathinfo($file, PATHINFO_EXTENSION);
			$mimetype = mime_content_type($full_path);
			header('Content-Disposition: softwareFile; filename="' . $file . '";');
			header("Content-Type: " . $mimetype);
			return readfile($full_path);
		} else {
			$notify[] = ['error', 'File does not exist.'];
			return back()->withNotify($notify);
		}
	}


	public function follow($id)
	{
		$follow = User::where('id', $id)->firstOrFail();
		$user = Auth()->user();
		if ($follow->id != $user->id) {
			if ($user->following->find($id) == null) {
				$user->following()->attach($id);
				$notify[] = ['success', "Following $follow->username"];
			} else {
				$user->following()->detach($id);
				$notify[] = ['success', "Unfollow $follow->username"];
			}
			return back()->withNotify($notify);
		}
		$notify[] = ['error', "it's You!"];
		return back()->withNotify($notify);
	}


	public function transactions()
	{
		$user = Auth::user();
		$pageTitle = "Transaction Log";
		$emptyMessage = "No data found";
		$transactions = Transaction::where('user_id', $user->id)->paginate(getPaginate());
		return view($this->activeTemplate . 'user.buyer.transactions', compact('pageTitle', 'emptyMessage', 'transactions'));
	}

	public function productBookingDetails($id)
	{
		$user = Auth::user();
		$pageTitle = "Product booking details";
		$booking = Booking::where('id', decrypt($id))->with('software')->firstOrFail();
		$productVerity = [];
		if ($booking->product_verity_id) {
			$productVerity = ProductInventory::find($booking->product_verity_id);
		}
		return view($this->activeTemplate . 'user.seller.product_booking_details', compact('pageTitle', 'booking', 'productVerity'));
	}

	public function productBookingConfirm(Request $request)
	{
		$request->validate([
			'booking_id' => 'required|exists:bookings,id',
			'confirm' => 'required|in:approved,cancel'
		]);
		$user = Auth::user();
		$general = GeneralSetting::first();
		$booking = Booking::where('id', $request->booking_id)->with('software')->firstOrFail();
		if (!$booking) {
			$notify[] = ['error', 'Invalid product booking'];
			return back()->withNotify($notify);
		}
		if ($request->confirm == "approved") {
			$booking->working_status = 1;
			$booking->updated_at = Carbon::now();
			$booking->save();
			$notify[] = ['success', 'Product booking has approved'];
			return back()->withNotify($notify);
		} elseif ($request->confirm == "cancel") {
			$booking->status = 4;
			$booking->working_status = 3;
			$booking->save();

			$user = User::where('id', $booking->user_id)->firstOrFail();
			$user->balance += $booking->amount;
			$user->save();

			$transaction = new Transaction();
			$transaction->user_id = $user->id;
			$transaction->amount = $booking->amount;
			$transaction->post_balance = $user->balance;
			$transaction->trx_type = '+';
			$transaction->trx = $booking->order_number;
			$transaction->details = "Refund Money " . $booking->order_number;
			$transaction->save();
			$softwareUser = User::where('id', $booking->software->user_id)->first();
			$charge = (($booking->amount / 100) * $general->charge);
			$reducableAmountUser = ($booking->amount - $charge);

			$softwareUser->balance -= $reducableAmountUser;
			$softwareUser->income -= $reducableAmountUser;
			$softwareUser->save();
			rankUser($softwareUser->id);

			$transaction = new Transaction();
			$transaction->user_id = $softwareUser->id;
			$transaction->amount = $reducableAmountUser;
			$transaction->post_balance = $softwareUser->balance;
			$transaction->charge = $charge;
			$transaction->trx_type = '-';
			$transaction->trx = $booking->order_number;
			$transaction->details = "Payment Deducted for " . $booking->order_number;
			$transaction->save();

			notify($user, 'MONEY_REFUND', [
				'amount' => getAmount($booking->amount),
				'currency' => $general->cur_text,
				'order_number' => $booking->order_number,
				'post_balance' => getAmount($user->balance)
			]);
			$notify[] = ['success', 'Refund money.'];
			return back()->withNotify($notify);
		} else {
			$notify[] = ['error', 'Invalid booking'];
			return back()->withNotify($notify);
		}
	}
}
