<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttachmentRequest;
use App\Services\AttachmentService;
use Illuminate\Http\Request;

class AttachmentController extends Controller
{
    protected $attachmentService;

    public function __construct(AttachmentService $attachmentService)
    {
        $this->attachmentService = $attachmentService;
    }

    public function index($taskId)
    {
        try {
            $attachments = $this->attachmentService->index($taskId);
            return response()->json($attachments);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to retrieve attachments.'
            ], 500);
        }
    }

    public function store(StoreAttachmentRequest $request)
    {
        try {
            // Retrieve the uploaded file from the request
            $file = $request->file('file');
            // Store the file using the service
            $attachment = $this->attachmentService->storeAttachment($file);

            if ($attachment === null) {
                return response()->json(['status' => 'error', 'message' => 'Unable to upload attachment'], 500);
            }

            return response()->json(['status' => 'success', 'attachment' => $attachment], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to upload the attachment.'
            ], 500);
        }
    }
    public function update(Request $request, $attachmentId)
    {
        try {
            // Validate that the request contains a file
            $validatedData = $request->validate([
                'file' => 'required|file|max:10240' // Maximum file size of 10MB
            ]);

            // Retrieve the new uploaded file from the request
            $newFile = $request->file('file');

            // Use the service to update the attachment
            $updatedAttachment = $this->attachmentService->updateAttachment($attachmentId, $newFile);

            if ($updatedAttachment === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unable to update attachment.'
                ], 500);
            }

            return response()->json([
                'status' => 'success',
                'attachment' => $updatedAttachment
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to update the attachment.'
            ], 500);
        }
    }

    public function destroy($attachmentId)
    {
        try {
            $success = $this->attachmentService->destroy($attachmentId);

            if (!$success) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unable to delete attachment.'
                ], 500);
            }

            return response()->json(['success' => $success]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to delete the attachment.'
            ], 500);
        }
    }
}
