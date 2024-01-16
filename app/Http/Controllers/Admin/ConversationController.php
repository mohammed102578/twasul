<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatMessageReply;
use App\Models\Conversation;
use Illuminate\Http\Request;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;

class ConversationController extends Controller
{
    public function chats()
    {

        $pageTitle = 'Message Chats';
        $emptyMessage = 'No Data found.';
        $items = Message::with(['sender'])->orderBy('id','desc')->get();
        return view('admin.message.chats', compact('items', 'pageTitle','emptyMessage'));
    }

    public function chatsReply($id = null)
    {
        $pageTitle = 'Message Chats';
        $items = Message::where('id',$id)->first();
        $messages_reply = ChatMessageReply::with(['admin'])->where('message_id',$id)->get();
       return view('admin.message.reply_chats',compact('pageTitle','items','messages_reply'));
    }

    public function Replychats (Request $request){
        $message = new ChatMessageReply();
        $message->message_id = $request->message_id;
        $message->message = $request->message;
        $message->admin_id = Auth::guard('admin')->id();
        $result = $message->save();
        if ($result){
            Message::where('id',$request->message_id)->update(['status'=>1]);
            $notify[] = ['success', "Message reply sent successfully"];
            return back()->withNotify($notify);
        }
    }

    public function pending_chats()
    {
        $pageTitle = 'Pending Message Chats';
        $emptyMessage = 'No Data found.';
        $items = Message::with(['sender'])->where('status','0')->orderBy('id','desc')->get();
        return view('admin.message.pending_chats', compact('items', 'pageTitle','emptyMessage'));
    }

    public function answered_chats()
    {
        $pageTitle = 'Answered Message Chats';
        $emptyMessage = 'No Data found.';
        $items = Message::with(['sender'])->where('status','1')->orderBy('id','desc')->get();
        return view('admin.message.answered_chats', compact('items', 'pageTitle','emptyMessage'));
    }

    public function delete_chats(Request $request)
    {

        $message = ChatMessageReply::findOrFail($request->message_id);
        $results = $message->delete();
        if ($results){
            $notify[] = ['success', "Deleted successfully"];
            return back()->withNotify($notify);
        }
    }

}
