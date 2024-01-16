<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Permission;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    
    public function index()
    {
    	$pageTitle = "All Staff";
    	$emptyMessage = "No data found";
    	$staffs = Admin::paginate(getPaginate());
    	return view('admin.staff.index', compact('pageTitle', 'emptyMessage', 'staffs'));
    }


    public function create()
    {
    	$pageTitle = "Staff Create";
    	$permissions = Permission::all();
    	return view('admin.staff.create', compact('pageTitle', 'permissions'));
    }


    public function store(Request $request)
    {
    	$request->validate([
            'name' => 'required|string|max:40',
            'username' => 'required|string|max:40|unique:admins',
            'email' => 'required|string|max:40|unique:admins',
            'permission' => 'required|array',
            'password' => 'required|string|min:6|confirmed'
        ]);
        $staff = new Admin();
        $staff->name = $request->name;
        $staff->username = $request->username;
        $staff->email = $request->email;
        $staff->staff_access = $request->permission;
        $staff->show_password = encrypt($request->password);
        $staff->password = Hash::make($request->password);
        $staff->save();
        notify($staff, 'STAFF_CREATE', [
            'password' => $request->password,
            'email' => $request->email,
            'username' => $request->username
        ]);
        $notify[] = ['success', 'Staff has been created.'];
        return back()->withNotify($notify);
    }

    public function edit($id)
    {
    	$pageTitle = "Staff Update";
    	$permissions = Permission::all();
    	$staff = Admin::findOrFail($id);
    	return view('admin.staff.edit', compact('pageTitle', 'staff', 'permissions'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'username' => 'required|string|max:40|unique:admins,username,'.$id,
            'email' => 'required|string|max:90|unique:admins,email,'.$id,
            'permission' => 'required|array',
            'password' => 'nullable|min:6|confirmed'
        ]);
        $staff = Admin::where('id', $id)->where('status', 0)->first();
        if(!$staff)
        {
            $notify[] = ['error', "Super Admin can't be update"];
            return back()->withNotify($notify);
        }
        $staff->name = $request->name;
        $staff->username = $request->username;
        $staff->email = $request->email;
        $staff->staff_access = $request->permission;
        $staff->show_password = $request->password ?  encrypt($request->password) : $staff->show_password;
        $staff->password = $request->password ? Hash::make($request->password) : $staff->password;
        $staff->save();
        $notify[] = ['success', 'Staff has been updated.'];
        return back()->withNotify($notify);
    }


    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:admins,id'
        ]);
        $staffDelete = Admin::where('status', 0)->where('id', $request->id)->first();
        if(!$staffDelete)
        {
            $notify[] = ['error', "Super Admin can't be delete"];
            return back()->withNotify($notify);
        }
        $staffDelete->delete();
        $notify[] = ['success', 'The staff has been deleted'];
        return back()->withNotify($notify);

    }
}
