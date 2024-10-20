<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CommentService
{
    public function index($taskId)
    {
        try {
            return Cache::remember("task_{$taskId}_comments", 3600, function () use ($taskId) {
                return DB::select('SELECT * FROM comments WHERE commentable_id = ? AND commentable_type = ?', [$taskId, 'App\Models\Task']);
            });
        } catch (\Exception $e) {
            Log::error('Comment retrieval failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to perform the requested operation'
            ], 500);
        }
    }

    public function store($taskId, $data)
    {
        try {
            // Add authenticated user's ID to the comment data
            $data['user_id'] = auth()->id();
            $data['commentable_id'] = $taskId;
            $data['commentable_type'] = 'App\Models\Task';

            $query = "INSERT INTO comments (content, user_id, commentable_id, commentable_type) VALUES (?, ?, ?, ?)";
            DB::insert($query, [$data['content'], $data['user_id'], $data['commentable_id'], $data['commentable_type']]);

            // Invalidate cache for this task’s comments
            Cache::forget("task_{$taskId}_comments");

            return DB::getPdo()->lastInsertId(); // Return the new comment ID
        } catch (\Exception $e) {
            Log::error('Comment creation failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to perform the requested operation'
            ], 500);
        }
    }

    public function update($commentId, $data)
    {
        try {
            $query = "UPDATE comments SET content = ? WHERE id = ?";
            DB::update($query, [$data['content'], $commentId]);

            // Invalidate cache for the task's comments
            $comment = DB::select('SELECT commentable_id FROM comments WHERE id = ?', [$commentId])[0];
            Cache::forget("task_{$comment->commentable_id}_comments");

            return DB::select('SELECT * FROM comments WHERE id = ?', [$commentId])[0]; // Return the updated comment
        } catch (\Exception $e) {
            Log::error('Comment update failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to perform the requested operation'
            ], 500);
        }
    }

    public function destroy($commentId)
    {
        try {
            $comment = DB::select('SELECT commentable_id FROM comments WHERE id = ?', [$commentId])[0];
            DB::delete('DELETE FROM comments WHERE id = ?', [$commentId]);

            // Invalidate cache for this task’s comments
            Cache::forget("task_{$comment->commentable_id}_comments");

            return true;
        } catch (\Exception $e) {
            Log::error('Comment deletion failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to perform the requested operation'
            ], 500);
        }
    }
}
