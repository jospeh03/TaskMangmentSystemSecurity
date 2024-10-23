<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Exception;

class AttachmentService
{
    /**
     * Fetch all attachments for a given task and cache the result.
     */
    public function index($taskId)
    {
        try {
            // Cache the attachments for the given task for 1 hour (3600 seconds)
            return Cache::remember("task_{$taskId}_attachments", 3600, function () use ($taskId) {
                return DB::select('SELECT * FROM attachments WHERE attachable_id = ? AND attachable_type = ?', [$taskId, 'App\Models\Task']);
            });
        } catch (Exception $e) {
            Log::error('Error fetching attachments: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to fetch attachments.'
            ], 500);
        }
    }

    /**
     * Store an attachment for a given task.
     */
    public function storeAttachment($file)
    {
        $originalName = $file->getClientOriginalName();
        $maxFileSize = 10 * 1024 * 1024; // 10MB file size limit

        // Check if the file size exceeds the limit
        if ($file->getSize() > $maxFileSize) {
            throw new FileException(trans('general.fileTooLarge'), 413);  // 413 Payload Too Large
        }

        // Validate the MIME type (optional)
        $allowedMimeTypes = ['application/pdf', 'application/msword', 'application/vnd.ms-excel', 'image/jpeg', 'image/png', 'image/gif', 'video/mp4'];
        $mimeType = $file->getClientMimeType();

        if (!in_array($mimeType, $allowedMimeTypes)) {
            throw new FileException(trans('general.invalidFileType'), 403);
        }

        // Ensure the file extension is valid
        if (preg_match('/\.[^.]+\./', $originalName)) {
            throw new Exception(trans('general.notAllowedAction'), 403);
        }

        // Check for path traversal (../ or ..\)
        if (strpos($originalName, '..') !== false || strpos($originalName, '/') !== false || strpos($originalName, '\\') !== false) {
            throw new Exception(trans('general.pathTraversalDetected'), 403);
        }

        // Generate a safe, random file name
        $fileName = Str::random(32);
        $extension = $file->getClientOriginalExtension();
        $filePath = "Attachments/{$fileName}.{$extension}";

        // Store the file securely
        $path = Storage::disk(config('filesystems.default'))->putFileAs('Attachments', $file, "{$fileName}.{$extension}");

        // Get the full URL of the uploaded file
        $url = Storage::disk(config('filesystems.default'))->url($path);

        // Store attachment record in the database using raw SQL
        try {
            DB::insert('INSERT INTO attachments (file_path, file_url, attachable_id, attachable_type) VALUES (?, ?, ?, ?)', [$path, $url, $file->task_id, 'App\Models\Task']);
            Cache::forget("task_{$file->task_id}_attachments");

            return [
                'file_path' => $path,
                'file_url' => $url
            ];
        } catch (Exception $e) {
            Log::error('Error saving attachment: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to store attachment.'
            ], 500);
        }
    }
    public function updateAttachment($attachmentId, $newFile)
    {
        try {
            // Find the existing attachment record using raw SQL
            $attachment = DB::select('SELECT * FROM attachments WHERE id = ?', [$attachmentId])[0];

            if (!$attachment) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Attachment not found.'
                ], 404);
            }

            // Delete the old file from storage
            Storage::delete($attachment->file_path);

            // Generate a new file name and path for the new file
            $fileName = Str::random(32);
            $extension = $newFile->getClientOriginalExtension();
            $filePath = "Attachments/{$fileName}.{$extension}";

            // Store the new file securely
            $newPath = Storage::disk(config('filesystems.default'))->putFileAs('Attachments', $newFile, "{$fileName}.{$extension}");

            // Get the full URL of the new file
            $newUrl = Storage::disk(config('filesystems.default'))->url($newPath);

            // Update the attachment record in the database using raw SQL
            DB::update('UPDATE attachments SET file_path = ?, file_url = ? WHERE id = ?', [$newPath, $newUrl, $attachmentId]);

            // Invalidate cache for this task’s attachments
            Cache::forget("task_{$attachment->attachable_id}_attachments");

            return [
                'file_path' => $newPath,
                'file_url' => $newUrl
            ];
        } catch (Exception $e) {
            Log::error('Error updating attachment: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to update attachment.'
            ], 500);
        }
    }

    /**
     * Delete an attachment.
     */
    public function destroy($attachmentId)
    {
        try {
            // Find the attachment using raw SQL
            $attachment = DB::select('SELECT * FROM attachments WHERE id = ?', [$attachmentId])[0];

            if (!$attachment) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Attachment not found.'
                ], 404);
            }

            // Delete the file from storage
            Storage::delete($attachment->file_path);

            // Delete the attachment record from the database using raw SQL
            DB::delete('DELETE FROM attachments WHERE id = ?', [$attachmentId]);

            // Invalidate cache for this task’s attachments
            Cache::forget("task_{$attachment->attachable_id}_attachments");

            return true;
        } catch (Exception $e) {
            Log::error('Error deleting attachment: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to delete attachment.'
            ], 500);
        }
    }
}

