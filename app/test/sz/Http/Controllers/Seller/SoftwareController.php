<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Software;
use App\Models\Features;
use App\Models\OptionalImage;
use App\Rules\FileTypeValidate;
use App\Models\GeneralSetting;
use App\Models\AdminNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class SoftwareController extends Controller
{
	public function __construct()
    {
        $this->activeTemplate = activeTemplate();
    }

    public function index()
    {
    	$user = Auth::user();
    	$pageTitle = "Manage Software";
    	$emptyMessage = "No data found";
    	$softwares = Software::where('user_id', $user->id)->latest()->paginate(getPaginate());
    	return view($this->activeTemplate . 'user.seller.software.index', compact('pageTitle', 'softwares', 'emptyMessage'));
    }

	public function create()
	{
		$pageTitle = "Upload Software";
		$features = Features::latest()->get();
		return view($this->activeTemplate . 'user.seller.software.create', compact('pageTitle', 'features'));
	}


	public function store(Request $request)
	{
		$request->validate([
            'image' => ['required', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
            'screenshot.*' => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
            'title' => 'required|string|max:255',
            'category' => 'required|exists:categories,id',
            'subcategory' => 'nullable|exists:sub_categories,id',
            'features' => 'required|array|exists:features,id',
            'tag' => 'required|array|min:3|max:15',
            'file_include' => 'required|array|min:3|max:15',
            'amount' => 'required|numeric|gt:0',
            'url' => 'required|url',
            'description' => 'required',
            'document' => ['required', new FileTypeValidate(['pdf'])],
            'uploadSoftware' => ['required', new FileTypeValidate(['zip'])],
        ]);
        $user = Auth::user();
    	$general = GeneralSetting::first();
        $software = new Software();
        $software->user_id = $user->id;
        $software->category_id = $request->category;
        $software->sub_category_id = $request->subcategory ? $request->subcategory : null;
        $software->title = $request->title;
        $software->amount = $request->amount;
        $software->demo_url = $request->url;
        $software->tag = $request->tag;
        $software->file_include = $request->file_include;
        $software->description = $request->description;

        $path = imagePath()['software']['path'];
        $size = imagePath()['software']['size'];
        if ($request->hasFile('image')) {
            $file = $request->image;
            try {
                $filename = uploadImage($file, $path, $size);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Image could not be uploaded.'];
                return back()->withNotify($notify);
            }
            $software->image = $filename;
        }

        $documentPath = imagePath()['document']['path'];
        if($request->hasFile('document')) {
           	$file = $request->document; 
           	try {
           		$filename = uploadFile($file, $documentPath);
           	}catch (\Exception $exp) {
                $notify[] = ['error', 'Pdf could not be uploaded.'];
                return back()->withNotify($notify);
            }
            $software->document_file = $filename;
        }
        $softwarePath = imagePath()['uploadSoftware']['path'];
        if($request->hasFile('uploadSoftware')) {
           	$file = $request->uploadSoftware; 
           	try {
           		$filename = uploadFile($file, $softwarePath);
           	}catch (\Exception $exp) {
                $notify[] = ['error', 'Zip could not be uploaded.'];
                return back()->withNotify($notify);
            }
            $software->upload_software = $filename;
        }

        if($general->approval_post == 1){
        	$software->status = 1;
        }
        $software->updated_at = Carbon::now();
        $software->save();
        $software->featuresSoftware()->attach($request->features);
        if($request->screenshot){
        	$screenshot = array_filter($request->screenshot);
        	$this->screenshotImageStore($request, $screenshot, $software->id);
        }

        $adminNotification = new AdminNotification();
        $adminNotification->user_id = $user->id;
        $adminNotification->title = 'Upload Software'.$software->title;
        $adminNotification->click_url = urlPath('admin.software.details',$software->id);
        $adminNotification->save();

        $notify[] = ['success', 'software has been uploaded.'];
        return back()->withNotify($notify);
	}

	public function edit($slug, $id){
		$user = Auth::user();
		$pageTitle = "Software Update";
		$software = Software::where('id', $id)->where('user_id', $user->id)->firstOrFail();
		$features = Features::latest()->get();
		return view($this->activeTemplate . 'user.seller.software.edit', compact('pageTitle', 'features', 'software'));
	}


    public function update(Request $request, $id)
    {
        $request->validate([
            'image' => ['required', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
            'screenshot.*' => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
            'title' => 'required|string|max:255',
            'category' => 'required|exists:categories,id',
            'subcategory' => 'nullable|exists:sub_categories,id',
            'features' => 'required|array|exists:features,id',
            'tag' => 'required|array|min:3|max:15',
            'file_include' => 'required|array|min:3|max:15',
            'amount' => 'required|numeric|gt:0',
            'url' => 'required|url',
            'description' => 'required',
            'document' => ['nullable', new FileTypeValidate(['pdf'])],
            'uploadSoftware' => ['nullable', new FileTypeValidate(['zip'])],
        ]);
        $user = Auth::user();
        $general = GeneralSetting::first();
        $software =Software::where('user_id', $user->id)->where('id', $id)->firstOrFail();
        $software->user_id = $user->id;
        $software->category_id = $request->category;
        $software->sub_category_id = $request->subcategory ? $request->subcategory : null;
        $software->title = $request->title;
        $software->amount = $request->amount;
        $software->demo_url = $request->url;
        $software->description = $request->description;
        $software->tag = $request->tag;
        $software->file_include = $request->file_include;
        $path = imagePath()['software']['path'];
        $size = imagePath()['software']['size'];
        if ($request->hasFile('image')) {
            $file = $request->image;
            try {
                $filename = uploadImage($file, $path, $size, $software->image);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Image could not be uploaded.'];
                return back()->withNotify($notify);
            }
            $software->image = $filename;
        }
        $documentPath = imagePath()['document']['path'];
        if($request->hasFile('document')) {
            $file = $request->document; 
            try {
                $filename = uploadFile($file, $documentPath, $size=null, $software->document_file);
            }catch (\Exception $exp) {
                $notify[] = ['error', 'Pdf could not be uploaded.'];
                return back()->withNotify($notify);
            }
            $software->document_file = $filename;
        }
        $softwarePath = imagePath()['uploadSoftware']['path'];
        if($request->hasFile('uploadSoftware')) {
            $file = $request->uploadSoftware; 
            try {
                $filename = uploadFile($file, $softwarePath, $size=null, $software->upload_software);
            }catch (\Exception $exp) {
                $notify[] = ['error', 'Zip could not be uploaded.'];
                return back()->withNotify($notify);
            }
            $software->upload_software = $filename;
        }
        if($general->approval_post == 1){
            $software->status = 1;
        }else{
            $software->status = 0;
            $software->created_at = Carbon::now();
        }
        $software->updated_at = Carbon::now();
        $software->save();
        $software->featuresSoftware()->sync($request->features);
        if($request->screenshot){
            $screenshot = array_filter($request->screenshot);
            $this->screenshotImageStore($request, $screenshot, $software->id);
        }

        $adminNotification = new AdminNotification();
        $adminNotification->user_id = $user->id;
        $adminNotification->title = 'Update Software'.$software->title;
        $adminNotification->click_url = urlPath('admin.software.details',$software->id);
        $adminNotification->save();

        $notify[] = ['success', 'software has been uploaded.'];
        return back()->withNotify($notify);
    }

	public function softwareFileDownload($softwareId)
	{
		$user = Auth::user();
	    $software = Software::where('id',decrypt($softwareId))->where('user_id', $user->id)->firstOrFail();
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
		$user = Auth::user();
	    $software = Software::where('id',decrypt($softwareId))->where('user_id', $user->id)->firstOrFail();
	    $file = $software->document_file;
	    $path = imagePath()['document']['path'];
	    $full_path = $path.'/'. $file;
	    $ext = pathinfo($file, PATHINFO_EXTENSION);
	    $mimetype = mime_content_type($full_path);
	    header('Content-Disposition: softwareFile; filename="' . $file . '.' . $ext . '";');
	    header("Content-Type: " . $mimetype);
	    return readfile($full_path);
	}


	private function screenshotImageStore($request, $screenshot, $softwareId)
    {
    	foreach($screenshot as $optional)
    	{
    		$optionals = new OptionalImage();
    		$optionals->software_id = $softwareId;
    		$path = imagePath()['screenshot']['path'];
	        $size = imagePath()['screenshot']['size'];
	        if ($request->hasFile('screenshot')) {
	            $file = $optional;
	            try {
	                $filename = uploadImage($file, $path, $size);
	            } catch (\Exception $exp) {
	                $notify[] = ['error', 'Image could not be uploaded.'];
	                return back()->withNotify($notify);
	            }
	            $optionals->image = $filename;
	        }
	        $optionals->save();
    	}
    }
    
}
