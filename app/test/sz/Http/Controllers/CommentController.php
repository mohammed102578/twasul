<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\Service;
use App\Models\Software;
use App\Models\Job;
use App\Models\CommentReply;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{

    public function store(Request $request)
    {
        $commentRequest = $request->all();
        if(array_key_exists("service_id", $commentRequest)){
        	$request->validate([
                'comment' => 'required|string|max:500',
                'service_id' => 'required|exists:services,id'
            ]);
            $service = Service::where('id', $request->service_id)->where('status', 1)->firstOrFail();
            $comment = new Comment();
            $comment->user_id = Auth::user()->id;
            $comment->service_id = $service->id;
            $comment->comments = $request->comment;
            $comment->save();
        }elseif(array_key_exists("software_id", $commentRequest)){
            $request->validate([
                'comment' => 'required|string|max:500',
                'software_id' => 'required|exists:software,id'
            ]);
            $software = Software::where('id', $request->software_id)->where('status', 1)->firstOrFail();
            $comment = new Comment();
            $comment->user_id = Auth::user()->id;
            $comment->software_id = $software->id;
            $comment->comments = $request->comment;
            $comment->save();
        }
        elseif(array_key_exists("job_id", $commentRequest)){
            $request->validate([
                'comment' => 'required|string|max:500',
                'job_id' => 'required|exists:jobs,id'
            ]);
            $job = Job::where('id', $request->job_id)->where('status', 1)->firstOrFail();
            $comment = new Comment();
            $comment->user_id = Auth::user()->id;
            $comment->job_id = $job->id;
            $comment->comments = $request->comment;
            $comment->save();
        }
        $notify[] = ['success', 'Comment Success'];
        return back()->withNotify($notify);
    }


    public function commentReply(Request $request)
    {
        $commentRequest = $request->all();
        if(array_key_exists("service_id", $commentRequest)){
        	$request->validate([
                'comment' => 'required|string|max:500',
                'comment_id' => 'required|exists:comments,id',
                'service_id' => 'required|exists:services,id'
            ]);
            $service = Service::where('id', $request->service_id)->where('status', 1)->firstOrFail();
            $comment = new CommentReply();
            $comment->user_id = Auth::user()->id;
            $comment->comment_id = $request->comment_id;
            $comment->service_id = $service->id;
            $comment->comments = $request->comment;
            $comment->save();
        }
        if(array_key_exists("software_id", $commentRequest)){
            $request->validate([
                'comment' => 'required|string|max:500',
                'comment_id' => 'required|exists:comments,id',
                'software_id' => 'required|exists:software,id'
            ]);
            $software = Software::where('id', $request->software_id)->where('status', 1)->firstOrFail();
            $comment = new CommentReply();
            $comment->user_id = Auth::user()->id;
            $comment->comment_id = $request->comment_id;
            $comment->service_id = $software->id;
            $comment->comments = $request->comment;
            $comment->save();
        }
        elseif(array_key_exists("job_id", $commentRequest)){
            $request->validate([
                'comment' => 'required|string|max:500',
                'comment_id' => 'required|exists:comments,id',
                'job_id' => 'required|exists:jobs,id'
            ]);
            $job = Job::where('id', $request->job_id)->where('status', 1)->firstOrFail();
            $comment = new CommentReply();
            $comment->user_id = Auth::user()->id;
            $comment->comment_id = $request->comment_id;
            $comment->job_id = $job->id;
            $comment->comments = $request->comment;
            $comment->save();
        }
        $notify[] = ['success', 'Comment has been replyed'];
        return back()->withNotify($notify);
    }
}
