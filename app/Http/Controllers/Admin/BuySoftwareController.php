<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Software;
use App\Models\Booking;
use Carbon\Carbon;

class BuySoftwareController extends Controller
{
    public function index()
    {
    	$pageTitle = "Sales Software list";
    	$emptyMessage = "No data found";
    	$bookings = Booking::where('status', '!=', '0')->whereNotNull('software_id')->with('user', 'software.user')->whereHas('software', function ($q) {
			$q->where('user_id', '!=',0);
		})->latest()->paginate(getPaginate());
    	return view('admin.sales.index', compact('pageTitle', 'emptyMessage', 'bookings'));
    }

	public function pending()
    {
    	$pageTitle = "Sales Software list";
    	$emptyMessage = "No data found";
    	$bookings = Booking::where('status', '!=', '0')->where('working_status','=',0)->whereNotNull('software_id')->with('user', 'software.user')->whereHas('software', function ($q) {
			$q->where('user_id', '!=',0);
		})->latest()->paginate(getPaginate());
    	return view('admin.sales.index', compact('pageTitle', 'emptyMessage', 'bookings'));
    }

	public function shipping()
    {
    	$pageTitle = "Sales Software list";
    	$emptyMessage = "No data found";
    	$bookings = Booking::where('status', '!=', '0')->where('working_status','=',2)->whereNotNull('software_id')->with('user', 'software.user')->whereHas('software', function ($q) {
			$q->where('user_id', '!=',0);
		})->latest()->paginate(getPaginate());
    	return view('admin.sales.index', compact('pageTitle', 'emptyMessage', 'bookings'));
    }

	

	public function completed()
    {
    	$pageTitle = "Sales Software list";
    	$emptyMessage = "No data found";
    	$bookings = Booking::where('status', '!=', '0')->where('working_status','=',1)->whereNotNull('software_id')->with('user', 'software.user')->whereHas('software', function ($q) {
			$q->where('user_id', '!=',0);
		})->latest()->paginate(getPaginate());
    	return view('admin.sales.index', compact('pageTitle', 'emptyMessage', 'bookings'));
    }

	public function rejected()
    {
    	$pageTitle = "Sales Software list";
    	$emptyMessage = "No data found";
    	$bookings = Booking::where('status', '!=', '0')->where('working_status','=',3)->whereNotNull('software_id')->with('user', 'software.user')->whereHas('software', function ($q) {
			$q->where('user_id', '!=',0);
		})->latest()->paginate(getPaginate());
    	return view('admin.sales.index', compact('pageTitle', 'emptyMessage', 'bookings'));
    }

	public function adminSales()
    {
    	$pageTitle = "Admin product sales";
    	$emptyMessage = "No data found";
		// $bookings = Booking::where('status', '!=', '0')->whereNotNull('software_id')->with('user', 'software.user')->latest()->paginate(getPaginate());
		/*$bookings = Booking::where('status', '!=', '0')->whereNotNull('software_id')->with('user','software.user')->whereHas('software', function ($q) {
			$q->where('user_id', 0);
		})->latest()->paginate(getPaginate());*/
		$bookings = Booking::where('status', '!=', '0')->whereNotNull('software_id')->with('user','software.user')->latest()->paginate(getPaginate());
		// dd($bookings);
    	return view('admin.sales.admin', compact('pageTitle', 'emptyMessage', 'bookings'));
    }

	public function updateStatus(Request $request){
	
		$id = $request->id;
		$status = $request->status;
		$booking = Booking::find($id);
		$booking->working_status = $status;
		$booking->updated_at = Carbon::now();
		$booking->save();
		// Booking::find($id)->update('working_status',$status);
		return 'success';;

	}

    public function softwareFileDownload($softwareId)
	{
	    $software = Software::where('id',decrypt($softwareId))->firstOrFail();
	    $file = $software->upload_software;
	    $path = imagePath()['uploadSoftware']['path'];
	    $full_path = $path.'/'. $file;
		if (file_exists($full_path)) {
			$ext = pathinfo($file, PATHINFO_EXTENSION);
			$mimetype = mime_content_type($full_path);
			//header('Content-Disposition: softwareFile; filename="' . $file . '.' . $ext . '";');
			header('Content-Disposition: softwareFile; filename="' . $file .'";');
			header("Content-Type: " . $mimetype);
			return readfile($full_path);
		}else{
			$notify[] = ['error', 'File does not exist.'];
            return back()->withNotify($notify);
		}
	}

	public function softwareDocumentFile($softwareId)
	{
	    $software = Software::where('id',decrypt($softwareId))->firstOrFail();
	    $file = $software->document_file;
	    $path = imagePath()['document']['path'];
	    $full_path = $path.'/'. $file;
		if (file_exists($full_path)) {
				$ext = pathinfo($file, PATHINFO_EXTENSION);
				$mimetype = mime_content_type($full_path);
				header('Content-Disposition: softwareFile; filename="' . $file . '";');
				header("Content-Type: " . $mimetype);
				return readfile($full_path);
		}else{
			$notify[] = ['error', 'File does not exist.'];
            return back()->withNotify($notify);
		}
	}


	public function search(Request $request)
    {
        $search = $request->search;
        $bookings = Booking::where('status', '!=', '0')->whereNotNull('software_id')
            ->whereHas('user', function($q) use ($search){
                $q->where('username', 'like', "%$search%");
            })
            ->orWhereHas('software.user', function($q) use ($search){
                $q->where('username', 'like', "%$search%");
            })->latest()->paginate(getPaginate());
        $pageTitle = 'Sales Software search by - ' . $search;
        $emptyMessage = 'No data found';
        return view('admin.sales.index', compact('pageTitle', 'emptyMessage', 'bookings', 'search'));
    }
}
