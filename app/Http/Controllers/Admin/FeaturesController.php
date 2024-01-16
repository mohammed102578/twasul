<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Features;

class FeaturesController extends Controller
{
    public function index()
    {
        $pageTitle = "Features list";
        $emptyMessage = "No data found";
        $features = Features::latest()->paginate(getPaginate());
        return view('admin.features.index', compact('features', 'pageTitle', 'emptyMessage'));
    }

    public function store(Request $request)
    {
    	$request->validate([
    		'name' => 'required|unique:features|max:255'
    	]);
    	$features = new Features;
    	$features->name = $request->name;
    	$features->save();
    	$notify[] = ['success', 'Features create successfully'];
	    return back()->withNotify($notify);
    }

    public function update(Request $request)
    {
    	$request->validate([
    		'name' => 'required|max:255|unique:features,name,'.$request->id,
    		'id' => 'required|exists:features,id'
    	]);
    	$features = Features::find($request->id);
    	$features->name = $request->name;
    	$features->save();
    	$notify[] = ['success', 'Features update successfully'];
	    return back()->withNotify($notify);
    }
}
