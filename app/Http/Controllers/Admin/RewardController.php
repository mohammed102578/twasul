<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use Carbon\Traits\Timestamp;
use Illuminate\Http\Request;
use App\Models\Reward;
use App\Models\RewardReview;
use Illuminate\Support\Facades\Auth;

class RewardController extends Controller
{

    public function index()
    {
        $pageTitle = "Reward List";
        $emptyMessage = "No data found";
        $rewards = Reward::latest()->paginate(getPaginate());
        return view('admin.reward.index', compact('rewards', 'pageTitle', 'emptyMessage'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
            'reward_category' => 'required',
            'profit' => 'required|max:40',
            'either_percent_or_fixed' => 'required|in:1,2',
        ]);
        $reward = new Reward;
        $reward->name = $request->name;
        $reward->start_date = date('Y-m-d', strtotime($request->start_date));
        $reward->end_date = date('Y-m-d', strtotime($request->end_date));
        $reward->number_of_days = $request->number_of_days;
        $reward->reward_category = $request->reward_category;
        $reward->number_of_referrals = $request->number_of_referrals;
        $reward->either_percent_or_fixed = $request->either_percent_or_fixed;
        $reward->value = $request->value;
        $reward->profit = $request->profit;
        $reward->status = $request->status ? 1 : 2;
        $reward->save();
        $notify[] = ['success', 'Reward has been created'];
        return back()->withNotify($notify);
    }

    public function review()
    {
        $pageTitle = "Reward Review List";
        $emptyMessage = "No data found";
        $reward_reviews = RewardReview::with('user')->latest()->paginate(getPaginate());
        return view('admin.reward.review', compact('reward_reviews', 'pageTitle', 'emptyMessage'));
    }


    public function review_update(Request $request)
    {
        $user = Auth::guard('admin')->user();
        $reward_review = RewardReview::where('id', $request->id)->first(); // 5= Referral
        $request->validate([
            'status' => 'required',
        ]);
        $reward_review = RewardReview::findOrFail($request->id);
        $reward_review->status = $request->status;
        $reward_review->approved_by = $user->id;
        $reward_review->save();

        //To track the profited and unprofited deposit amount
        $deposit_ids = json_decode($reward_review->deposit_ids);
        //dd($deposit_ids);
        if ($deposit_ids) {
            foreach ($deposit_ids as $deposit_id) {
                $deposit_update = Deposit::findOrFail($deposit_id);
                $deposit_update->is_profited = 1;
                $deposit_update->save();
            }
        }

        //To set profit given amount
        $depositId_profits = json_decode($reward_review->depositId_profit);
        if ($depositId_profits) {
            //dd($deposit_ids);
            foreach ($depositId_profits as $depositId_profit) {
                $separate = explode('_', $depositId_profit);
                if ($separate[1] != 0) {
                    $deposit_update = Deposit::findOrFail($separate[0]);
                    $deposit_update->profit_given = $separate[1];
                    $deposit_update->save();
                }
            }
        }


        $notify[] = ['success', 'Reward review has been updated'];
        return back()->withNotify($notify);
    }
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
            'reward_category' => 'required',
            'profit' => 'required|max:40',
            'either_percent_or_fixed' => 'required|in:1,2',
        ]);
        $reward = Reward::findOrFail($request->id);
        $reward->name = $request->name;
        $reward->start_date = date('Y-m-d', strtotime($request->start_date));
        $reward->end_date = date('Y-m-d', strtotime($request->end_date));
        $reward->number_of_days = $request->number_of_days;
        $reward->reward_category = $request->reward_category;
        $reward->number_of_referrals = $request->number_of_referrals;
        $reward->either_percent_or_fixed = $request->either_percent_or_fixed;
        $reward->value = $request->value;
        $reward->profit = $request->profit;
        $reward->status = $request->status ? 1 : 2;
        $reward->updated_at = date('Y-m-d H:i:s');
        $reward->save();
        $notify[] = ['success', 'Reward has been updated'];
        return back()->withNotify($notify);
    }


    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:rewards,id'
        ]);
        $reward = Reward::findOrFail($request->id);
        $reward->delete();
        $notify[] = ['success', 'Reward delete successfully'];
        return back()->withNotify($notify);
    }
}
