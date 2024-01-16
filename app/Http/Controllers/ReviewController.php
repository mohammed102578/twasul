<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Service;
use App\Models\Software;
use App\Models\Booking;
use App\Models\ReviewRating;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    
    public function store(Request $request)
    {
    	$request->validate([
            'rating'  => 'required|max:5|min:1',
            'review' => 'required|max:300',
            'like' => 'required|in:1,0',
            'service_id' => 'nullable|exists:services,id',
            'software_id' => 'nullable|exists:software,id',
        ]);
        $user = Auth::user();
        $reviewRequest = $request->all();
        if(array_key_exists("service_id", $reviewRequest))
        {
        	$service = Service::where('id', $request->service_id)->where('status', 1)->firstOrFail();
            $serviceBooking = Booking::where('status', '!=', 0)->whereNotNull('service_id')->where('service_id', $service->id)->where('user_id', $user->id)->first();
            if(!$serviceBooking){
                $notify[] = ['error', 'Invalid service'];
                return back()->withNotify($notify);
            }
        	$review = ReviewRating::where('user_id', $user->id)->where('service_id', $service->id)->first();
        	if($review){
        		$notify[] = ['error', 'You are already review this service'];
            	return back()->withNotify($notify);
        	}
            $rating = new ReviewRating();
        	$rating->user_id = $user->id;
            $rating->service_id = $service->id;
            $rating->rating = $request->rating;
	        $rating->review = $request->review;
	        $rating->save();

	        $ratingAvg = ReviewRating::where('service_id', $service->id)->avg('rating');

            $service->rating = intval($ratingAvg);
            if($request->like == 1){
                $service->likes +=1;
            }else{
                $service->dislike +=1;
            }
            $service->save();
            $notify[] = ['success', 'Thanks for you review'];
            return back()->withNotify($notify);
        }
        elseif(array_key_exists("software_id", $reviewRequest))
        {
        	$software = Software::where('id', $request->software_id)->where('status', 1)->firstOrFail();
            $softwareBooking = Booking::where('status', '!=', 0)->whereNotNull('software_id')->where('software_id', $software->id)->where('user_id', $user->id)->first();
            if(!$softwareBooking){
                $notify[] = ['error', 'Invalid software'];
                return back()->withNotify($notify);
            }
            $review = ReviewRating::where('user_id', $user->id)->where('software_id', $software->id)->first();
            if($review){
                $notify[] = ['error', 'You are already review this software'];
                return back()->withNotify($notify);
            }
            $rating = new ReviewRating();
        	$rating->user_id = $user->id;
            $rating->software_id = $software->id;
            $rating->rating = $request->rating;
	        $rating->review = $request->review;
	        $rating->save();

	        $ratingAvg = ReviewRating::where('software_id', $software->id)->avg('rating');
            $software->rating = intval($ratingAvg);
            if($request->like == 1){
                $software->likes +=1;
            }else{
                $software->dislike +=1;
            }
            $software->save();
            $notify[] = ['success', 'Thanks for you review'];
            return back()->withNotify($notify);
        }
    }
}
