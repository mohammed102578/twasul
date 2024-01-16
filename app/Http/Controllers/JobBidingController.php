<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JobBiding;
use App\Models\Job;
use Illuminate\Support\Facades\Auth;

class JobBidingController extends Controller
{
    
    public function store(Request $request)
    {
    	$request->validate([
            'job_id' => 'required|exists:jobs,id',
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|gt:0',
            'description' => 'required',
            'order_type' => 'required|in:1,2'
        ]);
    	$user = Auth::user();
        $job = Job::where('id', $request->job_id)->first();
        if($job->user_id == $user->id)
        {
            $notify[] = ["error", "You can not be biding your self job"];
            return back()->withNotify($notify);
        }
    	$jobBiding = new JobBiding();
    	$jobBiding->user_id = $user->id;
    	$jobBiding->job_id = $job->id;
    	$jobBiding->title = $request->title;
    	$jobBiding->price = $request->amount;
    	$jobBiding->order_type = $request->order_type;
    	$jobBiding->description = $request->description;
    	$jobBiding->save();
    	$notify[] = ['success', "Job biding has been created"];
    	return back()->withNotify($notify);
    }
}
