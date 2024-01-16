<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;

class ConversationController extends Controller
{

    public function __construct(){
        $this->activeTemplate = activeTemplate();
    }

    public function inbox()
    {
        $pageTitle = "Inbox";
        $user = Auth::user();
        $conversions = Conversation::where('sender_id', $user->id)->orWhere('receiver_id', $user->id)->latest()->get();
        return view($this->activeTemplate . 'user.message.inbox', compact('pageTitle', 'conversions'));
    }


    public function chat($conversionId)
    {
        $conversions = Conversation::findOrFail($conversionId);
        $pageTitle = "Chat List";
        $messages = Message::where('conversion_id',$conversions->id)->with('sender', 'receiver')->get();
        return view(activeTemplate() . 'user.message.chat', compact('pageTitle','messages', 'conversionId'));
    }
    
    public function store(Request $request)
    {
        $user = Auth::user();
        if($user->id != $request->recevier_id)
        {
        	$request->validate([
        		'subject' => 'required|max:250',
        		'message' => 'required|max:500',
        		'recevier_id' => 'required|exists:users,id'
        	]);
            $conversion = new Conversation();
            $conversion->sender_id = $user->id;
            $conversion->receiver_id = $request->recevier_id;
            $conversion->save();

        	$message = new Message();
            $message->conversion_id = $conversion->id;
        	$message->sender_id = $user->id;
        	$message->receiver_id = $request->recevier_id;
        	$message->subject = $request->subject;
        	$message->message = $request->message;
        	$message->save();
        	$notify[] = ['success', 'Message Sent'];
            return back()->withNotify($notify);
        }
        $notify[] = ['error', "it's You"];
        return back()->withNotify($notify);
    }

    public function messageStore(Request $request)
    {
       
        $conversionId =Conversation::findOrFail(decrypt($request->conversion_id));
        $receiver =User::findOrFail(decrypt($request->receiver_id));
        if($request->image == null){
            $request->validate(['message' => 'required|max:500']);
        }
        elseif($request->message == null){
            $request->validate(['image' => 'required|mimes:jpeg,jpg,png|max:100000']);
        }
        elseif($request->message && $request->image){
            $request->validate([
                'message' => 'required|max:500',
                'image' => 'required|mimes:jpeg,jpg,png|max:100000'
            ]);
        }
        $message = new Message();
        $message->conversion_id = $conversionId->id;
        $message->sender_id = auth()->user()->id;
        $message->receiver_id = $receiver->id;
        $message->message = $request->message;
        $path = imagePath()['message']['path'];
        $size = imagePath()['message']['size'];
        if($request->hasFile('image')) {
            try {
                $filename = uploadImage($request->image, $path, $size);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Image could not be uploaded.'];
                return back()->withNotify($notify);
            }
            $message->file = $filename;
        }
        $message->save();
        $notify[] = ['success', 'Message Sent'];
        return back()->withNotify($notify);
    }
}
