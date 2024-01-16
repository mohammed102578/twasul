<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\Category;
use Carbon\Carbon;

class ServiceController extends Controller
{

    public function index()
    {
    	$pageTitle = "Manage All Service";
    	$emptyMessage = "No data found";
    	$services = Service::with('category', 'user', 'subCategory')->latest()->paginate(getPaginate());
    	return view('admin.service.index', compact('pageTitle', 'emptyMessage', 'services'));
    }

    public function details($id)
    {
    	$pageTitle = "Service details";
    	$service = Service::findOrFail($id);
    	return view('admin.service.details', compact('pageTitle', 'service'));
    }


    public function pending()
    {
    	$pageTitle = "Manage Pending Service";
    	$emptyMessage = "No data found";
    	$services = Service::where('status', 0)->with('category', 'user', 'subCategory')->latest()->paginate(getPaginate());
    	return view('admin.service.index', compact('pageTitle', 'emptyMessage', 'services'));
    }
    public function approved()
    {
    	$pageTitle = "Manage Approved Service";
    	$emptyMessage = "No data found";
    	$services = Service::where('status', 1)->with('category', 'user', 'subCategory')->latest()->paginate(getPaginate());
    	return view('admin.service.index', compact('pageTitle', 'emptyMessage', 'services'));
    }
    public function cancel()
    {
    	$pageTitle = "Manage Cancel Service";
    	$emptyMessage = "No data found";
    	$services = Service::where('status', 3)->with('category', 'user', 'subCategory')->latest()->paginate(getPaginate());
    	return view('admin.service.index', compact('pageTitle', 'emptyMessage', 'services'));
    }

    public function approvedBy(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:services,id'
        ]);
        $service = Service::findOrFail($request->id);
        $service->status = 1;
        $service->created_at = Carbon::now();
        $service->save();
        $notify[] = ['success', 'Service has been approved'];
        return back()->withNotify($notify);
    }

    public function cancelBy(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:services,id'
        ]);
        $service = Service::findOrFail($request->id);
        $service->status = 2;
        $service->created_at = Carbon::now();
        $service->save();
        $notify[] = ['success', 'Service has been canceled'];
        return back()->withNotify($notify);
    }

    public function featuredInclude(Request $request)
    {
    	$request->validate([
            'id' => 'required|exists:services,id'
        ]);
        $service = Service::findOrFail($request->id);
        $service->featured = 1;
        $service->save();
        $notify[] = ['success', 'Include this service featured list'];
        return back()->withNotify($notify);
    }

    public function featuredNotInclude(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:services,id'
        ]);
        $service = Service::findOrFail($request->id);
        $service->featured = 0;
        $service->save();
        $notify[] = ['success', 'Remove this service featured list'];
        return back()->withNotify($notify);
    }


    public function serviceCategory(Request $request)
    {
    	$category = Category::findOrFail($request->category);
        $categoryId = $category->id;
    	$pageTitle = "Service search by category - " . $category->name;
    	$emptyMessage = "No data found";
    	$services = Service::where('category_id', $category->id)->with('category', 'user', 'subCategory')->latest()->paginate(getPaginate());
    	return view('admin.service.index', compact('pageTitle', 'emptyMessage', 'services', 'categoryId'));
    }

    public function search(Request $request, $scope)
    {
        $search = $request->search;
        $services = Service::where(function ($services) use ($search) {
            $services->where('price', $search)
                ->orWhereHas('user', function ($user) use ($search) {
                    $user->where('username', 'like', "%$search%");
                });
            });
        $pageTitle = '';
        switch ($scope) {
            case 'approved':
                $pageTitle .= 'Approved ';
                $services = $services->where('status', 1);
                break;
            case 'pending':
                $pageTitle .= 'Pending ';
                $services = $services->where('status', 0);
                break;
            case 'cancel':
                $pageTitle .= 'Cancel ';
                $services = $services->where('status', 2);
                break;
        }
        $services = $services->latest()->paginate(getPaginate());
        $pageTitle .= 'Service search by - ' . $search;
        $emptyMessage = 'No data found';
        return view('admin.service.index', compact('pageTitle', 'search', 'scope', 'emptyMessage', 'services'));
    }
}
