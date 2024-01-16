<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use Illuminate\Http\Request;
use App\Models\Software;
use App\Models\Category;
use App\Models\Features;
use App\Models\GeneralSetting;
use App\Models\OptionalImage;
use App\Models\ProductInventory;
use App\Rules\FileTypeValidate;
use Carbon\Carbon;

class SoftwareController extends Controller
{

	public function details($id)
    {
    	$pageTitle = "Product details";
    	$software = Software::findOrFail($id);
    	return view('admin.software.details', compact('pageTitle', 'software'));
    }

    public function index()
    {
    	$pageTitle = "Manage All Software";
    	$emptyMessage = "No data found";
    	$softwares = Software::with('category', 'user', 'subCategory','productType')->latest()->paginate(getPaginate());
    	return view('admin.software.index', compact('pageTitle', 'emptyMessage', 'softwares'));
    }

    public function pending()
    {
    	$pageTitle = "Software pending list";
    	$emptyMessage = "No data found";
    	$softwares = Software::where('status', 0)->with('category', 'user', 'subCategory','productType')->latest()->paginate(getPaginate());
    	return view('admin.software.index', compact('pageTitle', 'emptyMessage', 'softwares'));
    }
    public function approved()
    {
    	$pageTitle = "Software approved list";
    	$emptyMessage = "No data found";
    	$softwares = Software::where('status', 1)->with('category', 'user', 'subCategory','productType')->latest()->paginate(getPaginate());
    	return view('admin.software.index', compact('pageTitle', 'emptyMessage', 'softwares'));
    }

    public function cancel()
    {
    	$pageTitle = "Software approved list";
    	$emptyMessage = "No data found";
    	$softwares = Software::where('status', 2)->with('category', 'user', 'subCategory','productType')->latest()->paginate(getPaginate());
    	return view('admin.software.index', compact('pageTitle', 'emptyMessage', 'softwares'));
    }

    public function softwareCategory(Request $request)
    {
    	$category = Category::findOrFail($request->category);
        $categoryId = $category->id;
    	$pageTitle = "Software search by category - " . $category->name;
    	$emptyMessage = "No data found";
    	$softwares = Software::where('category_id', $category->id)->with('category', 'user', 'subCategory','productType')->latest()->paginate(getPaginate());
    	return view('admin.software.index', compact('pageTitle', 'emptyMessage', 'softwares', 'categoryId'));
    }

    public function approvedBy(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:software,id'
        ]);
        $software = Software::findOrFail($request->id);
        $software->status = 1;
        $software->created_at = Carbon::now();
        $software->save();
        $notify[] = ['success', 'Software has been approved'];
        return back()->withNotify($notify);
    }

    public function cancelBy(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:software,id'
        ]);
        $software = Software::findOrFail($request->id);
        $software->status = 2;
        $software->created_at = Carbon::now();
        $software->save();
        $notify[] = ['success', 'Software has been canceled'];
        return back()->withNotify($notify);
    }


    public function search(Request $request, $scope)
    {
        $search = $request->search;
        $softwares = Software::where(function ($softwares) use ($search) {
            $softwares->where('amount', $search)
                ->orWhereHas('user', function ($user) use ($search) {
                    $user->where('username', 'like', "%$search%");
                });
            });
        $pageTitle = '';
        switch ($scope) {
            case 'approved':
                $pageTitle .= 'Approved ';
                $softwares = $softwares->where('status', 1);
                break;
            case 'pending':
                $pageTitle .= 'Pending ';
                $softwares = $softwares->where('status', 0);
                break;
            case 'cancel':
                $pageTitle .= 'Cancel ';
                $softwares = $softwares->where('status', 2);
                break;
        }
        $softwares = $softwares->latest()->paginate(getPaginate());
        $pageTitle .= 'Software search by - ' . $search;
        $emptyMessage = 'No data found';
        return view('admin.software.index', compact('pageTitle', 'search', 'scope', 'emptyMessage', 'softwares'));
    }


    public function softwareFile($softwareId)
    {
        $software = Software::where('id',decrypt($softwareId))->firstOrFail();
        $file = $software->upload_software;
        $path = imagePath()['uploadSoftware']['path'];
        $full_path = $path.'/'. $file;
        if(file_exists($full_path)){
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

    public function softwareDocument($softwareId)
    {
        $software = Software::where('id',decrypt($softwareId))->firstOrFail();
        $file = $software->document_file;
        $path = imagePath()['document']['path'];
        $full_path = $path.'/'. $file;
        if(file_exists($full_path)){
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


    //=================admin products
    public function indexadmin()
    {
    	$pageTitle = "Manage All Software - Admin";
    	$emptyMessage = "No data found";
    	$softwares = Software::where('user_id',0)->whereNotIn('status',[10])->with('category', 'user', 'subCategory','productType')->latest()->paginate(getPaginate());
    	return view('admin.software.adminindex', compact('pageTitle', 'emptyMessage', 'softwares'));
    }

    public function detailsadmin($id)
    {
    	$pageTitle = "Product details";
    	$software = Software::findOrFail($id);
    	return view('admin.software.admindetails', compact('pageTitle', 'software'));
    }

    public function createadminproduct()
	{
		$pageTitle = "Create Product";
        $categories = Category::where('type', 2)->where('status', 1)->orderby('id', 'DESC')->inRandomOrder()->get();
		$features = Features::latest()->get();
		return view('admin.software.create', compact('pageTitle', 'features','categories'));
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
                'features' => 'required|array|exists:features,id',
                'tag' => 'required|array|min:3|max:15',
                'file_include' => 'required|array|min:3|max:15',
                'amount' => 'required|numeric|gt:0',
                'url' => 'required|url',
                'description' => 'required',
                'document' => ['required', new FileTypeValidate(['pdf'])],
                'uploadSoftware' => ['required', new FileTypeValidate(['zip'])],
            ]);
        }
		
        $user = 0;
    	$general = GeneralSetting::first();
        $software = new Software();
        $software->user_id = 0;
        $software->category_id = $request->category;
        $software->sub_category_id = $request->subcategory ? $request->subcategory : null;
        $software->title = $request->title;
        $software->product_code = $request->product_code;
        $software->amount = $request->amount;
        $software->demo_url = $request->url;
        $software->tag = $request->tag;
        $software->file_include = $request->file_include;
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
        $software->featuresSoftware()->attach($request->features);
        if($request->screenshot){
        	$screenshot = array_filter($request->screenshot);
        	$this->screenshotImageStore($request, $screenshot, $software->id);
        }
        if($request->product_type==1){
        	$this->productVeritiesStore($request,$software->id);
        }

        $adminNotification = new AdminNotification();
        $adminNotification->user_id = 0;
        $adminNotification->title = 'Upload Software'.$software->title;
        $adminNotification->click_url = urlPath('admin.software.details',$software->id);
        $adminNotification->save();

        $notify[] = ['success', 'Product has been uploaded.'];
        return back()->withNotify($notify);
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


    public function edit($slug, $id){
		// $user = Auth::user();
		$pageTitle = "Software Update";
        $categories = Category::where('type', 2)->where('status', 1)->orderby('id', 'DESC')->inRandomOrder()->get();
		$software = Software::where('id', $id)->where('user_id', 0)->firstOrFail();
		$features = Features::latest()->get();
		return view('admin.software.edit', compact('pageTitle', 'features', 'software','categories'));
	}

    public function delete(Request $request)
    {
        $user = 0;
        $general = GeneralSetting::first();
        $software =Software::where('user_id', 0)->where('id', $request->id)->firstOrFail();
              
        $software->status = 10;
        $software->created_at = Carbon::now();
        $software->updated_at = Carbon::now();
        $software->save();

        $adminNotification = new AdminNotification();
        $adminNotification->user_id = 0;
        $adminNotification->title = 'delete Software'.$software->title;
        $adminNotification->click_url = urlPath('admin.software.details',$software->id);
        $adminNotification->save();

        $notify[] = ['success', 'Products has been deleted.'];
        return back()->withNotify($notify);
    }

    public function update(Request $request, $id)
    {
        $user = 0;
        $general = GeneralSetting::first();
        $software =Software::where('user_id', 0)->where('id', $id)->firstOrFail();
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
                'features' => 'required|array|exists:features,id',
                'tag' => 'required|array|min:3|max:15',
                'file_include' => 'required|array|min:3|max:15',
                'amount' => 'required|numeric|gt:0',
                'url' => 'required|url',
                'description' => 'required',
                'document' => ['required', new FileTypeValidate(['pdf'])],
                'uploadSoftware' => ['required', new FileTypeValidate(['zip'])],
            ]);
        }
        
        
        
        $software->user_id = 0;
        $software->category_id = $request->category;
        $software->sub_category_id = $request->subcategory ? $request->subcategory : null;
        $software->title = $request->title;
        $software->product_code = $request->product_code;
        $software->amount = $request->amount;
        $software->demo_url = $request->url;
        $software->description = $request->description;
        $software->tag = $request->tag;
        $software->file_include = $request->file_include;
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
        $software->featuresSoftware()->sync($request->features);
        if($request->screenshot){
            $screenshot = array_filter($request->screenshot);
            $this->screenshotImageStore($request, $screenshot, $software->id);
        }
        if($request->product_name){
        	$this->productVeritiesStore($request,$software->id);
        }

        $adminNotification = new AdminNotification();
        $adminNotification->user_id = 0;
        $adminNotification->title = 'Update Software'.$software->title;
        $adminNotification->click_url = urlPath('admin.software.details',$software->id);
        $adminNotification->save();

        $notify[] = ['success', 'software has been uploaded.'];
        return back()->withNotify($notify);
    }


    public function manageProduct($slug, $id){
        $user = 0;
		$pageTitle = "Manage Product";
		$software = Software::where('id', $id)->where('user_id', 0)->firstOrFail();
		$features = Features::latest()->get();
        //dd($software->verities);
		return view('admin.software.manage_product', compact('pageTitle', 'features', 'software'));
    }

    public function updateManage(Request $request, $id){
        $user = 0;
        $general = GeneralSetting::first();
        $software =Software::where('user_id', 0)->where('id', $id)->firstOrFail();
        $request->validate([
            'shipping_type' => 'required|string|max:255',
            'country' => 'required',
            'shipping_charge' => 'required|numeric',
            'delivery_day' => 'required|numeric|gt:0',
            'recommand_seling_price' => 'required',
            'product_code'=>'required|unique:software,product_code,'.$id
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
        $adminNotification->user_id = 0;
        $adminNotification->title = 'Update Product'.$software->title;
        $adminNotification->click_url = urlPath('admin.software.details',$software->id);
        $adminNotification->save();

        $notify[] = ['success', 'Product management has been uploaded.'];
        return back()->withNotify($notify);
    }

    //=================end admin produts
}
