<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Events\MessageSent;
use App\Events\MessageRead;
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
            'content' => 'nullable|string|max:2000',
            'attachment' => 'nullable|file|max:5120',
        ]);

        if (empty($request->content) && !$request->hasFile('attachment')) {
            return response()->json(['error' => 'Message or attachment is required'], 422);
        }

        $path = null;
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('chat_attachments', 'public');
        }

        $message = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'content' => $request->content,
            'attachment_path' => $path,
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

    public function markRead(Request $request)
    {
        $request->validate(['sender_id' => 'required|exists:users,id']);

        $authId = Auth::id();
        $senderId = $request->sender_id;

        $updated = Message::where('sender_id', $senderId)
            ->where('receiver_id', $authId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        if ($updated > 0) {
            try {
                broadcast(new MessageRead($authId, $senderId))->toOthers();
            } catch (\Exception $e) { }
        }

        $unreadCount = Message::where('receiver_id', $authId)->where('is_read', false)->count();

        return response()->json(['status' => 'success', 'unread_count' => $unreadCount]);
    }
}
