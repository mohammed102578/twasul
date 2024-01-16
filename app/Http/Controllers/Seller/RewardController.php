<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Lib\GoogleAuthenticator;
use App\Models\AdminNotification;
use App\Models\GeneralSetting;
use App\Models\Transaction;
use App\Models\WithdrawMethod;
use App\Models\Withdrawal;
use App\Models\SubCategory;
use App\Models\FavoriteItem;
use App\Models\Service;
use App\Models\Reward;
use App\Models\Deposit;
use App\Models\User;
use Illuminate\Database\QueryException;
use App\Models\Job;
use App\Models\Software;
use App\Models\RewardReview;
use App\Models\RewardReviewDetail;
use App\Rules\FileTypeValidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Session;


class RewardController extends Controller
{
    public function __construct()
    {
        $this->activeTemplate = activeTemplate();
    }
    public function request_review_submit(Request $request)
    {
        try {
            // Get data using the Session facade
            $deposit_ids = Session::get('deposit_ids');
            $reward_perent = Reward::where('reward_category', 5)->where('end_date', '>=', date('Y-m-d'))->where('either_percent_or_fixed', 2)->first(); // 5= Referral
            $user = Auth::user();
            $reward_review = new RewardReview();
            $reward_review->user_id = $user->id;
            $reward_review->no_of_rewarded_referrals = $request->no_of_rewarded_referrals;
            $reward_review->no_of_rewarded_invited_users = $request->no_of_rewarded_invited_users;
            $reward_review->invited_users_profit_amount = $request->invited_users_profit_amount;
            $reward_review->referral_profit_amount = $request->referral_profit_amount;
            $reward_review->total_profit_amount = $request->total_profit_amount;

            if ($reward_perent) {
                $reward_review->deposit_ids = json_encode($deposit_ids);
                $reward_review->depositId_profit = json_encode($request->depositId_profit);
            }
            $reward_review->save();

            $invited_rewarded_users_array = $request->invited_rewarded_users_array;
            $invited_rewarded_users_amount_array = $request->invited_rewarded_users_amount_array;


            if ($invited_rewarded_users_amount_array) {
                for ($index = 0; $index < count($invited_rewarded_users_amount_array); $index++) {
                    $reward_review_details = new RewardReviewDetail();
                    $reward_review_details->user_id = $user->id;
                    $reward_review_details->reward_review_id = $reward_review->id;
                    $reward_review_details->invited_user_id = $invited_rewarded_users_array[$index];
                    $reward_review_details->profit_amount = $invited_rewarded_users_amount_array[$index];
                    $reward_review_details->save();
                }
            }


            return true;
        } catch (QueryException $e) {
            // Handle database query exceptions here
            return false;
        } catch (Exception $e) {
            // Handle other exceptions here
            return false;
        }
    }
    public function rewards()
    {

        $user = Auth::user();
        $pageTitle = "Rewards";
        $emptyMessage = "No data found";
        $totalReferrals = User::where('ref_by', $user->id)->count();
        //dd($totalReferrals);
        $reward = Reward::where('reward_category', 5)->where('end_date', '>=', date('Y-m-d'))->first(); // 5= Referral
        $totalRewarededReferrals = RewardReview::where('user_id', $user->id)->where('status', 0)->orWhere('status', 1)->sum('no_of_rewarded_referrals');

        $reward_perent = Reward::where('reward_category', 5)->where('end_date', '>=', date('Y-m-d'))->where('either_percent_or_fixed', 2)->first(); // 5= Referral

        $reward_fixed = Reward::where('reward_category', 5)->where('end_date', '>=', date('Y-m-d'))->where('either_percent_or_fixed', 1)->first(); // 5= Referral
        $profitAbleReferral = 0;
        $totalProfitFromReferral = 0;
        $leftReferrals = 0;
        $totalRewaredReferrals = 0;
        if ($reward_fixed) {
            $profitAbleReferral = floor(($totalReferrals - $totalRewarededReferrals) / $reward_fixed->number_of_referrals);
            //dd($profitAbleReferral);
            $totalProfitFromReferral = $profitAbleReferral * $reward_fixed->profit;
            //dd( $reward_fixed->number_of_referrals);
            $leftReferrals = ($totalReferrals - $totalRewarededReferrals) % $reward_fixed->number_of_referrals;
            // dd( $leftReferrals);
            $totalRewaredReferrals = $totalReferrals - $totalRewarededReferrals - $leftReferrals;
            if ($totalProfitFromReferral == 0) {
                $totalRewaredReferrals = 0;
            }
        }
        //dd($totalProfitFromReferral);


        //$invited_users = User::where('ref_by', $user->id)->get();

        $invited_users = User::with('deposits')
            ->selectRaw('users.*, deposits.amount as total_amount,deposits.is_profited,deposits.profit_given,deposits.id as depositId')
            ->leftJoin('deposits', 'users.id', '=', 'deposits.user_id')
            ->where('users.ref_by', $user->id)
            ->where('deposits.amount', '!=', 0)
            // ->where('deposits.is_profited', 0)//0=Not profitted yet
            // ->groupBy('users.id')
            ->get();

        $invited_users_deposits_ids = User::with('deposits')
            ->selectRaw('deposits.*')
            ->leftJoin('deposits', 'users.id', '=', 'deposits.user_id')
            ->where('users.ref_by', $user->id)
            ->where('deposits.is_profited', 0) //0=Not profitted yet
            ->where('deposits.amount', '!=', 0)
            ->get();

        $deposit_ids = [];

        foreach ($invited_users_deposits_ids as $invited_users_deposits_id) {
            // echo '<pre>';
            //print_r($invited_users_deposits_id);
            array_push($deposit_ids, $invited_users_deposits_id->id);
        }
        Session::put('deposit_ids', $deposit_ids);
        // dd($deposit_ids);

        $totalApprovedReward = RewardReview::where('user_id', $user->id)->where('status', 0)->orWhere('status', 1)->sum('total_profit_amount');
        /*0=pending,1=approved,2=declined*/
        $totalApprovedRewardInvitedUsers = RewardReview::where('user_id', $user->id)->where('status', 0)->orWhere('status', 1)->sum('no_of_rewarded_invited_users');
        /*0=pending,1=approved,2=declined*/
        $totalApprovedRewardReferrals = RewardReview::where('user_id', $user->id)->where('status', 0)->orWhere('status', 1)->sum('no_of_rewarded_referrals');
        /*0=pending,1=approved,2=declined*/
        $totalApprovedRewardInvitedUsersAmount = 0;
        $totalApprovedRewardReferralsAmount = 0;
        if ($reward_perent) {
            $totalApprovedRewardInvitedUsersAmount = RewardReview::where('user_id', $user->id)->where('status', 0)->orWhere('status', 0)->sum('invited_users_profit_amount');
        }
        if ($reward_fixed) {
            // dd($totalApprovedRewardInvitedUsersAmount);
            /*0=pending,1=approved,2=declined*/
            $totalApprovedRewardReferralsAmount = RewardReview::where('user_id', $user->id)->where('status', 0)->orWhere('status', 1)->sum('referral_profit_amount');
            /*0=pending,1=approved,2=declined*/
        }

        $reward_reviews = RewardReview::with('user')->where('user_id', $user->id)->latest()->paginate(getPaginate());

        return view($this->activeTemplate . 'user.seller.rewards', compact('pageTitle', 'totalApprovedRewardReferralsAmount', 'totalApprovedRewardInvitedUsersAmount', 'totalApprovedRewardReferrals', 'totalApprovedRewardInvitedUsers', 'reward_perent', 'reward_reviews', 'totalApprovedReward', 'invited_users', 'reward', 'totalReferrals', 'totalRewaredReferrals', 'leftReferrals', 'profitAbleReferral', 'totalProfitFromReferral', 'emptyMessage'));
    }
}
