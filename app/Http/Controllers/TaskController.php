<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignUserRequest;
use App\Http\Requests\FilterRequest;
use App\Http\Requests\ReassignRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TaskController extends Controller
{
    protected $TaskService;
    
    public function __construct(TaskService $TaskService)
    {
        $this->TaskService = $TaskService;
    }

    public function index() {
        try {
            $tasks = $this->TaskService->index();
            return response()->json($tasks);
        } catch (\Exception $e) {
            Log::error('Task retrieval failed: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Unable to retrieve tasks.'], 500);
        }
    }

    public function filter(FilterRequest $request): JsonResponse {
        try {
            $filters = $request->validated();
            $tasks = $this->TaskService->filterTasks($filters);
            return response()->json($tasks);
        } catch (\Exception $e) {
            Log::error('Task filtering failed: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Unable to filter tasks.'], 500);
        }
    }

    public function store(StoreTaskRequest $request) {
        if (!auth()->user()->can('create tasks')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $validatedData = $request->validated();
            $task = $this->TaskService->store($validatedData);

            return response()->json([
                'status' => 'success',
                'message' => 'Task has been created successfully.',
                'task' => $task
            ], 201);
        } catch (\Exception $e) {
            Log::error('Task creation failed: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Task creation failed.', 'errors' => $e->getMessage()], 500);
        }
    }

    public function show(Task $task) {
        try {
            if (!auth()->user()->hasAnyRole('view tasks')) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            $task = $this->TaskService->show($task->id);
            return response()->json(['status' => 'success', 'message' => 'Task found successfully.', 'task' => $task]);
        } catch (\Exception $e) {
            Log::error('Task retrieval failed: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Task not found.'], 404);
        }
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse {
        try {
            if (!auth()->user()->can('edit tasks')) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $validatedData = $request->validated();
            $updatedTask = $this->TaskService->update($task, $validatedData);

            return response()->json(['status' => 'success', 'message' => 'Task has been updated successfully.', 'task' => $updatedTask]);
        } catch (\Exception $e) {
            Log::error('Task update failed: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Task update failed.', 'errors' => $e->getMessage()], 500);
        }
    }

    public function destroy(Task $task) {
       try{
            $deleted = $this->TaskService->softDelete($task);
            return response()->json(['status' => 'success', 'message' => 'Task has been soft-deleted successfully.']);
        } catch (\Exception $e) {
            Log::error('Task deletion failed: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Unable to delete the task.'], 500);
        }
    }

    public function assign(AssignUserRequest $request): JsonResponse {
            if (!auth()->user()->can('assign user')) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        $validatedData = $request->validated();
        $success = $this->TaskService->assignTask($validatedData['task_id'], $validatedData['assigned_to']);

        return response()->json([
            'status' => $success ? 'success' : 'error',
            'message' => $success ? 'Task assigned successfully.' : 'Unable to assign task.'
        ], $success ? 200 : 500);
    }

    public function reassign(ReassignRequest $request): JsonResponse {
        if (!auth()->user()->can('reassign user')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $validatedData = $request->validated();
        $success = $this->TaskService->reassignTask($validatedData['task_id'], $validatedData['assigned_to']);

        return response()->json([
            'status' => $success ? 'success' : 'error',
            'message' => $success ? 'Task reassigned successfully.' : 'Unable to reassign task.'
        ], $success ? 200 : 500);
    }

    public function restore($id) {
        try {
            if(!auth()->user()->can('restore task')) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            $restored = $this->TaskService->restore($id);

            return response()->json(['status' => 'success', 'message' => 'Task restored successfully.']);
        } catch (\Exception $e) {
            Log::error('Task restoration failed: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Unable to restore task.', 'errors' => $e->getMessage()], 500);
        }
    }

    public function forceDelete(Task $task) {
        try {
            if (!auth()->user()->can('delete tasks')) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            $forceDeleted = $this->TaskService->forceDelete($task);

            return response()->json(['status' => 'success', 'message' => 'Task permanently deleted successfully.']);
        } catch (\Exception $e) {
            Log::error('Task force delete failed: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Unable to permanently delete task.', 'errors' => $e->getMessage()], 500);
        }
    }
    public function updateStatus(UpdateStatusRequest $request, Task $task)
    {
        if (!auth()->user()->can('update status')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $validatedData = $request->validated();
            $updatedTask = $this->TaskService->updateStatus($task, $validatedData);

            return response()->json(['message' => 'Task status updated successfully', 'task' => $updatedTask]);
        } catch (\Exception $e) {
            Log::error('Task status update failed: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Unable to update task status.', 'errors' => $e->getMessage()], 500);
        }
    }

    public function addDependency(Task $task, Task $dependency) {
        if (!auth()->user()->can( 'add dependency')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $success = $this->TaskService->addDependency($task, $dependency);

        return response()->json([
            'status' => $success ? 'success' : 'error',
            'message' => $success ? 'Dependency added successfully.' : 'Unable to add dependency.'
        ], $success ? 200 : 500);
    }

    public function removeDependency(Task $task, Task $dependency) {
        if (!auth()->user()->can( 'remove dependency')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $success = $this->TaskService->removeDependency($task, $dependency);

        return response()->json([
            'status' => $success ? 'success' : 'error',
            'message' => $success ? 'Dependency removed successfully.' : 'Unable to remove dependency.'
        ], $success ? 200 : 500);
    }
}
