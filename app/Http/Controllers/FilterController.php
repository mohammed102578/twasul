<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\Software;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Job;
use App\Models\Rank;
use App\Models\Features;
use Session;

class FilterController extends Controller
{
	public function __construct()
    {
        $this->activeTemplate = activeTemplate();
    }

    public function allServiceSearch(Request $request)
    {
        $level = null;
        $featuresData = null;
        $pageTitle = "Service Filter Search";
        $emptyMessage = "No data found";
        $services = Service::where('status', 1)->whereHas('category', function($q){ $q->where('status', 1);});
        if($request->level){
            $value = $request->level;
            $level = collect($value);
            $services = $services->whereHas('user',function($q) use($value){
                $q->whereIn('rank_id',$value);
            });
        }
        if($request->feature){
            $featuresValue = $request->feature;
            $featuresData = collect($featuresValue);
            $services = $services->whereHas('featuresService',function($q) use($featuresValue){
                $q->whereIn('features_id',$featuresValue);
            });
        }
        if($request->price){
            $rangeArr = filter_var($request->price,FILTER_SANITIZE_NUMBER_INT);
            $newArray = explode("-",$rangeArr);
            if(session()->has('range')){
                session()->forget('range');
            }
            session()->put('range',$newArray);
            $services = $services->whereBetween('price', $newArray);
        }else{
            return redirect()->route('home');
        }
        $services = $services->with('user', 'user.rank')->paginate(getPaginate());
        return view($this->activeTemplate. 'service', compact('services', 'level', 'pageTitle', 'emptyMessage', 'featuresData'));
    }


    public function serviceSearch(Request $request)
    {
        $pageTitle = "Service search";
        $emptyMessage = "No data found";
        $search = $request->search;
        $categorys = Category::where('type', 1)->where('status', 1)->orderby('id', 'DESC')->inRandomOrder()->get();
        $services = Service::where('status', 1)->whereHas('category', function($q){
                $q->where('status', 1);
            })->where('title', 'LIKE', "%$search%")->with('user')->paginate(getPaginate());
        return view($this->activeTemplate . 'service', compact('services', 'pageTitle', 'emptyMessage', 'search','categorys'));
    }


    public function serviceDefault(Request $request)
    {
        $request->validate([
            'default' => 'required|in:default,priceHighToLow,priceLowToHigh,service,software,job',
        ]);
        $filterSearch = $request->default;
        $pageTitle = "Service search";
        $emptyMessage = "No data found";
        if($request->default == "default"){
            return redirect()->route('home');
        }
        elseif($request->default == "priceHighToLow"){
            $services = Service::where('status', 1)->whereHas('category', function($q){
                $q->where('status', 1);
            })->orderBy('price', 'DESC')->with('user')->paginate(getPaginate());
        }
        elseif($request->default == "priceLowToHigh"){
            $services = Service::where('status', 1)->whereHas('category', function($q){
                $q->where('status', 1);
            })->orderBy('price', 'ASC')->with('user')->paginate(getPaginate());
        }
        elseif($request->default == "service"){
            return redirect()->route('service');
        }
        elseif($request->default == "software"){
            return redirect()->route('software');
        }
        elseif($request->default == "job"){
            return redirect()->route('job');
        }
        return view($this->activeTemplate . 'home', compact('services', 'pageTitle', 'emptyMessage', 'filterSearch'));
    }


    public function serviceCategory(Request $request, $slug, $categoryId)
    {
        $level = null;
        $featuresData = null;
        $categorys = Category::where('type', 1)->where('status', 1)->orderby('id', 'DESC')->inRandomOrder()->get();
        $emptyMessage = "No data found";
        $category = Category::where('status', 1)->where('id', $categoryId)->firstOrFail();
        $pageTitle = $category->name;
        $subCategorys = SubCategory::where('category_id', $category->id)->latest()->get();
        $services = Service::where('status', 1)->where('category_id', $category->id);
        if($request->level){
            $value = $request->level;
            $level = collect($value);
            $services = $services->whereHas('user',function($q) use($value){
                $q->whereIn('rank_id',$value);
            });
        }
        if($request->feature){
            $featuresValue = $request->feature;
            $featuresData = collect($featuresValue);
            $services = $services->whereHas('featuresService',function($q) use($featuresValue){
                $q->whereIn('features_id',$featuresValue);
            });
        }
        if($request->price){
            $rangeArr = filter_var($request->price,FILTER_SANITIZE_NUMBER_INT);
            $newArray = explode("-",$rangeArr);
            if(session()->has('range')){
                session()->forget('range');
            }
            session()->put('range',$newArray);
            $services = $services->whereBetween('price', $newArray);
        }
        $services = $services->with('user', 'user.rank')->paginate(getPaginate());
        return view($this->activeTemplate . 'service_category', compact('services', 'pageTitle', 'emptyMessage', 'subCategorys', 'category', 'level', 'featuresData','categorys'));
    }

    public function serviceSubCategory($slug, $subCategoryId)
    {
        $emptyMessage = "No data found";
        $categorys = Category::where('type', 1)->where('status', 1)->orderby('id', 'DESC')->inRandomOrder()->get();
        $subCategorys = SubCategory::where('id', $subCategoryId)->firstOrFail();
        $pageTitle = $subCategorys->name;
        $services = Service::where('status', 1)->whereHas('category', function($q){
                $q->where('status', 1);
            })->where('sub_category_id', $subCategorys->id)->with('user', 'category')->paginate(getPaginate());
        return view($this->activeTemplate . 'service', compact('services', 'pageTitle', 'emptyMessage','categorys'));
    }

    public function softwareItemSearch(Request $request)
    {
        $level = null;
        $featuresData = null;
        $pageTitle = "Service Search";
        $emptyMessage = "No data found";
        $categorys = Category::where('type', 2)->where('status', 1)->orderby('id', 'DESC')->inRandomOrder()->get();
        $softwares = Software::where('status', 1)->whereHas('category', function($q){ $q->where('status', 1);});
        if($request->level){
            $value = $request->level;
            $level = collect($value);
            $softwares = $softwares->whereHas('user',function($q) use($value){
                $q->whereIn('rank_id',$value);
            });
        }
        if($request->feature){
            $featuresValue = $request->feature;
            $featuresData = collect($featuresValue);
            $softwares = $softwares->whereHas('featuresSoftware',function($q) use($featuresValue){
                $q->whereIn('features_id',$featuresValue);
            });
        }
        if($request->price){
            $rangeArr = filter_var($request->price,FILTER_SANITIZE_NUMBER_INT);
            $newArray = explode("-",$rangeArr);
            if(session()->has('range')){
                session()->forget('range');
            }
            session()->put('range',$newArray);
            $softwares = $softwares->whereBetween('amount', $newArray);
        }else{
            return redirect()->route('software');
        }
        $softwares = $softwares->with('user', 'user.rank')->paginate(getPaginate());
        return view($this->activeTemplate. 'software', compact('softwares', 'level', 'pageTitle', 'emptyMessage', 'featuresData','categorys'));
    }

    public function softwareCategory(Request $request, $slug, $categoryId)
    {
        $emptyMessage = "No data found";
        $level = null;
        $featuresData = null;
        $categorys = Category::where('type', 2)->where('status', 1)->orderby('id', 'DESC')->inRandomOrder()->get();
        $category = Category::where('status', 1)->where('id', $categoryId)->firstOrFail();
        $pageTitle = $category->name;
        $subCategorys = SubCategory::where('category_id', $category->id)->latest()->get();
        $softwares = Software::where('status', 1)->whereHas('category', function($q){
                $q->where('status', 1);
            })->where('category_id', $category->id);
        if($request->level){
            $value = $request->level;
            $level = collect($value);
            $softwares = $softwares->whereHas('user',function($q) use($value){
                $q->whereIn('rank_id',$value);
            });
        }
        if($request->feature){
            $featuresValue = $request->feature;
            $featuresData = collect($featuresValue);
            $softwares = $softwares->whereHas('featuresSoftware',function($q) use($featuresValue){
                $q->whereIn('features_id',$featuresValue);
            });
        }
        if($request->price){
            $rangeArr = filter_var($request->price,FILTER_SANITIZE_NUMBER_INT);
            $newArray = explode("-",$rangeArr);
            if(session()->has('range')){
                session()->forget('range');
            }
            session()->put('range',$newArray);
            $softwares = $softwares->whereBetween('amount', $newArray);
        }
        $softwares = $softwares->with('user', 'user.rank')->paginate(getPaginate());
        return view($this->activeTemplate . 'software_category', compact('softwares', 'pageTitle', 'emptyMessage', 'subCategorys', 'category', 'level', 'featuresData','categorys'));
    }

    public function softwareSubCategory($slug, $subCategoryId)
    {
        $emptyMessage = "No data found";
        $categorys = Category::where('type', 2)->where('status', 1)->orderby('id', 'DESC')->inRandomOrder()->get();
        $subCategorys = SubCategory::where('id', $subCategoryId)->firstOrFail();
        $pageTitle = $subCategorys->name;
        $softwares = Software::where('status', 1)->whereHas('category', function($q){
                $q->where('status', 1);
            })->where('sub_category_id', $subCategorys->id)->with('user')->paginate(getPaginate());
        return view($this->activeTemplate . 'software', compact('softwares', 'pageTitle', 'emptyMessage','categorys'));
    }

    public function softwareSearch(Request $request)
    {
        $pageTitle = "Software search";
        $emptyMessage = "No data found";
        $search = $request->search;
        $categorys = Category::where('type', 2)->where('status', 1)->orderby('id', 'DESC')->inRandomOrder()->get();
        $softwares = Software::where('status', 1)->whereHas('category', function($q){
                $q->where('status', 1);
            })->where('title', 'LIKE', "%$search%")->with('user')->paginate(getPaginate());
        $trendingProducts = Software::where('status', 1)->orderby('id', 'DESC')->whereHas('category', function ($q) {
            $q->where('status', 1);
            })->with('user', 'user.rank')->latest()->paginate(getPaginate());
        return view($this->activeTemplate . 'software', compact('softwares', 'trendingProducts','pageTitle', 'emptyMessage', 'search','categorys'));
    }

       public function softwareCountrySearch(Request $request)
    {
        $pageTitle = "Software search";
        $emptyMessage = "No data found";
        $ctry = $request->ctr;
        $categorys = Category::where('type', 2)->where('status', 1)->orderby('id', 'DESC')->inRandomOrder()->get();
        $softwares = Software::where('status', 1)->whereHas('category', function($q){
                $q->where('status', 1);
            })->where('available_in_country', 'LIKE', "%$ctry%")->with('user')->paginate(getPaginate());
        return view($this->activeTemplate . 'software', compact('softwares', 'pageTitle', 'emptyMessage', 'ctry','categorys'));
    }

    public function jobItemSearch(Request $request)
    {
        $level = null;
        $pageTitle = "Job Search";
        $emptyMessage = "No data found";
        $jobs = Job::where('status', 1)->whereHas('category', function($q){ $q->where('status', 1);});
        if($request->level){
            $value = $request->level;
            $level = collect($value);
            $jobs = $jobs->whereHas('user',function($q) use($value){
                $q->whereIn('rank_id',$value);
            });
        }
        if($request->price){
            $rangeArr = filter_var($request->price,FILTER_SANITIZE_NUMBER_INT);
            $newArray = explode("-",$rangeArr);
            if(session()->has('range')){
                session()->forget('range');
            }
            session()->put('range',$newArray);
            $jobs = $jobs->whereBetween('amount', $newArray);
        }else{
            return redirect()->route('job');
        }
        $jobs = $jobs->with('user', 'user.rank', 'jobBiding')->paginate(getPaginate());
        return view($this->activeTemplate. 'job', compact('jobs', 'level', 'pageTitle', 'emptyMessage'));
    }

    public function jobCategory(Request $request, $slug, $categoryId)
    {
        $emptyMessage = "No data found";
        $level = null;
        $featuresData = null;
        $categorys = Category::where('type', 3)->where('status', 1)->orderby('id', 'DESC')->inRandomOrder()->get();
        $category = Category::where('status', 1)->where('id', $categoryId)->firstOrFail();
        $pageTitle = $category->name;
        $subCategorys = SubCategory::where('category_id', $category->id)->latest()->get();
        $jobs = Job::where('status', 1)->whereHas('category', function($q){
                $q->where('status', 1);
            })->where('category_id', $category->id);
        if($request->level){
            $value = $request->level;
            $level = collect($value);
            $jobs = $jobs->whereHas('user',function($q) use($value){
                $q->whereIn('rank_id',$value);
            });
        }
        if($request->price){
            $rangeArr = filter_var($request->price,FILTER_SANITIZE_NUMBER_INT);
            $newArray = explode("-",$rangeArr);
            if(session()->has('range')){
                session()->forget('range');
            }
            session()->put('range',$newArray);
            $jobs = $jobs->whereBetween('amount', $newArray);
        }
        $jobs = $jobs->with('user', 'user.rank', 'jobBiding')->paginate(getPaginate());
        return view($this->activeTemplate . 'job_category', compact('pageTitle', 'jobs', 'emptyMessage', 'subCategorys', 'category', 'level','categorys'));
    }


    public function jobSubCategory($slug, $subCategoryId)
    {
        $emptyMessage = "No data found";
        $categorys = Category::where('type', 3)->where('status', 1)->orderby('id', 'DESC')->inRandomOrder()->get();
        $subCategorys = SubCategory::where('id', $subCategoryId)->firstOrFail();
        $pageTitle = $subCategorys->name;
        $jobs = Job::where('status', 1)->whereHas('category', function($q){
                $q->where('status', 1);
            })->where('sub_category_id', $subCategorys->id)->with('user')->paginate(getPaginate());
        return view($this->activeTemplate . 'job', compact('jobs', 'pageTitle', 'emptyMessage','categorys'));
    }


    public function jobSearch(Request $request)
    {
        $pageTitle = "Job search";
        $emptyMessage = "No data found";
        $search = $request->search;
        $categorys = Category::where('type', 3)->where('status', 1)->orderby('id', 'DESC')->inRandomOrder()->get();
        $jobs = Job::where('status', 1)->whereHas('category', function($q){
                $q->where('status', 1);
            })->where('title', 'LIKE', "%$search%")->with('user')->paginate(getPaginate());
        $suggestedJobs = Job::where('status', 0)->orderby('id', 'DESC')->whereHas('category', function ($q) {
                $q->where('status', 1); // Jobs have staus 0 mean 'not approved', 'not cancel'
            })->with('user', 'user.rank', 'jobBiding')->paginate(getPaginate());
        return view($this->activeTemplate . 'job', compact('jobs', 'suggestedJobs','pageTitle', 'emptyMessage', 'search','categorys'));
    }

public function searchAll(Request $request)
    {   
        $search_all=true;
        $pageTitle = "Search";
        $emptyMessage = "No data found";
        $search = $request->search;
         $categorys = Category::where('status', 1)->orderby('id', 'DESC')->inRandomOrder()->get();
        $services = Service::where('status', 1)->whereHas('category', function($q){
                $q->where('status', 1);
            })->where('title', 'LIKE', "%$search%")->with('user')->paginate(getPaginate());

        $softwares = Software::where('status', 1)->whereHas('category', function($q){
                $q->where('status', 1);
            })->where('title', 'LIKE', "%$search%")->with('user')->paginate(getPaginate());

        $jobs = Job::where('status', 1)->whereHas('category', function($q){
                $q->where('status', 1);
            })->where('title', 'LIKE', "%$search%")->with('user')->paginate(getPaginate());

        return view($this->activeTemplate . 'search', compact('jobs','softwares','services', 'pageTitle', 'emptyMessage', 'search','categorys'));
    }

    
}
