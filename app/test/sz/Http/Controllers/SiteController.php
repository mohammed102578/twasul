<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Models\Frontend;
use App\Models\Language;
use App\Models\Service;
use App\Models\SupportAttachment;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\Conversation;
use App\Models\Software;
use App\Models\Subscriber;
use Validator;
use App\Models\Booking;
use App\Models\JobBiding;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Comment;
use App\Models\Advertise;
use App\Models\ReviewRating;
use App\Models\Job;
use App\Models\Rank;
use App\Models\Features;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class SiteController extends Controller
{
    public function __construct()
    {
        $this->activeTemplate = activeTemplate();
    }

    public function index()
    {
        $reference = @$_GET['reference'];
        if ($reference) {
            session()->put('reference', $reference);
        }
        $pageTitle = "Home";
        $emptyMessage = "No data found";
        $services = Service::where('status', 1)->where('featured', 1)->whereHas('category', function ($q) {
            $q->where('status', 1);
        })->limit(20)->inRandomOrder()->with('user', 'user.rank')->get();
        return view($this->activeTemplate . 'home', compact('pageTitle', 'services', 'emptyMessage'));
    }

    public function service()
    {
        $pageTitle = "Service";
        $emptyMessage = "No data found";
        $services = Service::where('status', 1)->whereHas('category', function ($q) {
            $q->where('status', 1);
        })->with('user', 'user.rank')->inRandomOrder()->paginate(getPaginate());
        return view($this->activeTemplate . 'service', compact('pageTitle', 'services', 'emptyMessage'));
    }

    public function serviceDetails($slug, $id)
    {
        $pageTitle = "Service details";
        $service = Service::where('status', 1)->whereHas('category', function ($q) {
            $q->where('status', 1);
        })->where('id', decrypt($id))->firstOrFail();

        $otherServices = Service::where('id', '!=', $service->id)->where('status', 1)->whereHas('category', function ($q) {
            $q->where('status', 1);
        })->where('user_id', $service->user_id)->with('user', 'user.rank')->limit(4)->orderBy('id', 'DESC')->get();

        $totalService = Service::where('status', 1)->whereHas('category', function ($q) {
            $q->where('status', 1);
        })->where('user_id', $service->user_id)->with('user')->count();

        $activeUser = Auth::user();
        $conversion = null;
        $serviceGetRating = null;
        $serviceUser = User::where('id', $service->user_id)->firstOrFail();
        if ($activeUser) {
            $serviceGetRating = Booking::where('user_id', $activeUser->id)->where('working_status', 1)->where('service_id', $service->id)->first();
            $conversion = Conversation::where(function ($query) use ($activeUser, $serviceUser) {
                $query->orWhere('sender_id', $activeUser->id)
                    ->orWhere('receiver_id', $activeUser->id);
            })->where(function ($query2) use ($activeUser, $serviceUser) {
                $query2->orWhere('sender_id', $serviceUser->id)->orWhere('receiver_id', $serviceUser->id);
            })->first();
        }
        $workInprogress = Booking::where('status', '!=', 0)->where('working_status', 4)->whereHas('service', function ($q) use ($service) {
            $q->where('user_id', $service->user_id);
        })->count();

        $reviewRataingAvg = ReviewRating::whereHas('service', function ($q) use ($service) {
            $q->where('user_id', $service->user_id);
        })->orWhereHas('software', function ($q) use ($service) {
            $q->where('user_id', $service->user_id);
        })->avg('rating');

        $comments = Comment::where('service_id', $service->id)->with('user', 'commentReply')->paginate(7);
        $reviews = ReviewRating::where('service_id', $service->id)->with('user', 'service')->paginate(7);
        return view($this->activeTemplate . 'service_deatils', compact('pageTitle', 'service', 'otherServices', 'totalService', 'conversion', 'comments', 'reviews', 'serviceGetRating', 'reviewRataingAvg', 'workInprogress'));
    }

    public function software()
    {
        $pageTitle = "Software";
        $emptyMessage = "No data found";
        $softwares = Software::where('status', 1)->whereHas('category', function ($q) {
            $q->where('status', 1);
        })->with('user', 'user.rank')->latest()->paginate(getPaginate());
        return view($this->activeTemplate . 'software', compact('pageTitle', 'softwares', 'emptyMessage'));
    }


    public function softwareDetails($slug, $id)
    {
        $pageTitle = "Software Details";
        $software = Software::where('status', 1)->whereHas('category', function ($q) {
            $q->where('status', 1);
        })->where('id', decrypt($id))->firstOrFail();

        $otherServices = Service::where('status', 1)->whereHas('category', function ($q) {
            $q->where('status', 1);
        })->where('user_id', $software->user_id)->with('user')->limit(4)->orderBy('id', 'DESC')->get();

        $totalService = Service::where('status', 1)->whereHas('category', function ($q) {
            $q->where('status', 1);
        })->where('user_id', $software->user_id)->count();

        $activeUser = Auth::user();
        $softwareGetRating = null;
        $softwareUser = User::where('id', $software->user_id)->firstOrFail();
        if ($activeUser) {
            $softwareGetRating = Booking::where('user_id', $activeUser->id)->where('status', 3)->where('working_status', 1)->where('software_id', $software->id)->first();
        }

        $workInprogress = Booking::where('status', '!=', 0)->where('working_status', 4)->whereHas('service', function ($q) use ($software) {
            $q->where('user_id', $software->user_id);
        })->count();

        $softwareSales = Booking::where('status', 3)->where('working_status', 1)->whereNotNull('software_id')->where('software_id', $software->id)->count();
        $reviewRataingAvg = ReviewRating::whereHas('service', function ($q) use ($software) {
            $q->where('user_id', $software->user_id);
        })->orWhereHas('software', function ($q) use ($software) {
            $q->where('user_id', $software->user_id);
        })->avg('rating');

        $comments = Comment::where('software_id', $software->id)->with('user', 'commentReply')->paginate(7);
        $reviews = ReviewRating::where('software_id', $software->id)->with('user')->paginate(7);
        return view($this->activeTemplate . 'software_details', compact('pageTitle', 'software', 'otherServices', 'totalService', 'comments', 'reviews', 'softwareGetRating', 'softwareSales', 'reviewRataingAvg', 'workInprogress'));
    }

    public function job()
    {
        $pageTitle = "All Jobs";
        $emptyMessage = "No data found";
        $jobs = Job::where('status', 1)->whereHas('category', function ($q) {
            $q->where('status', 1);
        })->with('user', 'user.rank', 'jobBiding')->paginate(getPaginate());
        return view($this->activeTemplate . 'job', compact('pageTitle', 'jobs', 'emptyMessage'));
    }

    public function jobDeatils($slug, $id)
    {
        $pageTitle = "Job Details";
        $job = Job::where('status', 1)->where('id', decrypt($id))->firstOrFail();

        $otherServices = Service::where('status', 1)->whereHas('category', function ($q) {
            $q->where('status', 1);
        })->where('user_id', $job->user_id)->with('user', 'user.rank')->limit(4)->orderBy('id', 'DESC')->get();

        $totalService = Service::where('status', 1)->whereHas('category', function ($q) {
            $q->where('status', 1);
        })->where('user_id', $job->user_id)->count();

        $workInprogress = Booking::where('status', '!=', 0)->where('working_status', 4)->whereHas('service', function ($q) use ($job) {
            $q->where('user_id', $job->user_id);
        })->count();

        $reviewRataingAvg = ReviewRating::whereHas('service', function ($q) use ($job) {
            $q->where('user_id', $job->user_id);
        })->orWhereHas('software', function ($q) use ($job) {
            $q->where('user_id', $job->user_id);
        })->avg('rating');

        $jobBidings = JobBiding::where('job_id', $job->id)->with('user')->inRandomOrder()->paginate(5);
        $comments = Comment::where('job_id', $job->id)->with('user')->paginate(7);
        return view($this->activeTemplate . 'job_details', compact('pageTitle', 'job', 'otherServices', 'comments', 'reviewRataingAvg', 'workInprogress', 'totalService', 'jobBidings'));
    }

    public function userProfile($username)
    {
        $pageTitle = "User Profile";
        $activeUser = Auth::user();
        $user = User::where('username', $username)->with('rank')->firstOrFail();
        $reviewCal = ReviewRating::whereHas('service', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->orWhereHas('software', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->with('user');
        $userReviews = $reviewCal->paginate(6);
        $reviewCount = $reviewCal->count();
        $reviewAvg = $reviewCal->avg('rating');

        $workCompleteCount = Booking::where('status', 3)->where('working_status', 1)->whereHas('service', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->count();

        $workPendingCount = Booking::where('status', 1)->where('working_status', 0)->whereHas('service', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->count();

        $userServices = Service::where('user_id', $user->id)->whereHas('category', function ($q) {
            $q->where('status', 1);
        })->with('user', 'user.rank')->paginate(6);

        $userSoftwares = Software::where('user_id', $user->id)->whereHas('category', function ($q) {
            $q->where('status', 1);
        })->with('user', 'user.rank')->paginate(6);

        $userJobs = Job::where('user_id', $user->id)->whereHas('category', function ($q) {
            $q->where('status', 1);
        })->with('user', 'user.rank', 'jobBiding')->paginate(6);

        $conversion = null;
        if ($activeUser) {
            $conversion = Conversation::where(function ($query) use ($activeUser, $user) {
                $query->orWhere('sender_id', $activeUser->id)
                    ->orWhere('receiver_id', $activeUser->id);
            })->where(function ($query2) use ($activeUser, $user) {
                $query2->orWhere('sender_id', $user->id)->orWhere('receiver_id', $user->id);
            })->first();
        }
        return view($this->activeTemplate . 'profile', compact('pageTitle', 'user', 'reviewCount', 'reviewAvg', 'workCompleteCount', 'workPendingCount', 'workPendingCount', 'conversion', 'userReviews', 'userServices', 'userSoftwares', 'userJobs'));
    }


    public function userService($username)
    {
        $pageTitle = "User Profile";
        $activeUser = Auth::user();
        $user = User::where('username', $username)->with('rank')->firstOrFail();
        $reviewCal = ReviewRating::whereHas('service', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->orWhereHas('software', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
        $reviewCount = $reviewCal->count();
        $reviewAvg = $reviewCal->avg('rating');

        $workCompleteCount = Booking::where('status', 3)->where('working_status', 1)->whereHas('service', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->count();

        $workPendingCount = Booking::where('status', 1)->where('working_status', 0)->whereHas('service', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->count();

        $userServices = Service::where('user_id', $user->id)->whereHas('category', function ($q) {
            $q->where('status', 1);
        })->with('user', 'user.rank')->paginate(getPaginate());
        return view($this->activeTemplate . 'user_service', compact('pageTitle', 'user', 'reviewCount', 'reviewAvg', 'workCompleteCount', 'workPendingCount', 'workPendingCount', 'userServices'));
    }


    public function userSoftware($username)
    {
        $pageTitle = "User Profile";
        $activeUser = Auth::user();
        $user = User::where('username', $username)->firstOrFail();
        $reviewCal = ReviewRating::whereHas('service', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->orWhereHas('software', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
        $reviewCount = $reviewCal->count();
        $reviewAvg = $reviewCal->avg('rating');

        $workCompleteCount = Booking::where('status', 3)->where('working_status', 1)->whereHas('service', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->count();

        $workPendingCount = Booking::where('status', 1)->where('working_status', 0)->whereHas('service', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->count();

        $userSoftwares = Software::where('user_id', $user->id)->whereHas('category', function ($q) {
            $q->where('status', 1);
        })->with('user', 'user.rank')->paginate(getPaginate());
        return view($this->activeTemplate . 'user_software', compact('pageTitle', 'user', 'reviewCount', 'reviewAvg', 'workCompleteCount', 'workPendingCount', 'workPendingCount', 'userSoftwares'));
    }


    public function userJob($username)
    {
        $pageTitle = "User Profile";
        $activeUser = Auth::user();
        $user = User::where('username', $username)->firstOrFail();
        $reviewCal = ReviewRating::whereHas('service', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->orWhereHas('software', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
        $reviewCount = $reviewCal->count();
        $reviewAvg = $reviewCal->avg('rating');

        $workCompleteCount = Booking::where('status', 3)->where('working_status', 1)->whereHas('service', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->count();

        $workPendingCount = Booking::where('status', 1)->where('working_status', 0)->whereHas('service', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->count();

        $userJobs = Job::where('user_id', $user->id)->whereHas('category', function ($q) {
            $q->where('status', 1);
        })->with('user', 'user.rank')->paginate(getPaginate());
        return view($this->activeTemplate . 'user_job', compact('pageTitle', 'user', 'reviewCount', 'reviewAvg', 'workCompleteCount', 'workPendingCount', 'workPendingCount', 'userJobs'));
    }

    public function contact()
    {
        $pageTitle = "Contact Us";
        return view($this->activeTemplate . 'contact', compact('pageTitle'));
    }

    public function contactSubmit(Request $request)
    {

        $attachments = $request->file('attachments');
        $allowedExts = array('jpg', 'png', 'jpeg', 'pdf');

        $this->validate($request, [
            'name' => 'required|max:191',
            'email' => 'required|max:191',
            'subject' => 'required|max:100',
            'message' => 'required',
        ]);


        $random = getNumber();

        $ticket = new SupportTicket();
        $ticket->user_id = auth()->id() ?? 0;
        $ticket->name = $request->name;
        $ticket->email = $request->email;
        $ticket->priority = 2;


        $ticket->ticket = $random;
        $ticket->subject = $request->subject;
        $ticket->last_reply = Carbon::now();
        $ticket->status = 0;
        $ticket->save();

        $adminNotification = new AdminNotification();
        $adminNotification->user_id = auth()->id() ?? 0;
        $adminNotification->title = 'A new support ticket has opened ';
        $adminNotification->click_url = urlPath('admin.ticket.view', $ticket->id);
        $adminNotification->save();

        $message = new SupportMessage();
        $message->supportticket_id = $ticket->id;
        $message->message = $request->message;
        $message->save();

        $notify[] = ['success', 'ticket created successfully!'];

        return redirect()->route('ticket.view', [$ticket->ticket])->withNotify($notify);
    }

    public function changeLanguage($lang = null)
    {
        $prev_link = url()->previous();
        $s = preg_replace('#&?lang=[^&]*#', '' , $prev_link) . PHP_EOL;
        $language = Language::where('code', $lang)->first();
        if (!$language) $lang = 'en';
        session()->put('lang', $lang);
        
        return redirect($s);
    
    }

    public function blog()
    {
        $blogs = Frontend::where('data_keys', 'blog.element')->where('status',1)->paginate(getPaginate());
        $pageTitle = "Blog Post";
        return view($this->activeTemplate . 'blog', compact('blogs', 'pageTitle'));
    }

    public function blogDetails($id, $slug)
    {
        $fservices = Service::where('status', 1)->where('featured', 1)->whereHas('category', function ($q) {
            $q->where('status', 1);
        })->paginate(getPaginate(4));
        $blog = Frontend::where('id', $id)->where('data_keys', 'blog.element')->firstOrFail();
        $pageTitle = "Blog Details";
        return view($this->activeTemplate . 'blog_details', compact('blog', 'pageTitle', 'fservices'));
    }


    public function cookieAccept()
    {
        session()->put('cookie_accepted', true);
        $notify[] = ['success', 'Cookie accepted successfully'];
        return back()->withNotify($notify);
    }

    public function placeholderImage($size = null)
    {
        $imgWidth = explode('x', $size)[0];
        $imgHeight = explode('x', $size)[1];
        $text = $imgWidth . '×' . $imgHeight;
        $fontFile = realpath('assets/font') . DIRECTORY_SEPARATOR . 'RobotoMono-Regular.ttf';
        $fontSize = round(($imgWidth - 50) / 8);
        if ($fontSize <= 9) {
            $fontSize = 9;
        }
        if ($imgHeight < 100 && $fontSize > 30) {
            $fontSize = 30;
        }

        $image     = imagecreatetruecolor($imgWidth, $imgHeight);
        $colorFill = imagecolorallocate($image, 100, 100, 100);
        $bgFill    = imagecolorallocate($image, 175, 175, 175);
        imagefill($image, 0, 0, $bgFill);
        $textBox = imagettfbbox($fontSize, 0, $fontFile, $text);
        $textWidth  = abs($textBox[4] - $textBox[0]);
        $textHeight = abs($textBox[5] - $textBox[1]);
        $textX      = ($imgWidth - $textWidth) / 2;
        $textY      = ($imgHeight + $textHeight) / 2;
        header('Content-Type: image/jpeg');
        imagettftext($image, $fontSize, 0, $textX, $textY, $colorFill, $fontFile, $text);
        imagejpeg($image);
        imagedestroy($image);
    }

    public function adclicked($id)
    {
        $ads = Advertise::where('id', decrypt($id))->firstOrFail();
        $ads->click += 1;
        $ads->save();
        return redirect($ads->redirect_url);
    }


    public function footerMenu($slug, $id)
    {
        $data = Frontend::where('id', $id)->where('data_keys', 'footer.element')->firstOrFail();
        $pageTitle =  $data->data_values->menu;
        return view($this->activeTemplate . 'menu', compact('data', 'pageTitle'));
    }


    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors());
        }
        $if_exist = Subscriber::where('email', $request->email)->first();
        if (!$if_exist) {
            Subscriber::create([
                'email' => $request->email
            ]);
            return response()->json(['success' => 'Subscribed Successfully']);
        } else {
            return response()->json(['error' => 'Already Subscribed']);
        }
    }
}

