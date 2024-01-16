<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\Features;
use App\Models\OptionalImage;
use App\Models\ExtraService;
use App\Models\GeneralSetting;
use App\Models\AdminNotification;
use App\Rules\FileTypeValidate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
class ServiceController extends Controller
{
    
    public function __construct()
    {
        $this->activeTemplate = activeTemplate();
    }

    public function index()
    {
        $user = Auth::user();
        $pageTitle = "Manage service";
        $emptyMessage = "No data found";
        $services = Service::where('user_id',$user->id)->with('category')->latest()->paginate(getPaginate());
        return view($this->activeTemplate . 'user.seller.service.index', compact('pageTitle', 'services', 'emptyMessage'));
    }
    
    public function create()
    {
    	$pageTitle = "Create service";
    	$features = Features::latest()->get();
    	return view($this->activeTemplate . 'user.seller.service.create', compact('pageTitle', 'features'));
    }

    public function store(Request $request)
    {
    	$user = Auth::user();
    	$general = GeneralSetting::first();
        $request->validate([
            'image' => ['required', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
            'optional_image.*' => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
            'title' => 'required|string|max:255',
            'category' => 'required|exists:categories,id',
            'subcategory' => 'nullable|exists:sub_categories,id',
            'features' => 'required|array|exists:features,id',
            'price' => 'required|numeric|gt:0',
            'delivery' => 'required|integer|min:1',
            'tag' => 'required|array|min:3|max:15',
            'description' => 'required',
            'extra_title.*' => 'required_with:extra_price|string|max:255',
            'extra_price.*' => 'required_with:extra_title|numeric|gt:0',
        ]);
        $service = new Service();
        $service->user_id = $user->id;
        $service->category_id = $request->category;
        $service->sub_category_id = $request->subcategory ? $request->subcategory : null;
        $service->title = $request->title;
        $service->price = $request->price;
        $service->delivery_time = $request->delivery;
        $service->tag = $request->tag;
        $service->description = $request->description;
        $path = imagePath()['service']['path'];
        $size = imagePath()['service']['size'];
        if ($request->hasFile('image')) {
            $file = $request->image;
            try {
                $filename = uploadImage($file, $path, $size);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Image could not be uploaded.'];
                return back()->withNotify($notify);
            }
            $service->image = $filename;
        }
        if($general->approval_post == 1){
        	$service->status = 1;
        }
        $service->save();
        $service->featuresService()->attach($request->features);
        if($request->optional_image){
        	$optionalImage = array_filter($request->optional_image);
        	$this->optionalImageStore($request, $optionalImage, $service->id);
        }
        if($request->extra_title){
        	$extraTitle = array_filter($request->extra_title);
	        $extraPrice = array_filter($request->extra_price);
	        for($i=0; $i<count($extraTitle); $i++){
        		$extraService = new ExtraService();
        		$extraService->service_id = $service->id;
        		$extraService->title = $extraTitle[$i];
        		$extraService->price = $extraPrice[$i];
        		$extraService->save();
	        }
        }
        $adminNotification = new AdminNotification();
        $adminNotification->user_id = $user->id;
        $adminNotification->title = 'Service Create '.$service->title;
        $adminNotification->click_url = urlPath('admin.service.details',$service->id);
        $adminNotification->save();
        $notify[] = ['success', 'Service has been created.'];
        return back()->withNotify($notify);
    }


    public function edit($id, $slug)
    {
        $user = Auth::user();
        $pageTitle = "Update service";
        $features = Features::latest()->get();
        $service = Service::where('id',$id)->where('user_id', $user->id)->firstOrFail();
        return view($this->activeTemplate . 'user.seller.service.edit', compact('pageTitle', 'features', 'service'));
    }


    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $general = GeneralSetting::first();
        $request->validate([
            'image' => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
            'optional_image.*' => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
            'title' => 'required|string|max:255',
            'category' => 'required|exists:categories,id',
            'subcategory' => 'nullable|exists:sub_categories,id',
            'features' => 'required|array|exists:features,id',
            'price' => 'required|numeric|gt:0',
            'delivery' => 'required|integer|min:1',
            'tag' => 'required|array|min:3|max:15',
            'description' => 'required',
            'extra_title.*' => 'required_with:extra_price|string|max:255',
            'extra_price.*' => 'required_with:extra_title|numeric|gt:0',
        ]);
        $service =Service::where('id', $id)->where('user_id', $user->id)->firstOrFail();
        $service->user_id = $user->id;
        $service->category_id = $request->category;
        $service->sub_category_id = $request->subcategory ? $request->subcategory : null;
        $service->title = $request->title;
        $service->price = $request->price;
        $service->delivery_time = $request->delivery;
        $service->tag = $request->tag;
        $service->description = $request->description;
        $path = imagePath()['service']['path'];
        $size = imagePath()['service']['size'];
        if ($request->hasFile('image')) {
            $file = $request->image;
            try {
                $filename = uploadImage($file, $path, $size, $service->image);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Image could not be uploaded.'];
                return back()->withNotify($notify);
            }
            $service->image = $filename;
        }
        if($general->approval_post == 1){
            $service->status = 1;
        }
        else{
            $service->status = 0;
            $service->created_at = Carbon::now();
        }
        $service->updated_at = Carbon::now();
        $service->save();
        $service->featuresService()->sync($request->features);
        if($request->optional_image){
            $optionalImage = array_filter($request->optional_image);
            $this->optionalImageStore($request, $optionalImage, $service->id);
        }
        if($request->extra_title){
            $extraDelete = ExtraService::where('service_id', $service->id)->delete();
            $extraTitle = array_filter($request->extra_title);
            $extraPrice = array_filter($request->extra_price);
            for($i=0; $i<count($extraTitle); $i++)
            {
                $extraService = new ExtraService();
                $extraService->service_id = $service->id;
                $extraService->title = $extraTitle[$i];
                $extraService->price = $extraPrice[$i];
                $extraService->save();
            }
        }
        $adminNotification = new AdminNotification();
        $adminNotification->user_id = $user->id;
        $adminNotification->title = 'Service updated '.$service->title;
        $adminNotification->click_url = urlPath('admin.service.details',$service->id);
        $adminNotification->save();
        $notify[] = ['success', 'Service has been updated.'];
        return back()->withNotify($notify);
    }


    public function optionalImageRemove(Request $request)
    {
        $optional = OptionalImage::findOrFail(decrypt($request->id));
        $path = imagePath()['optionalService']['path'];
        $file_remove = $path . '/' . $optional->image;
        removeFile($file_remove);
        $optional->delete();
        $notify[] = ['success', 'Image has been deleted.'];
        return back()->withNotify($notify);
    }

    private function optionalImageStore($request, $optionalImage, $serviceId)
    {
    	foreach($optionalImage as $optional)
    	{
    		$optionals = new OptionalImage();
    		$optionals->service_id = $serviceId;
    		$path = imagePath()['optionalService']['path'];
	        $size = imagePath()['optionalService']['size'];
	        if ($request->hasFile('optional_image')) {
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
