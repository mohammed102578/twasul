<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Rules\FileTypeValidate;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\SubCategory;

class CategoryController extends Controller
{
    
    public function index()
    {
        $pageTitle = "Category list";
        $emptyMessage = "No data found";
        $types=config('product_types');
        $categorys = Category::with('subCategory')->latest()->paginate(getPaginate());
        return view('admin.category.index', compact('categorys', 'pageTitle', 'emptyMessage','types'));
    }

    public function store(Request $request)
    {
    	$request->validate([
            'name' => 'required|unique:categories|max:50',
            'type' => 'required'
        ]);
        $category = new Category;
        $category->name = $request->name;
        $category->type = $request->type;
        $category->status = $request->status ? 1 : 2;
        //($category);
        $category->save();
        $notify[] = ['success', 'Category has been created'];
        return back()->withNotify($notify);
    }

    public function update(Request $request)
    {
    	$request->validate([
            'name' => 'required|max:50|unique:categories,name,'.$request->id,
            'id' => 'required|exists:categories,id',
            'type' => 'required'
        ]);
        $category = Category::find($request->id);
        $category->name = $request->name;
        $category->type = $request->type;
        $category->status = $request->status ? 1 : 2;
        $category->save();
        $notify[] = ['success', 'Category has been updated'];
        return back()->withNotify($notify);
    }


    public function subCategoryIndex()
    {
        $pageTitle = "Sub Category list";
        $emptyMessage = "No data found";
        $categorys = Category::where('status', 1)->get();
        $subCategorys = SubCategory::with('category')->latest()->paginate(getPaginate());
        return view('admin.category.sub_index', compact('subCategorys', 'pageTitle', 'emptyMessage', 'categorys'));
    }


    public function subCategoryStore(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:sub_categories|max:50',
            'image' => ['required','image',new FileTypeValidate(['jpeg', 'jpg', 'png'])],
            'category_id' => 'required|exists:categories,id'
        ]);
        $subcategory = new SubCategory;
        $subcategory->name = $request->name;
        $subcategory->category_id = $request->category_id;
        $path = imagePath()['subcategory']['path'];
        $size = imagePath()['subcategory']['size'];
        if ($request->hasFile('image')) {
            $file = $request->image;
            $this->fileValidate($file);
            try {
                $filename = uploadImage($file, $path, $size);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Image could not be uploaded.'];
                return back()->withNotify($notify);
            }
            $subcategory->image = $filename;
        }
        $subcategory->save();
        $notify[] = ['success', 'Sub category has been created'];
        return back()->withNotify($notify);
    }


    public function subCategoryUpdate(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:sub_categories,id',
            'name' => 'required|max:50|unique:sub_categories,name,'.$request->id,
            'image' => ['image',new FileTypeValidate(['jpeg', 'jpg', 'png'])],
            'category_id' => 'required|exists:categories,id'
        ]);
        $subcategory = SubCategory::find($request->id);
        $subcategory->name = $request->name;
        $subcategory->category_id = $request->category_id;
        $path = imagePath()['subcategory']['path'];
        $size = imagePath()['subcategory']['size'];
        if ($request->hasFile('image')) {
            $file = $request->image;
            $this->fileValidate($file);
            try {
                $filename = uploadImage($file, $path, $size);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Image could not be uploaded.'];
                return back()->withNotify($notify);
            }
            $subcategory->image = $filename;
        }
        $subcategory->save();
        $notify[] = ['success', 'Sub category has been updated'];
        return back()->withNotify($notify);
    }


    private function fileValidate($file)
    {
        $allowedExts = array('jpeg', 'jpg', 'png');
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, $allowedExts)) {
            $notify = 'Only jpeg, jpg, png files are allowed';
            return back()->withNotify($notify);
        }
    }
}
