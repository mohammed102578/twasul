<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Job;
use App\Models\Category;
use Carbon\Carbon;
use App\Models\JobBiding;

class JobController extends Controller
{
    public function index()
    {
    	$pageTitle = "Manage All Job";
    	$emptyMessage = "No data found";
    	$jobs = Job::latest()->with('user', 'category', 'subCategory')->paginate(getPaginate());
    	return view('admin.job.index', compact('pageTitle', 'emptyMessage', 'jobs'));
    }

    public function details($id)
    {
    	$pageTitle = "Job Details";
    	$job = Job::findOrFail($id);
    	return view('admin.job.details', compact('pageTitle', 'job'));
    }

    public function pending()
    {
    	$pageTitle = "Pending Job";
    	$emptyMessage = "No data found";
    	$jobs = Job::where('status', 0)->latest()->with('user', 'category', 'subCategory')->paginate(getPaginate());
    	return view('admin.job.index', compact('pageTitle', 'emptyMessage', 'jobs'));
    }

    public function approved()
    {
    	$pageTitle = "Approved Job";
    	$emptyMessage = "No data found";
    	$jobs = Job::where('status', 1)->latest()->with('user', 'category', 'subCategory')->paginate(getPaginate());
    	return view('admin.job.index', compact('pageTitle', 'emptyMessage', 'jobs'));
    }

    public function closed()
    {
        $pageTitle = "Closed Job";
        $emptyMessage = "No data found";
        $jobs = Job::where('status', 2)->latest()->with('user', 'category', 'subCategory')->paginate(getPaginate());
        return view('admin.job.index', compact('pageTitle', 'emptyMessage', 'jobs'));
    }

    public function cancel()
    {
    	$pageTitle = "Cancel Job";
    	$emptyMessage = "No data found";
    	$jobs = Job::where('status', 3)->latest()->with('user', 'category', 'subCategory')->paginate(getPaginate());
    	return view('admin.job.index', compact('pageTitle', 'emptyMessage', 'jobs'));
    }


    public function approvedBy(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:jobs,id'
        ]);
        $job = Job::findOrFail($request->id);
        $job->status = 1;
        $job->created_at = Carbon::now();
        $job->save();
        $notify[] = ['success', 'Job has been approved'];
        return back()->withNotify($notify);
    }

    public function cancelBy(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:jobs,id'
        ]);
        $job = Job::findOrFail($request->id);
        $job->status = 3;
        $job->created_at = Carbon::now();
        $job->save();
        $notify[] = ['success', 'Job has been canceled'];
        return back()->withNotify($notify);
    }


    public function closedBy(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:jobs,id'
        ]);
        $job = Job::findOrFail($request->id);
        $job->status = 2;
        $job->created_at = Carbon::now();
        $job->save();
        $notify[] = ['success', 'Job has been closed'];
        return back()->withNotify($notify);
    }


    public function jobCategory(Request $request)
    {
        $category = Category::findOrFail($request->category);
        $searchCategory = $category->id;
        $pageTitle = "Job search by category - " . $category->name;
        $emptyMessage = "No data found";
        $jobs = Job::where('category_id', $category->id)->with('category', 'user', 'subCategory')->latest()->paginate(getPaginate());
        return view('admin.job.index', compact('pageTitle', 'emptyMessage', 'jobs', 'searchCategory'));
    }


    public function jobBiding($id)
    {
        $pageTitle = "Job Biding List";
        $emptyMessage = "No data found";
        $jobBidings = JobBiding::where('job_id', $id)->with('user')->latest()->paginate(getPaginate());
        return view('admin.job.job_biding', compact('pageTitle', 'emptyMessage', 'jobBidings'));
    }


    public function jobBidingDetails($id)
    {
        $pageTitle = "Job Biding Details";
        $jobBidingDetails = JobBiding::where('id', $id)->firstOrFail();
        return view('admin.job.job_biding_details', compact('pageTitle', 'jobBidingDetails'));
    }


    public function search(Request $request, $scope)
    {
        $search = $request->search;
        $jobs = Job::where(function ($jobs) use ($search) {
            $jobs->where('amount', $search)
                ->orWhereHas('user', function ($user) use ($search) {
                    $user->where('username', 'like', "%$search%");
                });
            });
        $pageTitle = '';
        switch ($scope) {
            case 'approved':
                $pageTitle .= 'Approved ';
                $jobs = $jobs->where('status', 1);
                break;
            case 'pending':
                $pageTitle .= 'Pending ';
                $jobs = $jobs->where('status', 0);
                break;
            case 'closed':
                $pageTitle .= 'Cancel ';
                $jobs = $jobs->where('status', 2);
                break;
            case 'cancel':
                $pageTitle .= 'Cancel ';
                $jobs = $jobs->where('status', 3);
                break;
        }
        $jobs = $jobs->latest()->paginate(getPaginate());
        $pageTitle .= 'Job search by - ' . $search;
        $emptyMessage = 'No data found';
        return view('admin.job.index', compact('pageTitle', 'search', 'scope', 'emptyMessage', 'jobs'));
    }
}
