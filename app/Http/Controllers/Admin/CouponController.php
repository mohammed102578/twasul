<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Coupon;

class CouponController extends Controller
{
    
    public function index()
    {
    	$pageTitle = "Coupon List";
    	$emptyMessage = "No data found";
    	$coupons = Coupon::latest()->paginate(getPaginate());
    	return view('admin.coupon.index', compact('coupons', 'pageTitle', 'emptyMessage'));
    }

    public function store(Request $request)
    {
    	$request->validate([
    		'name' => 'required|max:40',
    		'code' => 'required|unique:coupons|max:40',
    		'type' => 'required|in:1,2',
    		'value' => 'required|numeric|gt:0'
    	]);
        $coupon = new Coupon;
        $coupon->name = $request->name;
        $coupon->code = $request->code;
        $coupon->type = $request->type;
        $coupon->value = $request->value;
        $coupon->status = $request->status ? 1 : 2;
        $coupon->save();
    	$notify[] = ['success', 'Coupon has been created'];
	    return back()->withNotify($notify);
    }


    public function update(Request $request)
    {
    	$request->validate([
    		'name' => 'required|max:40',
    		'code' => 'required|max:40|unique:coupons,code,'.$request->id,
    		'type' => 'required|in:1,2',
    		'value' => 'required|numeric|gt:0'
    	]);
        $coupon = Coupon::findOrFail($request->id);
        $coupon->name = $request->name;
        $coupon->code = $request->code;
        $coupon->type = $request->type;
        $coupon->value = $request->value;
        $coupon->status = $request->status ? 1 : 2;
        $coupon->save();
    	$notify[] = ['success', 'Coupon has been updated'];
	    return back()->withNotify($notify);
    }


    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:coupons,id'
        ]);
        $coupon =Coupon::findOrFail($request->id);
        $coupon->delete();
        $notify[] = ['success', 'Coupon delete successfully'];
        return back()->withNotify($notify);
    }
}
