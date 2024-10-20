<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Services\CommentService;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    protected $commentService;

    public function __construct(CommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    public function index($taskId)
    {
        try {
            $comments = $this->commentService->index($taskId);
            return response()->json($comments);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to retrieve comments.'
            ], 500);
        }
    }

    public function store(StoreCommentRequest $request, $taskId)
    {
        try {
            $validatedData = $request->validated();
            $comment = $this->commentService->store($taskId, $validatedData);
            return response()->json($comment, 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to store the comment.'
            ], 500);
        }
    }

    public function update(UpdateCommentRequest $request, $commentId)
    {
        try {
            $validatedData = $request->validated();
            $comment = $this->commentService->update($commentId, $validatedData);
            return response()->json($comment);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to update the comment.'
            ], 500);
        }
    }

    public function destroy($commentId)
    {
        try {
            $success = $this->commentService->destroy($commentId);
            return response()->json(['success' => $success]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to delete the comment.'
            ], 500);
        }
    }
}
