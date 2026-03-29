<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    /**
     * Get chat history between auth user and another user
     */
    public function getHistory($userId)
    {
        $authId = Auth::id();

        $messages = Message::with('sender')
            ->where(function ($query) use ($authId, $userId) {
                $query->where('sender_id', $authId)->where('receiver_id', $userId);
            })
            ->orWhere(function ($query) use ($authId, $userId) {
                $query->where('sender_id', $userId)->where('receiver_id', $authId);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    /**
     * Send a new message and broadcast
     */
    public function store(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'content' => 'required|string|max:1000',
        ]);

        $message = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'content' => $request->content,
            'is_read' => false,
        ]);

        // Broadcast with Reverb gracefully
        try {
            broadcast(new MessageSent($message))->toOthers();
        } catch (\Exception $e) {
            // Silently ignore broadcasting errors if the Reverb web socket server is down
        }

        return response()->json(['status' => 'Message sent', 'message' => $message->load('sender')]);
    }
}
