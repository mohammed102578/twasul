<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Rank;

class RankController extends Controller
{
    
    public function index()
    {
    	$pageTitle = "Manage User Rank";
    	$emptyMessage = "No data found";
    	$ranks = Rank::latest()->paginate(getPaginate());
    	return view('admin.rank.index', compact('pageTitle', 'emptyMessage', 'ranks'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'level' => 'required|max:40|unique:ranks',
            'amount' => 'required|numeric|gt:0',
        ]);
        $rank = new Rank();
        $rank->level = $request->level;
        $rank->amount = $request->amount;
        $rank->status = $request->status ? 1 : 0;
        $rank->save();
        $notify[] = ['success', 'Rank has been created.'];
        return back()->withNotify($notify);
    }

    public function update(Request $request)
    {
    	$request->validate([
            'id' => 'required|exists:ranks,id',
            'level' => 'required|max:40|unique:ranks,level,'.$request->id,
            'amount' => 'required|numeric|gt:0',
        ]);
        $rank = Rank::find($request->id);
        $rank->level = $request->level;
        $rank->amount = $request->amount;
        $rank->status = $request->status ? 1 : 0;
        $rank->save();
        $notify[] = ['success', 'Rank has been updated.'];
        return back()->withNotify($notify);
    }

}
