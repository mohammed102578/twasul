<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Job;
use Carbon\Carbon;
use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Auth;

class JobController extends Controller
{
	public function __construct()
    {
        $this->activeTemplate = activeTemplate();
    }
    public function create()
    {
    	$pageTitle = "Job Create";
    	return view($this->activeTemplate . 'user.buyer.job.create', compact('pageTitle'));
    }
    public function index()
    {
    	$user = Auth::user();
    	$pageTitle = "Manage Job";
    	$emptyMessage = "No data found";
    	$jobs = Job::where('user_id', $user->id)->latest()->paginate(getPaginate());
    	return view($this->activeTemplate . 'user.buyer.job.index', compact('pageTitle', 'emptyMessage', 'jobs'));
    }

    public function store(Request $request)
    {
        $general = GeneralSetting::first();
        $user = Auth::user();
    	$request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'title' => 'required|string|max:255',
            'category' => 'required|exists:categories,id',
            'subcategory' => 'nullable|exists:sub_categories,id',
            'amount' => 'required|numeric|gt:0',
            'delivery' => 'required|integer|min:1',
            'skill' => 'required|array|min:3|max:15',
            'description' => 'required',
            'requirement' => 'required',
        ]);
    	$job = new Job();
    	$job->title = $request->title;
    	$job->user_id = $user->id;
    	$job->category_id = $request->category;
        $job->sub_category_id = $request->subcategory ? $request->subcategory : null;
        $job->amount = $request->amount;
        $job->delivery_time = $request->delivery;
        $job->skill = $request->skill;
        $job->description = $request->description;
        $job->requirements = $request->requirement;
        $path = imagePath()['job']['path'];
        $size = imagePath()['job']['size'];
        if ($request->hasFile('image')){
            $file = $request->image;
            $this->fileValidate($file);
            try {
                $filename = uploadImage($file, $path, $size);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Image could not be uploaded.'];
                return back()->withNotify($notify);
            }
            $job->image = $filename;
        }
        if($general->approval_post  == 1){
            $job->status = 1;
        }
        $job->updated_at = Carbon::now();
        $job->save();
        $notify[] = ['success', 'Job has been created.'];
        return back()->withNotify($notify);
    }

    public function edit($slug, $id)
    {
    	$user = Auth::user();
    	$pageTitle = "Job Update";
    	$job = Job::where('user_id', $user->id)->where('id', $id)->firstOrFail();
    	return view($this->activeTemplate . 'user.buyer.job.edit', compact('pageTitle', 'job')); 
    }

    public function update(Request $request, $id)
    {
        $general = GeneralSetting::first();
        $user = Auth::user();
    	$request->validate([
            'image' => 'image|mimes:jpeg,png,jpg|max:2048',
            'title' => 'required|string|max:255',
            'category' => 'required|exists:categories,id',
            'subcategory' => 'nullable|exists:sub_categories,id',
            'amount' => 'required|numeric|gt:0',
            'delivery' => 'required|integer|min:1',
            'skill' => 'required|array|min:3|max:15',
            'description' => 'required',
            'requirement' => 'required',
        ]);
    	$job = Job::where('id', $id)->where('user_id', $user->id)->firstOrFail();
    	$job->title = $request->title;
    	$job->user_id = $user->id;
    	$job->category_id = $request->category;
        $job->sub_category_id = $request->subcategory ? $request->subcategory : null;
        $job->amount = $request->amount;
        $job->delivery_time = $request->delivery;
        $job->skill = $request->skill;
        $job->description = $request->description;
        $job->requirements = $request->requirement;
        $path = imagePath()['job']['path'];
        $size = imagePath()['job']['size'];
        if ($request->hasFile('image')) {
            $file = $request->image;
            $this->fileValidate($file);
            try {
                $filename = uploadImage($file, $path, $size, $job->image);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Image could not be uploaded.'];
                return back()->withNotify($notify);
            }
            $job->image = $filename;
        }
        if($general->approval_post == 1){
            $job->status = 1;
        }else{
            $job->status = 0;
            $job->created_at = Carbon::now();
        }
        $job->updated_at = Carbon::now();
        $job->save();
        $notify[] = ['success', 'Job has been updated.'];
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

    public function cancelBy(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:jobs,id'
        ]);
        $user = Auth::user();
        $job = Job::where('user_id', $user->id)->where('id', $request->id)->firstOrFail();
        $job->status = 2;
        $job->created_at = Carbon::now();
        $job->save();
        $notify[] = ['success', 'Job has been closed.'];
        return back()->withNotify($notify);
    }
}
