<?php

namespace App\Http\Controllers;

use App\Models\ChatMessageReply;
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
        $conversions = Conversation::with(['receiver','admin','messages'])->where('sender_id', $user->id)->orWhere('receiver_id', $user->id)->latest()->get();
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

    public function openSupportChat()
    {
        if (!Auth::user()) {
            abort(404);
        }
        $pageTitle = "Support Chat";
        $user = Auth::user();
        return view($this->activeTemplate . 'user.message.create', compact('pageTitle', 'user'));
    }

    public function messageStore(Request $request)
    {
       
        $conversionId =Conversation::findOrFail(decrypt($request->conversion_id));
        $receiver =User::findOrFail(decrypt($request->receiver_id));
        /*if($request->image == null){
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
        }*/
        if($request->has('image')){
          $request->validate([
                'message' => 'required|max:500',
                'image' => 'required|mimes:jpeg,jpg,png|max:100000'
            ]);  
        }else{
            $request->validate(['message' => 'required|max:500']);
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

    public function viewMessage($id=null)
    {
        $pageTitle = 'Reply Messages'  ;
       $message =  Message::where('conversion_id',$id)->first();
       $ChatReply = ChatMessageReply::with(['admin'])->where('message_id',$message->id)->get();
       $reply = ChatMessageReply::with(['admin'])->where('message_id',$message->id)->first();

        return view('templates.basic.user.message.chat_reply',compact('pageTitle','ChatReply','reply'));
    }

    public function OpenSendMessages()
    {
        $pageTitle = 'Send Message'  ;
        return view('templates.basic.user.message.send_message',compact('pageTitle'));
    }

    public function SendSms(Request $request)
    {
        $conversation = new Conversation();
        $conversation->sender_id = Auth::user()->id;
        $conversation->receiver_id = 1;
        $results = $conversation->save();
        if ($results){
            $message = new Message();
            $message->conversion_id = $conversation->id;
            $message->sender_id = Auth::user()->id;
            $message->receiver_id = 1;
            $message->subject = $request->subject;
            $message->message = $request->message;
            $message->status = 0;
            $message_results = $message->save();
             if ($message_results){
                 $notify[] = ['success', 'Message Sent'];
                 return back()->withNotify($notify);
             }
        }
    }
}
