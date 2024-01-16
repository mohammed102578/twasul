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
use App\Models\ProductInventory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Category;

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
    	$softwares = Software::with('productType')->where('user_id', $user->id)->latest()->paginate(getPaginate());
    	return view($this->activeTemplate . 'user.seller.software.index', compact('pageTitle', 'softwares', 'emptyMessage'));
    }

	public function create()
	{
		$pageTitle = "Upload Software";
        $categories = Category::where('type', 2)->where('status', 1)->orderby('id', 'DESC')->inRandomOrder()->get();
		$features = Features::latest()->get();
		return view($this->activeTemplate . 'user.seller.software.create', compact('pageTitle', 'features','categories'));
	}


	public function store(Request $request)
	{
       if($request->product_type==1){
            $request->validate([
                'image' => ['required', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
                'title' => 'required|string|max:255',
                'category' => 'required|exists:categories,id',
                'subcategory' => 'nullable|exists:sub_categories,id',
                'amount' => 'required|numeric|gt:0',
                'product_code' => 'required|string|max:50|unique:software',
                'product_name' => 'required|array|min:1|max:15',
                'inventory' => 'required|array|min:1|max:15',
                'description' => 'required',
            ]);
        }else if($request->product_type==2){
            $request->validate([
                'image' => ['required', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
                'screenshot.*' => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
                'title' => 'required|string|max:255',
                'category' => 'required|exists:categories,id',
                'subcategory' => 'nullable|exists:sub_categories,id',
//                'features' => 'required|array|exists:features,id',
                'tag' => 'required|array|min:3|max:15',
//                'file_include' => 'required|array|min:3|max:15',
                'amount' => 'required|numeric|gt:0',
                'url' => 'required|url',
                'description' => 'required',
                'document' => ['required', new FileTypeValidate(['pdf'])],
                'uploadSoftware' => ['required', new FileTypeValidate(['zip'])],
            ]);
        }
		
        $user = Auth::user();
    	$general = GeneralSetting::first();
        $software = new Software();
        $software->user_id = $user->id;
        $software->category_id = $request->category;
        $software->sub_category_id = $request->subcategory ? $request->subcategory : null;
        $software->title = $request->title;
        $software->product_code = $request->product_code;
        $software->amount = $request->amount;
        $software->demo_url = $request->url;
        $software->tag = $request->tag;
//        $software->file_include = $request->file_include;
        $software->description = $request->description;
        $software->product_type = $request->product_type;

        $path = imagePath()['product']['path'];
        // $size = imagePath()['product']['size'];
        $max_size = imagePath()['product']['max_size'];

        if ($request->hasFile('image')) {
            $file = $request->image;
            $dimensions = getimagesize($file);
            $size = decide_resize($dimensions, $max_size);
            try {
                $filename = uploadImage($file, $path, $size);
                $original_image = uploadImage($file, $path);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Image could not be uploaded.'];
                return back()->withNotify($notify);
            }
            $software->image = $filename;
            $software->original_image = $original_image;
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
//        $software->featuresSoftware()->attach($request->features);
        if($request->screenshot){
        	$screenshot = array_filter($request->screenshot);
        	$this->screenshotImageStore($request, $screenshot, $software->id);
        }
        if($request->product_type==1){
        	$this->productVeritiesStore($request,$software->id);
        }

        $adminNotification = new AdminNotification();
        $adminNotification->user_id = $user->id;
        $adminNotification->title = 'Upload Software'.$software->title;
        $adminNotification->click_url = urlPath('admin.software.details',$software->id);
        $adminNotification->save();

        $notify[] = ['success', 'Product has been uploaded.'];
        return back()->withNotify($notify);
	}

	public function edit($slug, $id){
		$user = Auth::user();
		$pageTitle = "Produt Update";
         $categories = Category::where('type', 2)->where('status', 1)->orderby('id', 'DESC')->inRandomOrder()->get();
		$software = Software::where('id', $id)->where('user_id', $user->id)->firstOrFail();
		$features = Features::latest()->get();
        //dd($software->verities);
		return view($this->activeTemplate . 'user.seller.software.edit', compact('pageTitle', 'features', 'software','categories'));
	}

    public function manageProduct($slug, $id){
        $user = Auth::user();
		$pageTitle = "Manage Product";
		$software = Software::where('id', $id)->where('user_id', $user->id)->firstOrFail();
		$features = Features::latest()->get();
        //dd($software->verities);
		return view($this->activeTemplate . 'user.seller.software.manage_product', compact('pageTitle', 'features', 'software'));
    }

    public function updateManage(Request $request, $id){
        $user = Auth::user();
        $general = GeneralSetting::first();
        $software =Software::where('user_id', $user->id)->where('id', $id)->firstOrFail();
        $request->validate([
            'shipping_type' => 'required|string|max:255',
            'country' => 'required',
            'shipping_charge' => 'required|numeric',
            'delivery_day' => 'required|numeric|gt:0',
            'recommand_seling_price' => 'required',
        ]);
        $software->recommand_seling_price = $request->recommand_seling_price;
        $software->product_code = $request->product_code;
        $software->demo_url = implode(',',$request->demo_url);
        $software->shipping_type = $request->shipping_type;
        $software->shipping_charge = $request->shipping_charge;
        $software->delivery_day = $request->delivery_day;
        $software->available_in_country = implode(',',$request->country);
        
        $software->updated_at = Carbon::now();
        $software->save();
        $adminNotification = new AdminNotification();
        $adminNotification->user_id = $user->id;
        $adminNotification->title = 'Update Product'.$software->title;
        $adminNotification->click_url = urlPath('admin.software.details',$software->id);
        $adminNotification->save();

        $notify[] = ['success', 'Product management has been uploaded.'];
        return back()->withNotify($notify);
    }


    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $general = GeneralSetting::first();
        $software =Software::where('user_id', $user->id)->where('id', $id)->firstOrFail();
        if($software->product_type==1){
            $request->validate([
                'image' => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
                'title' => 'required|string|max:255',
                'category' => 'required|exists:categories,id',
                'subcategory' => 'nullable|exists:sub_categories,id',
                'product_code' => 'required|string|unique:software,product_code,'.$software->id,
                'amount' => 'required|numeric|gt:0',
                'description' => 'required',
            ]);
        }else if($request->product_type==2){
            $request->validate([
                'image' => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
                'screenshot.*' => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
                'title' => 'required|string|max:255',
                'category' => 'required|exists:categories,id',
                'subcategory' => 'nullable|exists:sub_categories,id',
//                'features' => 'required|array|exists:features,id',
                'tag' => 'required|array|min:3|max:15',
//                'file_include' => 'required|array|min:3|max:15',
                'amount' => 'required|numeric|gt:0',
                'url' => 'required|url',
                'description' => 'required',
                'document' => ['required', new FileTypeValidate(['pdf'])],
                'uploadSoftware' => ['required', new FileTypeValidate(['zip'])],
            ]);
        }
        
        
        
        $software->user_id = $user->id;
        $software->category_id = $request->category;
        $software->sub_category_id = $request->subcategory ? $request->subcategory : null;
        $software->title = $request->title;
        $software->product_code = $request->product_code;
        $software->amount = $request->amount;
        $software->demo_url = $request->url;
        $software->description = $request->description;
        $software->tag = $request->tag;
//        $software->file_include = $request->file_include;
        $path = imagePath()['software']['path'];
        // $size = imagePath()['software']['size']; 
        $max_size = imagePath()['product']['max_size'];
        if ($request->hasFile('image')) {
            $file = $request->image;
            $dimensions = getimagesize($file);
            $size = decide_resize($dimensions, $max_size);
            try {
                $filename = uploadImage($file, $path, $size, $software->image);
                $original_image = uploadImage($file, $path);

            } catch (\Exception $exp) {
                $notify[] = ['error', 'Image could not be uploaded.'];
                return back()->withNotify($notify);
            }
            $software->image = $filename;
            $software->original_image = $original_image;

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
//        $software->featuresSoftware()->sync($request->features);
        if($request->screenshot){
            $screenshot = array_filter($request->screenshot);
            $this->screenshotImageStore($request, $screenshot, $software->id);
        }
        if($request->product_name){
        	$this->productVeritiesStore($request,$software->id);
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
	    $software = Software::where('id',decrypt($softwareId))->firstOrFail();
	    $file = $software->upload_software;
	    $path = imagePath()['uploadSoftware']['path'];
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


	public function softwareDocumentFile($softwareId)
	{
		$user = Auth::user();
	    $software = Software::where('id',decrypt($softwareId))->firstOrFail();
	    $file = $software->document_file;
	    $path = imagePath()['document']['path'];
	    $full_path = $path.'/'. $file;
        if (file_exists($full_path)) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            $mimetype = mime_content_type($full_path);
            header('Content-Disposition: softwareFile; filename="' . $file .'";');
            header("Content-Type: " . $mimetype);
            return readfile($full_path);
        }else{
            $notify[] = ['error', 'File does not exist.'];
            return back()->withNotify($notify);
        }
	}

    public function checkProductCode(Request $request){
        $ifExist = Software::where('product_code',$request->product_code)->first();
        if($ifExist != ''){
            return response()->json(['is_exist' => true], 200);
        }else{
            return response()->json(['is_exist' => false], 200);
        }
    }
    

	private function screenshotImageStore($request, $screenshot, $softwareId)
    {
        $max_size = imagePath()['product']['max_size'];
        
    	foreach($screenshot as $optional)
    	{
    		$optionals = new OptionalImage();
    		$optionals->software_id = $softwareId;
    		$path = imagePath()['screenshot']['path'];
	        if ($request->hasFile('screenshot')) {
	            $file = $optional;
                $dimensions = getimagesize($file);
                $size = decide_resize($dimensions, $max_size);
	            try {
	                $filename = uploadImage($file, $path, $size);
                    $original_image = uploadImage($file, $path);

	            } catch (\Exception $exp) {
	                $notify[] = ['error', 'Image could not be uploaded.'];
	                return back()->withNotify($notify);
	            }
	            $optionals->image = $filename;
	            $optionals->original_image = $original_image;
	        }
	        $optionals->save();
    	}
    }
    private function productVeritiesStore($request, $softwareId)
    {
        $i=0;
    	foreach($request->product_name as $optional)
    	{
    		$optionals = new ProductInventory();
    		$optionals->software_id = $softwareId;
            $optionals->name = $optional;
            $optionals->inventory = $request->inventory[$i];
	        $optionals->save();
            $i++;
    	}
    }

    public function productVerityRemove(Request $request){
        $optional = ProductInventory::findOrFail(decrypt($request->id));
        $optional->delete();
        $notify[] = ['success', 'Verity has been deleted.'];
        return back()->withNotify($notify);
    }

    public function productVerityUpdate(Request $request){
        $optionals = ProductInventory::find($request->id);
        $optionals->inventory = $request->qty;
        $optionals->save();
        $notify[] = ['success', 'Verity has been updated.'];
        return back()->withNotify($notify);

    }

}