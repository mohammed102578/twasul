<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Software;
use App\Models\Booking;

class BuySoftwareController extends Controller
{
    public function index()
    {
    	$pageTitle = "Sales Software list";
    	$emptyMessage = "No data found";
    	$bookings = Booking::where('status', '!=', '0')->whereNotNull('software_id')->with('user', 'software.user')->latest()->paginate(getPaginate());
    	return view('admin.sales.index', compact('pageTitle', 'emptyMessage', 'bookings'));
    }

    public function softwareFileDownload($softwareId)
	{
	    $software = Software::where('id',decrypt($softwareId))->firstOrFail();
	    $file = $software->upload_software;
	    $path = imagePath()['uploadSoftware']['path'];
	    $full_path = $path.'/'. $file;
	    $ext = pathinfo($file, PATHINFO_EXTENSION);
	    $mimetype = mime_content_type($full_path);
	    header('Content-Disposition: softwareFile; filename="' . $file . '.' . $ext . '";');
	    header("Content-Type: " . $mimetype);
	    return readfile($full_path);
	}

	public function softwareDocumentFile($softwareId)
	{
	    $software = Software::where('id',decrypt($softwareId))->firstOrFail();
	    $file = $software->document_file;
	    $path = imagePath()['document']['path'];
	    $full_path = $path.'/'. $file;
	    $ext = pathinfo($file, PATHINFO_EXTENSION);
	    $mimetype = mime_content_type($full_path);
	    header('Content-Disposition: softwareFile; filename="' . $file . '.' . $ext . '";');
	    header("Content-Type: " . $mimetype);
	    return readfile($full_path);
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
