<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * MessageController
 *
 * Send, retrieve, edit, and delete messages within a conversation.
 * All authenticated users can message within their conversations.
 *
 * Routes prefix: /api/v1/messages
 */
class MessageController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────
    // POST /messages  — Send a message
    // ──────────────────────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'conversation_id' => ['required', 'integer', 'exists:conversations,id'],
            'body'            => ['required_without:attachment', 'nullable', 'string', 'max:5000'],
            'attachment'      => ['nullable', 'file', 'max:20480',
                                  'mimes:jpg,jpeg,png,gif,pdf,doc,docx,mp4,mp3,zip'],
        ]);

        $conversation = Conversation::findOrFail($data['conversation_id']);

        // Ensure sender is a participant
        $isParticipant = $conversation->participants()
                                      ->where('user_id', $request->user()->id)
                                      ->exists();

        if (! $isParticipant) {
            return response()->json(['success' => false, 'message' => 'You are not part of this conversation.'], 403);
        }

        $attachmentPath = null;
        $attachmentType = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('chat-attachments', 'public');
            $attachmentType = $request->file('attachment')->getMimeType();
        }

        $message = Message::create([
            'conversation_id' => $data['conversation_id'],
            'sender_id'       => $request->user()->id,
            'body'            => $data['body'] ?? null,
            'attachment_path' => $attachmentPath,
            'attachment_type' => $attachmentType,
            'is_read'         => false,
        ]);

        // In production: broadcast MessageSent event for real-time updates
        // broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Message sent.',
            'data'    => ['message' => $message->load('sender')],
        ], 201);
    }

    // ──────────────────────────────────────────────────────────────────────
    // PUT /messages/{id}  — Edit own message
    // ──────────────────────────────────────────────────────────────────────
    public function update(Request $request, int $id): JsonResponse
    {
        $message = Message::findOrFail($id);

        // Only sender can edit
        if ($message->sender_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'You can only edit your own messages.'], 403);
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message->update([
            'body'       => $data['body'],
            'is_edited'  => true,
            'edited_at'  => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message updated.',
            'data'    => ['message' => $message->fresh('sender')],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // DELETE /messages/{id}  — Delete own message (or admin)
    // ──────────────────────────────────────────────────────────────────────
    public function destroy(Request $request, int $id): JsonResponse
    {
        $message = Message::findOrFail($id);

        $user = $request->user();
        if ($message->sender_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        if ($message->attachment_path) {
            Storage::disk('public')->delete($message->attachment_path);
        }

        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted.',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // GET /messages/unread-count  — Count of unread messages for current user
    // ──────────────────────────────────────────────────────────────────────
    public function unreadCount(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $count = Message::whereHas('conversation.participants', fn ($q) =>
                            $q->where('user_id', $userId))
                        ->where('sender_id', '!=', $userId)
                        ->where('is_read', false)
                        ->count();

        return response()->json([
            'success' => true,
            'data'    => ['unread_count' => $count],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // PATCH /messages/{id}/read  — Mark single message as read
    // ──────────────────────────────────────────────────────────────────────
    public function markRead(Request $request, int $id): JsonResponse
    {
        $message = Message::findOrFail($id);

        if ($message->sender_id === $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Cannot mark your own message as read.'], 422);
        }

        $message->update(['is_read' => true, 'read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Marked as read.',
        ]);
    }
}
