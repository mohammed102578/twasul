<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Frontend;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Rules\FileTypeValidate;
use App\Models\GeneralSetting;
use App\Models\SubCategory;

class BlogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        $this->activeTemplate = activeTemplate();
    }
    public function index()
    {
        $key = 'blog';
        $section = @getPageSections()->$key;
        if (!$section) {
            return abort(404);
        }
        $content = Frontend::where('data_keys', $key . '.content')->where('user_id',Auth::user()->id)->orderBy('id','desc')->first();
        $elements = Frontend::where('data_keys', $key . '.element')->where('user_id',Auth::user()->id)->orderBy('id')->orderBy('id','desc')->get();
        $pageTitle = $section->name ;
        $emptyMessage = 'No item create yet.';
        // return view('admin.frontend.index', compact('section', 'content', 'elements', 'key', 'pageTitle', 'emptyMessage'));

        $user = Auth::user();
        // $pageTitle = "Manage Blogs";
        // $emptyMessage = "No data found";
        // $services = Service::where('user_id',$user->id)->with('category')->latest()->paginate(getPaginate());
        
        return view($this->activeTemplate . 'user.seller.blog.index',compact('user','section', 'content', 'elements', 'key', 'pageTitle', 'emptyMessage'));
    }
    public function blog($key)
    {

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $key = 'blog';
        $section = @getPageSections()->$key;
        if (!$section) {
            return abort(404);
        }

        unset($section->element->modal);
        $pageTitle = $section->name . ' Items';
        // if ($id) {
        //     $data = Frontend::findOrFail($id);
        //     return view('admin.frontend.element', compact('section', 'key', 'pageTitle', 'data'));
        // }
        //$categories = Category::where('status',1)->get();
        return view($this->activeTemplate . 'user.seller.blog.create', compact('section','key', 'pageTitle'));
    }

    public function getSubcategory($id)
    {
        $sub_categories = SubCategory::where('category_id',$id)->get();
        return $sub_categories;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $key = "blog";
        $purifier = new \HTMLPurifier();
        $valInputs = $request->except('_token', 'image_input', 'key', 'status', 'type', 'id');
        //dd($valInputs);
        foreach ($valInputs as $keyName => $input) {
            if (gettype($input) == 'array') {
                $inputContentValue[$keyName] = $input;
                continue;
            }
            $inputContentValue[$keyName] = $purifier->purify($input);
        }
        $type = $request->type;
        if (!$type) {
            abort(404);
        }
        $imgJson = @getPageSections()->$key->$type->images;
        $validation_rule = [];
        $validation_message = [];
        foreach ($request->except('_token', 'video') as $input_field => $val) {
            if ($input_field == 'has_image' && $imgJson) {
                foreach ($imgJson as $imgValKey => $imgJsonVal) {
                    $validation_rule['image_input.'.$imgValKey] = ['nullable','image',new FileTypeValidate(['jpg','jpeg','png'])];
                    $validation_message['image_input.'.$imgValKey.'.image'] = inputTitle($imgValKey).' must be an image';
                    $validation_message['image_input.'.$imgValKey.'.mimes'] = inputTitle($imgValKey).' file type not supported';
                }
                continue;
            }elseif($input_field == 'seo_image'){
                $validation_rule['image_input'] = ['nullable', 'image', new FileTypeValidate(['jpeg', 'jpg', 'png'])];
                continue;
            }
            $validation_rule[$input_field] = 'required';
        }
        $request->validate($validation_rule, $validation_message, ['image_input' => 'image']);
        if ($request->id) {
            $content = Frontend::findOrFail($request->id);
            $content->category_id = $request->sel_cate;
            $content->sub_category_id = $request->sel_sub_cate;
            $content->is_submitted = ($request->has('save_as_draft')) ? 0 :1;
            $content->status = ($request->has('save_as_draft')) ? 3 :0;
        } else {
            $content = Frontend::where('data_keys', $key . '.' . $request->type)->first();
            if (!$content || $request->type == 'element') {
                // dd(Auth::user());
                $content = new Frontend();
                $content->user_id = Auth::user()->id;
                $content->category_id = $request->sel_cate;
                $content->sub_category_id = $request->sel_sub_cate;
                $content->data_keys = $key . '.' . $request->type;
                $content->is_submitted = ($request->has('save_as_draft')) ? 0 :1;
                $content->status = ($request->has('save_as_draft')) ? 3 :0;
                $content->save();
            }
        }
        if ($type == 'data') {
            $inputContentValue['image'] = @$content->data_values->image;
            if ($request->hasFile('image_input')) {
                try {
                    $inputContentValue['image'] = uploadImage($request->image_input,imagePath()['seo']['path'], imagePath()['seo']['size'], @$content->data_values->image);
                } catch (\Exception $exp) {

                    $notify[] = ['error', 'Could not upload the image.'];
                    return back()->withNotify($notify);
                }
            }
        }else{
            if ($imgJson) {
                foreach ($imgJson as $imgKey => $imgValue) {
                    $imgData = @$request->image_input[$imgKey];
                    if (is_file($imgData)) {
                        try {
                            // dd(@$content->data_values->$imgKey);
                            $inputContentValue[$imgKey] = $this->storeImage($imgJson,$type,$key,$imgData,$imgKey,@$content->data_values->$imgKey);
                        } catch (\Exception $exp) {
                            // dd($inputContentValue);

                            $notify[] = ['error', 'Could not upload the image.'];
                            return back()->withNotify($notify);
                        }
                    } else if (isset($content->data_values->$imgKey)) {
                        $inputContentValue[$imgKey] = $content->data_values->$imgKey;
                    }
                }
            }
        }
        $content->data_values = $inputContentValue;
        $content->save();
        $notify[] = ['success', 'Content has been updated.'];
        return back()->withNotify($notify);
    }

    protected function storeImage($imgJson,$type,$key,$image,$imgKey,$old_image = null)
    {
        $path = 'assets/images/frontend/' . $key;
        if ($type == 'element' || $type == 'content') {
            $size = @$imgJson
            ->$imgKey->size;
            $thumb = @$imgJson
            ->$imgKey->thumb;
        }else{
            $path = imagePath()[$key]['path'];
            $size = imagePath()[$key]['size'];
            $thumb = @imagePath()[$key]['thumb'];
        }
        return uploadImage($image, $path, $size, $old_image, $thumb);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $key = 'blog';
        $section = @getPageSections()->$key;
        if (!$section) {
            return abort(404);
        }

        unset($section->element->modal);
        $pageTitle = $section->name . ' Items';
    
        $data = Frontend::findOrFail($id);
        
        
        return view($this->activeTemplate . 'user.seller.blog.edit', compact('section','key', 'pageTitle', 'data'));


    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $request->validate(['id' => 'required']);
        $frontend = Frontend::findOrFail($request->id);
        $key = explode('.', @$frontend->data_keys)[0];
        $type = explode('.', @$frontend->data_keys)[1];
        if (@$type == 'element' || @$type == 'content') {
            $path = 'assets/images/frontend/' . $key;
            $imgJson = @getPageSections()->$key->$type->images;
            if ($imgJson) {
                foreach ($imgJson as $imgKey => $imgValue) {
                    removeFile($path . '/' . @$frontend->data_values->$imgKey);
                    removeFile($path . '/thumb_' . @$frontend->data_values->$imgKey);
                }
            }
        }
        $frontend->delete();
        $notify[] = ['success', 'Content has been removed.'];
        return back()->withNotify($notify);
    }
}
